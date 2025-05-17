<?php

namespace App\Controller\Frontend;

use App\Repository\ProductRepository;
use App\Service\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
    public function testEmail(MailService $mailService): Response
    {
        $mailService->sendEmail(
            'younessetalibi11@gmail.com',
            'Your Invoice',
            'email/test.html.twig',
            ['invoiceId' => 456, 'amount' => 99.99]
        );
        return new Response('Email sent!');
    }
}
