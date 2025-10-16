<?php

declare(strict_types=1);

namespace App\Service\Mail;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final readonly class EmailService
{
	public function __construct(private MailerInterface $mailer) {}

	public function send(string $from, string $to, string $subject, string $template, array $context = []): void
	{
		$email = new TemplatedEmail()->to($to)
			->from($from)
			->subject($subject)
			->htmlTemplate($template)
			->context($context);

		$this->mailer->send($email);
	}
}
