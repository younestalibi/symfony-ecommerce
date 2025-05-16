<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Enum\CartStatus;
use App\Enum\Currency;
use App\Repository\AddressRepository;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;

class CheckoutService
{
    public function __construct(
        private CartRepository $cartRepository,
        private AddressRepository $addressRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function getActiveCartForUser(User $user): ?object
    {
        return $this->cartRepository->findOneBy([
            'user' => $user,
            'status' => CartStatus::ACTIVE,
        ]);
    }

    /**
     * Validate if the address belongs to the user.
     */
    public function validateUserAddress(int $addressId, User $user): ?object
    {
        // Ensure address exist and users can only select their own address
        $address = $this->addressRepository->find($addressId);
        if (!$address || $address->getUser() !== $user) {
            throw new \InvalidArgumentException('Invalid address selected.');
        }
        return $address;
    }

    /**
     * check if the cart is empty and validate stock for each item.
     * Throws exception if cart is empty or any product is out of stock.
     */
    public function validateCartStock(object $cart): void
    {
        $cartItems = $cart->getCartItems();

        if (count($cartItems) === 0) {
            throw new \RuntimeException('Your cart is empty.');
        }

        foreach ($cartItems as $cartItem) {
            if ($cartItem->getQuantity() > $cartItem->getProduct()->getQuantity()) {
                $product = $cartItem->getProduct();
                throw new \RuntimeException(sprintf(
                    'Not enough stock for product %s. Available: %d',
                    $product->getName(),
                    $product->getQuantity()
                ));
            }
        }
    }

    /**
     * Creates an order from the active cart and shipping address.
     */
    public function createOrder(User $user, object $cart, object $address): Order
    {
        $order = new Order();
        $order->setUser($user);
        $order->setShippingLine1($address->getLine1());
        $order->setShippingLine2($address->getLine2());
        $order->setShippingCity($address->getCity());
        $order->setShippingCountry($address->getCountry());
        $order->setShippingZipCode($address->getZipCode());

        $order->setReference($this->generateReference());
        $order->setCurrency(Currency::USD);

        $total = 0;
        foreach ($cart->getCartItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setProduct($cartItem->getProduct());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setPrice($cartItem->getProduct()->getPrice());
            $orderItem->setProductName($cartItem->getProduct()->getName());
            $orderItem->setProductOrder($order);

            $this->entityManager->persist($orderItem);

            $total += $cartItem->getQuantity() * $cartItem->getProduct()->getPrice();
        }

        $order->setTotal($total);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }

    private function generateReference(): string
    {
        try {
            return 'ORD-' . time() . '-' . strtoupper(bin2hex(random_bytes(3)));
        } catch (\Exception $e) {
            return 'ORD-' . time() . '-' . bin2hex(random_int(100, 999));
        }
    }
}
