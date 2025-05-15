<?php

namespace App\Controller\Frontend;

use App\Enum\CartStatus;
use App\Enum\OrderStatus;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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


        return $this->render('frontend/checkout/redirect.html.twig', [
            'stripe_url' => $session->url,
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

    #[Route('/webhook', name: 'app_payment_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        OrderRepository $orderRepository,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        CartRepository $cartRepository
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            return new Response('Invalid signature', 400);
        }
        // log the event for debugging purposes
        $logger->info('Stripe Webhook Event', ['event' => $event]);

        switch ($event->type) {
            case 'checkout.session.completed':
                // Handle successful payment
                $session = $event->data->object;

                // Get sesseion ID to get the order
                $paymentIntentId = $session->id;

                $order = $orderRepository->findOneBy([
                    'paymentIntentId' => $paymentIntentId,
                ]);

                if ($order && $order->getStatus() !== OrderStatus::PAID) {
                    $order->setStatus(OrderStatus::PAID);

                    $cart = $cartRepository->findOneBy([
                        'user' => $order->getUser(),
                        'status' => CartStatus::ACTIVE,
                    ]);

                    if ($cart) {
                        $cart->setStatus(CartStatus::COMPLETED);
                    }

                    // reduce product quantities
                    foreach ($order->getOrderItems() as $item) {
                        $product = $item->getProduct();
                        if ($item->getQuantity() > $product->getQuantity()) {
                            //send email or notification to admin for low stock
                            $logger->error('Not enough product quantity', [
                                'product' => $product->getName(),
                                'requested' => $item->getQuantity(),
                                'available' => $product->getQuantity(),
                            ]);
                        }
                        $product->setQuantity($product->getQuantity() - $item->getQuantity());
                    }

                    $em->flush();
                }


                break;
        }
        return new Response('Webhook received', 200);
    }
}
