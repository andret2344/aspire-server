<?php

namespace App\Tests\Service;

use App\Service\EmailService;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class EmailServiceTest extends TestCase
{
	public function testSend(): void
	{
		$mailer = $this->createMock(MailerInterface::class);

		$mailer->expects($this->once())
			->method('send')
			->with($this->callback(function (TemplatedEmail $email) {
				$toAddresses = $email->getTo();
				$fromAddresses = $email->getFrom();

				$this->assertCount(1, $toAddresses);
				$this->assertSame('to@example.com', $toAddresses[0]->getAddress());
				$this->assertCount(1, $fromAddresses);
				$this->assertSame('from@example.com', $fromAddresses[0]->getAddress());
				$this->assertSame('Test Subject', $email->getSubject());
				$this->assertSame('emails/test.html.twig', $email->getHtmlTemplate());
				$this->assertSame(['name' => 'John'], $email->getContext());

				return true;
			}));

		$service = new EmailService($mailer);
		$service->send('from@example.com', 'to@example.com', 'Test Subject', 'emails/test.html.twig', ['name' => 'John']);
	}

	public function testSendWithoutContext(): void
	{
		$mailer = $this->createMock(MailerInterface::class);

		$mailer->expects($this->once())
			->method('send')
			->with($this->callback(function (TemplatedEmail $email) {
				$this->assertSame([], $email->getContext());
				return true;
			}));

		$service = new EmailService($mailer);
		$service->send('from@example.com', 'to@example.com', 'Test Subject', 'emails/test.html.twig');
	}

	public function testSendWithEmptyContext(): void
	{
		$mailer = $this->createMock(MailerInterface::class);

		$mailer->expects($this->once())
			->method('send')
			->with($this->callback(function (TemplatedEmail $email) {
				$this->assertSame([], $email->getContext());
				return true;
			}));

		$service = new EmailService($mailer);
		$service->send('from@example.com', 'to@example.com', 'Test Subject', 'emails/test.html.twig', []);
	}
}
