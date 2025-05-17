<?php

namespace App\Controller\Frontend;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_frontend_home')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();
        return $this->render('frontend/home/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/test-email')]
    public function testEmail(MailerInterface $mailer): Response
    {
        return $this->render('auth/reset_password/email.html.twig', [
            'resetToken' => [
                'token' => 'value',
                'expirationMessageKey' => 'value',
                'expirationMessageData' => 'value',
            ],
        ]);
        try {
            $email = (new Email())
                ->from('hello@example.com')
                ->to('younessetalibi11@gmail.com')
                //->cc('cc@example.com')
                //->bcc('bcc@example.com')
                //->replyTo('fabien@example.com')
                //->priority(Email::PRIORITY_HIGH)
                ->subject('Time for Symfony Mailer!')
                ->text('Sending emails is fun again!')
                ->html('<p>See Twig integratiasdfration!</p>');

            $mailer->send($email);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }

        return new Response('Email sent!');
    }
}
