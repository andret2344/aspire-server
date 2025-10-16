<?php

namespace App\Controller;

use App\Dto\Auth\ChangePasswordRequest;
use App\Dto\Auth\RegisterUserRequest;
use App\Dto\Auth\ResetPasswordConfirmRequest;
use App\Dto\Auth\ResetPasswordStartRequest;
use App\Entity\User;
use App\Service\Auth\PasswordResetService;
use App\Service\Auth\UserService;
use App\Service\Mail\EmailService;
use DomainException;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/account', name: 'account_')]
final class AccountController extends AbstractController
{
	public function __construct(private readonly UserService          $userService,
								private readonly EmailService         $emailService,
								private readonly PasswordResetService $passwordResetService) {}

	#[Route('/register', name: 'register', methods: ['POST'])]
	public function register(Request $request): JsonResponse
	{
		$data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
		$dto = RegisterUserRequest::fromArray($data);

		try {
			$user = $this->userService->register($dto);
		} catch (DomainException|InvalidArgumentException $e) {
			return $this->json(['detail' => $e->getMessage()], 400);
		}

		return $this->json([
			'email' => $user->getEmail(),
		], 201);
	}

	#[Route('/change_password', name: 'change_password', methods: ['POST'])]
	#[IsGranted('IS_AUTHENTICATED_FULLY')]
	public function __invoke(Request $request): JsonResponse
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
			template: 'emails/password_reset_successful_confirmation.html.twig'
		);

		return $this->json(['message' => 'Password changed successfully']);
	}

	#[Route('/password_reset', name: 'password_reset', methods: ['POST'])]
	public function start(Request $request): JsonResponse
	{
		$data = json_decode($request->getContent() ?: '{}', true, 512, JSON_THROW_ON_ERROR);
		$dto = ResetPasswordStartRequest::fromArray($data);

		// Zawsze 200 – nie ujawniamy, czy istnieje email
		$this->passwordResetService->start($dto);

		return $this->json([
			'status' => 'OK',
			'message' => 'If the email exists, a reset link has been sent.',
		]);
	}

	#[Route('/password_reset/confirm', name: 'password_reset_confirm', methods: ['POST'])]
	public function confirm(Request $request): JsonResponse
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
}
