<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailService
{
    private const FROM_EMAIL = 'admin@admin.com';
    private const FROM_NAME = 'Admin Bot';

    public function __construct(private MailerInterface $mailer) {}

    /**
     * Send a general templated email
     *
     * @param string $toEmail Recipient email
     * @param string $subject Email subject
     * @param string $templatePath Path to the Twig template (e.g. 'email/order_confirmation.html.twig')
     * @param array $context Context for the template
     */
    public function sendEmail(
        string $toEmail,
        string $subject,
        string $templatePath,
        array $context = []
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to(new Address($toEmail))
            ->subject($subject)
            ->htmlTemplate($templatePath)
            ->context($context);

        $this->mailer->send($email);
    }
}
