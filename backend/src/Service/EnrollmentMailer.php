<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;

final class EnrollmentMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'FRONTEND_URL')]
        private readonly string $frontendUrl,
    ) {
    }

    public function send(User $user): void
    {
        $url = rtrim($this->frontendUrl, '/').'/2fa/enroll?token='.$user->getEnrollmentToken();

        $email = (new TemplatedEmail())
            ->from('noreply@example.com')
            ->to((string) $user->getEmail())
            ->subject('Set up two-factor authentication')
            ->htmlTemplate('emails/enroll.html.twig')
            ->context(['url' => $url]);

        $this->logger->info('2FA enrollment email sent', [
            'to' => $user->getEmail(),
            'url' => $url,
        ]);

        $this->mailer->send($email);
    }
}
