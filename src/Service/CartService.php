<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\CartStatus;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;

class CartService
{
    public function __construct(
        private EntityManagerInterface $em,
        private CartRepository $cartRepository,
    ) {}

    public function getActiveCart(User $user): ?Cart
    {
        return $this->cartRepository->findOneBy([
            'user' => $user,
            'status' => CartStatus::ACTIVE,
        ]);
    }

    public function addProductToCart(User $user, Product $product,int $quantity): void
    {
        $cart = $this->getActiveCart($user);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $this->em->persist($cart);
        }
        // check if the product is already in the cart
        $existingCartItem = $cart->findItemByProductId($product->getId());

        if (is_null($existingCartItem)) {
            if ($product->getQuantity() < 1) {
                throw new \RuntimeException('Not enough stock available.');
            }
            $cartItem = new CartItem();
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
            $cart->addCartItem($cartItem);
            $this->em->persist($cartItem);
        }

        $this->em->flush();
    }

    public function updateItemQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity < 1) {
            $item->getCart()->removeCartItem($item);
            $this->em->remove($item);
        } else {
            $item->setQuantity($quantity);
        }

        $this->em->flush();
    }

    public function removeItem(CartItem $item): void
    {
        $item->getCart()->removeCartItem($item);
        $this->em->remove($item);
        $this->em->flush();
    }
}
