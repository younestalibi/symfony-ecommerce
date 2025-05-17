<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class MailService
{

    public function __construct(private MailerInterface $mailer, private string $adminEmail, private string $adminName)
    {
        $this->adminEmail = $adminEmail ?: 'younessetalibi11@gmail.com';
        $this->adminName = $adminName ?: 'Younes';
    }

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
            ->from(new Address($this->adminEmail, $this->adminName))
            ->to(new Address($toEmail))
            ->subject($subject)
            ->htmlTemplate($templatePath)
            ->context($context);

        $this->mailer->send($email);
    }
}
