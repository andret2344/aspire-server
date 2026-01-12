<?php

namespace App\Controller;

use App\Dto\Auth\ChangePasswordRequest;
use App\Dto\Auth\RegisterUserRequest;
use App\Dto\Auth\ResetPasswordConfirmRequest;
use App\Dto\Auth\ResetPasswordStartRequest;
use App\Entity\User;
use App\Entity\VerificationToken;
use App\Service\Auth\PasswordResetService;
use App\Service\Auth\UserService;
use App\Service\DiscoveryService;
use App\Service\EmailService;
use App\Service\VerificationTokenService;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/account', name: 'account_')]
final class AccountController extends AbstractController
{
	public function __construct(private readonly UserService              $userService,
								private readonly EmailService             $emailService,
								private readonly PasswordResetService     $passwordResetService,
								private readonly DiscoveryService         $discoveryService,
								private readonly VerificationTokenService $tokenService) {}

	#[Route('/register', name: 'register', methods: ['POST'])]
	public function register(Request $request): JsonResponse
	{
		$data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
		$dto = RegisterUserRequest::fromArray($data);

		try {
			$user = $this->userService->register($dto);
			$plainToken = $this->tokenService->recreateToken($user);
			$this->sendVerificationEmail($user, $plainToken);
		} catch (DomainException|InvalidArgumentException $e) {
			return $this->json(['detail' => $e->getMessage()], 400);
		}

		return $this->json([
			'email' => $user->getEmail(),
		], 201);
	}

	#[Route('/verify_email', name: 'verify_email', methods: ['POST'])]
	public function verifyEmail(Request $request): JsonResponse
	{
		$userId = $request->toArray()['user_id'];
		$user = $this->userService->getUserById($userId);
		if ($user === null) {
			return $this->json(['detail' => 'User not found.'], 404);
		}
		if ($user->getVerifiedDate() != null) {
			return $this->json(['detail' => 'Email already verified.'], 400);
		}
		$plainToken = $this->tokenService->recreateToken($user);
		$this->sendVerificationEmail($user, $plainToken);
		return $this->json(['message' => 'Verification email sent.']);
	}

	#[Route('/me', name: 'me', methods: ['GET'])]
	#[IsGranted('IS_AUTHENTICATED_FULLY')]
	public function me(): JsonResponse
	{
		/** @var User $user */
		$user = $this->getUser();
		return $this->json([
			'id' => $user->getId(),
			'email' => $user->getEmail(),
			'is_verified' => $user->getVerifiedDate() !== null,
			'last_login' => $user->getLastLogin()
				?->format('Y-m-d H:i:sP'),
		]);
	}

	#[Route('/confirm', name: 'confirm', methods: ['POST'])]
	public function confirm(Request $request): JsonResponse
	{
		$plainToken = $request->toArray()['token'];
		if (!$plainToken) {
			return $this->json(['detail' => 'Token is required.'], 400);
		}
		$token = $this->tokenService->getToken($plainToken);
		if (!$token || !$token->isValid()) {
			return $this->json(['detail' => 'Invalid token.'], 400);
		}
		$this->userService->confirmEmail($token);
		return $this->json(['message' => 'Email confirmed.']);
	}

	#[Route('/change_password', name: 'change_password', methods: ['POST'])]
	#[IsGranted('IS_AUTHENTICATED_FULLY')]
	public function changePassword(Request $request): JsonResponse
	{
		/** @var User $user */
		$user = $this->getUser();

		$data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
		$dto = ChangePasswordRequest::fromArray($data);

		if (!$dto->passwordsMatch()) {
			return $this->json(['detail' => 'Passwords do not match.'], 400);
		}

		try {
			$this->userService->changePassword($user, $dto);
		} catch (DomainException|InvalidArgumentException $e) {
			return $this->json(['error' => $e->getMessage()], 400);
		}

		$this->emailService->send(
			from: 'aspire@aspireapp.online',
			to: $user->getEmail(),
			subject: 'Password reset successfully',
			template: 'password_reset_confirmation.html.twig'
		);

		return $this->json(['message' => 'Password changed successfully']);
	}

	#[Route('/password_reset', name: 'password_reset', methods: ['POST'])]
	public function passwordReset(Request $request): JsonResponse
	{
		$data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
		$dto = ResetPasswordStartRequest::fromArray($data);

		$this->passwordResetService->start($dto);

		return $this->json([
			'status' => 'OK',
			'message' => 'If the email exists, a reset link has been sent.',
		]);
	}

	#[Route('/password_reset/confirm', name: 'password_reset_confirm', methods: ['POST'])]
	public function passwordResetConfirm(Request $request): JsonResponse
	{
		$data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
		$dto = ResetPasswordConfirmRequest::fromArray($data);

		try {
			$this->passwordResetService->confirm($dto);
		} catch (DomainException|InvalidArgumentException $e) {
			return $this->json(['detail' => $e->getMessage()], 400);
		}

		return $this->json(['message' => 'Password reset successfully']);
	}

	private function sendVerificationEmail(User $user, string $plainToken): void
	{
		$this->emailService->send(
			from: 'aspire@aspireapp.online',
			to: $user->getEmail(),
			subject: 'Verify your email',
			template: 'emails/verify_email.html.twig',
			context: [
				'current_user' => $user->getEmail(),
				'verification_url' => $this->createUrl($this->discoveryService->getDiscoveryData()['frontend'], $plainToken)
			]
		);
	}

	private function createUrl(string $host, string $plainToken): string
	{
		return sprintf('%s/confirm/%s', $host, $plainToken);
	}
}
