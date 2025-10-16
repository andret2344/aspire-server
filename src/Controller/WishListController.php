<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\WishListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/wishlists', name: 'wishlists_')]
final class WishListController extends AbstractController
{
	public function __construct(private readonly WishListService $wishListService) {}

	#[Route('', name: 'list', methods: ['GET'])]
	public function list(Request $request): JsonResponse
	{
		$uuid = $request->query->get('uuid');
		if (!$uuid) {
			$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
			/** @var User $user */
			$user = $this->getUser();
			return $this->json($this->wishListService->listFor($user));
		}
		$wishList = $this->wishListService->findPublicByUuid($uuid);
		if (!$wishList) {
			return $this->json(['detail' => 'Not found'], 404);
		}
		foreach ($wishList->getItems() as $item) {
			if ($item->isHidden()) {
				$wishList->removeItem($item);
			}
		}
		return $this->json($wishList);
	}

	#[Route('', name: 'create', methods: ['POST'])]
	#[IsGranted('IS_AUTHENTICATED_FULLY')]
	public function create(Request $request): JsonResponse
	{
		/** @var User $user */
		$user = $this->getUser();

		$data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
		$name = trim((string)($data['name'] ?? ''));
		if ($name === '') {
			return $this->json(['detail' => 'Field "name" is required'], 400);
		}

		$wishList = $this->wishListService->create($user, $name, $data['access_code'] ?? null);
		return $this->json($wishList, 201);
	}

	#[Route('/{id<\d+>}', name: 'get', methods: ['GET'])]
	#[IsGranted('IS_AUTHENTICATED_FULLY')]
	public function getOne(int $id): JsonResponse
	{
		/** @var User $user */
		$user = $this->getUser();

		$wishList = $this->wishListService->getOwned($user, $id);
		if (!$wishList) {
			return $this->json(['detail' => 'Not found'], 404);
		}
		return $this->json($wishList);
	}

	#[Route('/{id<\d+>}', name: 'update', methods: ['PUT'])]
	#[IsGranted('IS_AUTHENTICATED_FULLY')]
	public function update(int $id, Request $request): JsonResponse
	{
		/** @var User $user */
		$user = $this->getUser();

		$wishList = $this->wishListService->getOwned($user, $id);
		if (!$wishList) {
			return $this->json(['detail' => 'Not found'], 404);
		}

		$data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
		$name = array_key_exists('name', $data) ? trim((string)$data['name']) : null;
		if ($name === '') {
			return $this->json(['detail' => 'Field "name" cannot be empty'], 400);
		}

		$hasAccessKey = array_key_exists('access_code', $data);
		$accessCode = $hasAccessKey ? (string)($data['access_code'] ?? '') : null;

		$this->wishListService->update($wishList, $name, $hasAccessKey, $accessCode);

		return $this->json($wishList);
	}

	#[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
	#[IsGranted('IS_AUTHENTICATED_FULLY')]
	public function delete(int $id): JsonResponse
	{
		/** @var User $user */
		$user = $this->getUser();

		$wl = $this->wishListService->getOwned($user, $id);
		if (!$wl) {
			return $this->json(['detail' => 'Not found'], 404);
		}

		$this->wishListService->softDelete($wl);
		return $this->json(['detail' => 'Object deleted']);
	}

	#[Route('/{id<\d+>}/set-access-code', name: 'set_access_code', methods: ['POST'])]
	#[IsGranted('IS_AUTHENTICATED_FULLY')]
	public function setAccessCode(int $id, Request $request): JsonResponse
	{
		/** @var User $user */
		$user = $this->getUser();

		$wishList = $this->wishListService->getOwned($user, $id);
		if (!$wishList) {
			return $this->json(['message' => 'Not found'], 404);
		}

		$data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
		if (!array_key_exists('access_code', $data)) {
			return $this->json(['message' => 'Access code required'], 400);
		}

		$this->wishListService->setAccessCode($wishList, (string)$data['access_code']);
		return $this->json(['message' => 'Access code set successfully']);
	}
}
