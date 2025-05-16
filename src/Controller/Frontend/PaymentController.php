<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Repository\OrderRepository;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/payment')]
final class PaymentController extends AbstractController
{
    #[Route('/start/{reference}', name: 'app_payment_start', methods: ['GET'])]
    public function start(string $reference, PaymentService $paymentService, OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $order = $orderRepository->findOneBy(['user' => $user, 'reference' => $reference]);

        if (!$order) {
            $this->addFlash('danger', 'Invalid order.');
            return $this->redirectToRoute('app_frontend_checkout');
        }

        $session = $paymentService->createStripeSession($user, $order, 'app_payment_success', 'app_payment_cancel');

        return $this->render('frontend/checkout/redirect.html.twig', [
            'stripe_url' => $session->url,
        ]);
    }


    #[Route('/success/{reference}', name: 'app_payment_success')]
    public function success($reference): Response
    {
        return $this->render('frontend/checkout/success.html.twig', [
            'reference' => $reference,
        ]);
    }

    #[Route('/cancel', name: 'app_payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('frontend/checkout/fail.html.twig');
    }

    #[Route('/webhook', name: 'app_payment_webhook', methods: ['POST'])]
    public function webhook(Request $request, PaymentService $paymentService): Response
    {
        try {
            $paymentService->handleStripeWebhook(
                $request->getContent(),
                $request->headers->get('stripe-signature')
            );
        } catch (\RuntimeException $e) {
            return new Response($e->getMessage(), 400);
        }

        return new Response('Webhook received', 200);
    }
}
