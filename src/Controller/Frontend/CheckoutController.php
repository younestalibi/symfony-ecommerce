<?php

namespace App\Controller\Frontend;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Enum\CartStatus;
use App\Repository\AddressRepository;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CheckoutController extends AbstractController
{

    #[Route('/checkout', name: 'app_frontend_checkout', methods: ['GET'])]
    public function index(CartRepository $cartRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $activeCart = $cartRepository->findOneBy([
            'user' => $user,
            'status' => CartStatus::Active,
        ]);

        if (!$activeCart) {
            $this->addFlash('error', 'No active cart found.');
            return $this->redirectToRoute('app_frontend_cart');
        }

        return $this->render('frontend/checkout/index.html.twig', [
            'addresses' => $user->getAddresses(),
            'cart' => $activeCart,
        ]);
    }

    #[Route('/checkout/place-order', name: 'app_frontend_checkout_place_order', methods: ['POST'])]
    public function placeOrder(
        Request $request,
        AddressRepository $addressRepository,
        EntityManagerInterface $em,
        CartRepository $cartRepository
    ): Response {
        $user = $this->getUser();
        $addressId = $request->request->get('address_id');

        if (!$addressId) {
            $this->addFlash('danger', 'Please select a shipping address.');
            return $this->redirectToRoute('app_frontend_checkout');
        }

        $address = $addressRepository->find($addressId);

        /** @var User $user */
        $user = $this->getUser();

        // Ensure users can only edit their own address
        if (!$address || $address->getUser() !== $user) {
            throw $this->createAccessDeniedException('Invalid address selected.');
        }

        $activeCart = $cartRepository->findOneBy([
            'user' => $user,
            'status' => CartStatus::Active,
        ]);
        $cartItems = $activeCart->getCartItems();

        if (count($cartItems) === 0) {
            $this->addFlash('warning', 'Your cart is empty.');
            return $this->redirectToRoute('app_frontend_cart');
        }
        // check if quantity in stock is enough
        foreach ($cartItems as $cartItem) {
            if ($cartItem->getQuantity() > $cartItem->getProduct()->getQuantity()) {
                $this->addFlash('danger', sprintf(
                    'Not enough stock for product %s. Available: %d',
                    $cartItem->getProduct()->getName(),
                    $cartItem->getProduct()->getQuantity()
                ));
                return $this->redirectToRoute('app_frontend_cart');
            }
        }

        $order = new Order();
        $order->setUser($user);
        $order->setShippingLine1($address->getLine1());
        $order->setShippingLine2($address->getLine2());
        $order->setShippingCity($address->getCity());
        $order->setShippingCountry($address->getCountry());
        $order->setShippingZipCode($address->getZipCode());
        $reference = 'ORD-' . time() . '-' . strtoupper(bin2hex(random_bytes(3))); // Example: ORD-1684160401-5F3A9C        
        $order->setReference($reference);

        $total = 0;
        foreach ($cartItems as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setProduct($cartItem->getProduct());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setPrice($cartItem->getProduct()->getPrice());
            $orderItem->setProductName($cartItem->getProduct()->getName());
            $orderItem->setProductOrder($order); //associate order item with order
            $total += $cartItem->getQuantity() * $cartItem->getProduct()->getPrice();
            $em->persist($orderItem);
        }

        $order->setTotal($total);

        $em->persist($order);
        $em->flush();

        $this->addFlash('success', 'Order placed successfully!');
        return $this->redirectToRoute('app_frontend_order', ['id' => $order->getId()]);
    }
}
