<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\CartStatus;
use App\Enum\OrderStatus;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PaymentService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private CartRepository $cartRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function createStripeSession(User $user, Order $order, string $successRoute, string $cancelRoute): Session
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $lineItems = array_map(function ($orderItem) {
            return [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $orderItem->getPrice() * 100,
                    'product_data' => [
                        'name' => $orderItem->getProductName(),
                    ],
                ],
                'quantity' => $orderItem->getQuantity(),
            ];
        }, $order->getOrderItems()->getValues());

        $successUrl = $this->urlGenerator->generate($successRoute, [
            'reference' => $order->getReference(),
        ], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}';

        $cancelUrl = $this->urlGenerator->generate($cancelRoute, [
            'reference' => $order->getReference(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $session = Session::create([
            'customer_email' => $user->getEmail(),
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        $order->setPaymentIntentId($session->id);
        $this->em->flush();

        return $session;
    }

    public function handleStripeWebhook(string $payload, ?string $sigHeader): void
    {
        $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $e) {
            throw new \RuntimeException('Stripe webhook error: ' . $e->getMessage());
        }

        $this->logger->info('Stripe Webhook Event', ['event' => $event]);

        // Handle successful payment
        if ($event->type === 'checkout.session.completed') {
            /** @var Session $session */
            $session = $event->data->object;

            $order = $this->orderRepository->findOneBy([
                'paymentIntentId' => $session->id,
            ]);

            if (!$order || $order->getStatus() === OrderStatus::PAID) {
                return;
            }

            $order->setStatus(OrderStatus::PAID);

            $cart = $this->cartRepository->findOneBy([
                'user' => $order->getUser(),
                'status' => CartStatus::ACTIVE,
            ]);

            if ($cart) {
                $cart->setStatus(CartStatus::COMPLETED);
            }

            // reduce product quantities
            foreach ($order->getOrderItems() as $item) {
                $product = $item->getProduct();
                $available = $product->getQuantity();
                $requested = $item->getQuantity();

                if ($requested > $available) {
                    //send email or notification to admin for low stock
                    $this->logger->error('Not enough product quantity', [
                        'product' => $product->getName(),
                        'requested' => $requested,
                        'available' => $available,
                    ]);
                }

                $product->setQuantity(max(0, $available - $requested));
            }

            $this->em->flush();
        }
    }
}
