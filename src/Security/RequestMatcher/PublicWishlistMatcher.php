<?php

namespace App\Security\RequestMatcher;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use function in_array;
use function is_string;
use function preg_match;

class PublicWishlistMatcher implements RequestMatcherInterface
{
	public function matches(Request $request): bool
	{
		if ($request->getPathInfo() !== '/wishlists') {
			return false;
		}
		if (!in_array($request->getMethod(), ['GET', 'OPTIONS'], true)) {
			return false;
		}
		$uuid = $request->query->get('uuid');
		return is_string($uuid);
	}
}
