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

    private const ADMIN_EMAIL = 'younessetalibi11@gmail.com';

    public function __construct(
        private OrderRepository $orderRepository,
        private CartRepository $cartRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private UrlGeneratorInterface $urlGenerator,
        private CurrencyContext $currency,
        private MailService $mailService,
    ) {}

    public function createStripeSession(User $user, Order $order, string $successRoute, string $cancelRoute): Session
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $lineItems = array_map(function ($orderItem) {
            return [
                'price_data' => [
                    'currency' => $this->currency->getCurrency(),
                    'unit_amount' => $orderItem->getPrice() * 100,
                    'product_data' => [
                        'name' => $orderItem->getProductName(),
                    ],
                ],
                'quantity' => $orderItem->getQuantity(),
            ];
        }, $order->getOrderItems()->getValues());

        $successUrl = $this->urlGenerator->generate(
            $successRoute,
            ['reference' => $order->getReference()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

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

        $this->logger->info('Stripe Webhook Event', ['event' => $event->type]);

        // Handle successful payment
        if ($event->type === 'checkout.session.completed') {
            /** @var Session $session */
            $session = $event->data->object;

            $order = $this->orderRepository->findOneBy(['paymentIntentId' => $session->id]);

            $this->logger->info('Stripe Webhook Event order', ['status' => $order->getStatus(), 'session' => $session->id, 'order' => (array) $order]);


            if (!$order || $order->getStatus() === OrderStatus::PAID) {
                return;
            }

            $order->setStatus(OrderStatus::PAID);

            $this->mailService->sendEmail(
                (string)self::ADMIN_EMAIL,
                'Your Have A New Order',
                'email/admin_new_order.html.twig',
                ['order' => $order]
            );

            if ($order->getUser()) {
                $this->mailService->sendEmail(
                    (string)$order->getUser()->getEmail(),
                    'Your Payment Received for Your Order!',
                    'email/user_order_success.html.twig',
                    ['order' => $order]
                );
            }


            $cart = $this->cartRepository->findOneBy([
                'user' => $order->getUser(),
                'status' => CartStatus::ACTIVE,
            ]);

            $this->logger->info('Stripe cart', ['cart' => $cart]);
            $this->logger->info('Stripe order', ['order' => $order->getUser()]);

            if ($cart) {
                $cart->setStatus(CartStatus::COMPLETED);
            }

            // reduce product quantities and notify admin if any product is too low to satisfy order
            $lowStockItems = [];
            foreach ($order->getOrderItems() as $item) {
                $product = $item->getProduct();
                $available = $product->getQuantity();
                $requested = $item->getQuantity();

                if ($requested > $available) {
                    $lowStockItems[] = [
                        'product' => $product,
                        'requested' => $requested,
                        'available' => $available,
                    ];

                    $this->logger->error('Not enough product quantity', [
                        'product' => $product->getName(),
                        'requested' => $requested,
                        'available' => $available,
                    ]);
                }

                $product->setQuantity(max(0, $available - $requested));
            }

            if (!empty($lowStockItems)) {
                $this->mailService->sendEmail(
                    self::ADMIN_EMAIL,
                    '⚠ Low Stock Warning – Multiple Products',
                    'email/admin_stock_alert.html.twig',
                    ['lowStockItems' => $lowStockItems]
                );
            }


            $this->em->flush();
        }
    }
}
