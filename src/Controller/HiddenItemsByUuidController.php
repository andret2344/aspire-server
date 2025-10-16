<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\WishListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class HiddenItemsByUuidController extends AbstractController
{
	public function __construct(private readonly WishListService $wishlists) {}

	#[Route('/hidden_items', name: 'wishlist_hidden_items', methods: ['GET'])]
	#[IsGranted('PUBLIC_ACCESS')]
	public function __invoke(Request $request): JsonResponse
	{
		$uuid = (string)$request->query->get('uuid', '');
		if ($uuid === '') {
			return $this->json(['detail' => 'Wishlist UUID is required.'], 400);
		}

		$items = $this->wishlists->hiddenItemsForUuid($uuid, $request->headers->get('Access-Code'));

		return $this->json($items);
	}
}
