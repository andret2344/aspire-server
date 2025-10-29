<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\WishList;
use App\Service\WishListItemService;
use App\Service\WishListService;
use DomainException;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use OpenApi\Attributes as OA;

#[Route('/wishlists/{wishlistId<\d+>}/items', name: 'wishlist_items_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[OA\Tag(name: 'Wishlist Items')]
final class WishListItemController extends AbstractController
{
	public function __construct(
		private readonly WishListService     $wishlists,
		private readonly WishListItemService $items,
	) {}

	private function ownedWishlistOr404(int $wishlistId): WishList|JsonResponse
	{
		/** @var User $user */
		$user = $this->getUser();
		$wishList = $this->wishlists->getOwned($user, $wishlistId);
		if (!$wishList) {
			return $this->json(['detail' => 'Not found'], 404);
		}
		return $wishList;
	}

	#[Route('', name: 'list', methods: ['GET'])]
	public function list(int $wishlistId): JsonResponse
	{
		$wishList = $this->ownedWishlistOr404($wishlistId);
		if ($wishList instanceof JsonResponse) {
			return $wishList;
		}
		return $this->json($wishList->getItems());
	}

	#[Route('', name: 'create', methods: ['POST'])]
	public function create(int $wishlistId, Request $request): JsonResponse
	{
		$wl = $this->ownedWishlistOr404($wishlistId);
		if ($wl instanceof JsonResponse) {
			return $wl;
		}

		$data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);

		$name = trim((string)($data['name'] ?? ''));
		$description = (string)($data['description'] ?? '');
		$priority = (int)($data['priority'] ?? 1);
		$hidden = (bool)($data['hidden'] ?? false);

		/** @var User $user */
		$user = $this->getUser();

		try {
			$item = $this->items->create($user, $wl, $name, $description, $priority, $hidden);
		} catch (InvalidArgumentException|DomainException $e) {
			return $this->json(['detail' => $e->getMessage()], 400);
		}

		return $this->json($item->jsonSerialize(), 201);
	}

	#[Route('/{id<\d+>}', name: 'get', methods: ['GET'])]
	public function getOne(int $wishlistId, int $id): JsonResponse
	{
		$wl = $this->ownedWishlistOr404($wishlistId);
		if ($wl instanceof JsonResponse) {
			return $wl;
		}

		$item = $this->items->getOwnedItem($wl, $id);
		if (!$item) {
			return $this->json(['detail' => 'Not found'], 404);
		}

		return $this->json($item->jsonSerialize());
	}

	#[Route('/{id<\d+>}', name: 'update', methods: ['PUT'])]
	public function update(int $wishlistId, int $id, Request $request): JsonResponse
	{
		$wl = $this->ownedWishlistOr404($wishlistId);
		if ($wl instanceof JsonResponse) {
			return $wl;
		}

		$item = $this->items->getOwnedItem($wl, $id);
		if (!$item) {
			return $this->json(['detail' => 'Not found'], 404);
		}

		$data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);

		$name = array_key_exists('name', $data) ? (string)$data['name'] : null;
		$description = array_key_exists('description', $data) ? (string)$data['description'] : null;
		$priority = array_key_exists('priority', $data) ? (int)$data['priority'] : null;
		$hidden = array_key_exists('hidden', $data) ? (bool)$data['hidden'] : null;

		try {
			$item = $this->items->update($item, $wl, $name, $description, $priority, $hidden);
		} catch (InvalidArgumentException|DomainException $e) {
			return $this->json(['detail' => $e->getMessage()], 400);
		}

		return $this->json($item->jsonSerialize());
	}

	#[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
	public function delete(int $wishlistId, int $id): JsonResponse
	{
		$wishList = $this->ownedWishlistOr404($wishlistId);
		if ($wishList instanceof JsonResponse) {
			return $wishList;
		}

		$wishListItem = $this->items->getOwnedItem($wishList, $id);
		if (!$wishListItem) {
			return $this->json(['detail' => 'Not found'], 404);
		}

		$this->items->delete($wishListItem);
		return $this->json(['detail' => 'Deleted']);
	}
}
