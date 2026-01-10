<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Entity\Wishlist;
use App\Entity\WishlistItem;
use App\Service\WishlistService;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/readonly', name: 'readonly_')]
final class ReadonlyController extends AbstractController
{
	public function __construct(private readonly WishlistService $wishListService) {}

	#[Route('/{uuid}', name: 'get', methods: ['GET'])]
	public function get(string $uuid): JsonResponse
	{
		$wishList = $this->wishListService->findPublicByUuid($uuid);
		if (!$wishList) {
			throw $this->createNotFoundException();
		}
		return $this->json($this->serializeReadonlyWishlist($wishList));
	}

	#[Route('/{uuid}/hidden_items', name: 'hidden_items', methods: ['GET'])]
	public function hiddenItems(string $uuid, Request $request): JsonResponse
	{
		$items = $this->wishListService->hiddenItemsForUuid($uuid, $request->headers->get('Access-Code'));
		return $this->json($items);
	}

	private function serializeReadonlyWishlist(Wishlist $wishlist): array
	{
		$visibleItems = $wishlist->getItems()
			->filter(fn(WishlistItem $item) => !$item->isHidden());

		return [
			'id' => $wishlist->getId(),
			'name' => $wishlist->getName(),
			'uuid' => $wishlist->getUuid(),
			'items' => $visibleItems->getValues(),
			'has_password' => $wishlist->getAccessCode() !== null,
		];
	}
}
