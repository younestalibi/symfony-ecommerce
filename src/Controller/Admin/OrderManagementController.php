<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Form\OrderForm;
use App\Repository\OrderRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/order/management')]
final class OrderManagementController extends AbstractController
{

    public function __construct(
        private MailService $mailService,
    ) {}

    #[Route(name: 'app_admin_order_management_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('admin/order_management/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }


    #[Route('/{id}', name: 'app_admin_order_management_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/order_management/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/status', name: 'app_admin_order_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, Order $order, EntityManagerInterface $em): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('update_order_status_' . $order->getId(), $submittedToken)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        try {
            $order->setStatus(OrderStatus::from($request->request->get('status')));
            $em->flush();
            $this->addFlash('success', 'Status updated.');
            $this->mailService->sendEmail(
                (string)$order->getUser()->getEmail(),
                'Your Order Status Has Been Updated',
                'email/order_status_update.html.twig',
                ['order' => $order]
            );
        } catch (\ValueError $e) {
            $this->addFlash('error', 'Invalid status.');
        }

        return $this->redirectToRoute('app_admin_order_management_index');
    }
}
