<?php

namespace App\Controller\Frontend;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/order')]
final class OrderController extends AbstractController
{
    #[Route(name: 'app_frontend_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('frontend/order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_frontend_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('frontend/order/show.html.twig', [
            'order' => $order,
        ]);
    }
}
