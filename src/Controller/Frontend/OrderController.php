<?php

namespace App\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController extends AbstractController
{
    #[Route('/frontend/order', name: 'app_frontend_order')]
    public function index(): Response
    {
        return $this->render('frontend/order/index.html.twig', [
            'controller_name' => 'OrderController',
        ]);
    }
}
