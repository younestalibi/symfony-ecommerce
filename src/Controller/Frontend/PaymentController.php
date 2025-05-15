<?php

namespace App\Controller\Frontend;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/payment')]
final class PaymentController extends AbstractController
{
    #[Route('/start/{reference}', name: 'app_payment_start', methods: ['GET'])]
    public function start(
        string $reference,
        EntityManagerInterface $em,
        OrderRepository $orderRepository,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $order = $orderRepository->findOneBy([
            'user' => $user,
            'reference' => $reference,
        ]);

        if (!$order) {
            $this->addFlash('danger', 'Invalid order.');
            return $this->redirectToRoute('app_frontend_checkout');
        }

        // Prepare line items
        $lineItems = array_map(function ($orderItem) {
            return [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $orderItem->getPrice() * 100,
                    'product_data' => [
                        'name' => $orderItem->getProductName(),
                        // 'images' => [$orderItem->getProduct()->getImage()],
                    ],
                ],
                'quantity' => $orderItem->getQuantity(),
            ];
        }, $order->getOrderItems()->getValues());

        // Generate absolute URLs
        $successUrl = $urlGenerator->generate(
            'app_payment_success',
            ['reference' => $reference],
            UrlGeneratorInterface::ABSOLUTE_URL
        ) . '?session_id={CHECKOUT_SESSION_ID}';

        $cancelUrl = $urlGenerator->generate(
            'app_payment_cancel',
            ['reference' => $reference],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        // Create Stripe session
        $session = Session::create([
            'customer_email' => $user->getEmail(),
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        // Update order
        $order->setPaymentIntentId($session->id);
        $em->flush();


        return $this->render('frontend/checkout/review.html.twig', [
            'stripe_url' => $session->url,
            'order' => $order,
        ]);
    }

    #[Route('/success/{reference}', name: 'app_payment_success')]
    public function success(): Response
    {
        $this->addFlash('success', 'Payment completed successfully!');
        return $this->redirectToRoute('app_frontend_cart');
    }

    #[Route('/cancel', name: 'app_payment_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('error', 'Payment was canceled.');
        return $this->redirectToRoute('app_frontend_cart');
    }
}
