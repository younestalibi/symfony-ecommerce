<?php

namespace App\Controller\Frontend;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Enum\CartStatus;
use App\Repository\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
final class CartController extends AbstractController
{
    #[Route('/', name: 'app_frontend_cart')]
    public function index(CartRepository $cartRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $activeCart = $cartRepository->findOneBy([
            'user' => $user,
            'status' => CartStatus::ACTIVE,
        ]);

        return $this->render('frontend/cart/index.html.twig', [
            'cart' => $activeCart,
        ]);
    }

    #[Route('/add/{productId}', name: 'app_frontend_cart_add', methods: ['POST'])]
    public function addToCart(int $productId, EntityManagerInterface $em, CartRepository $cartRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $product = $em->getRepository(Product::class)->find($productId);

        if (!$product) {
            $this->addFlash('error', 'Product not found.');
            return $this->redirectToRoute('app_frontend_product_show', ['productId' => $productId]);
        }

        // Check if the user has a cart
        /** @var User $user */
        $user = $this->getUser();
        $cart = $cartRepository->findOneBy([
            'user' => $user,
            'status' => CartStatus::ACTIVE,
        ]);;
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $em->persist($cart);
            $em->flush();
        }

        // Check if the product already exists in the cart        
        $cartItem = $cart->findItemByProductId($productId);

        // If the item doesn't exists, create a new cart item
        if (is_null($cartItem)) {
            if ($product->getQuantity() < 1) {
                $this->addFlash('error', 'Not enough stock available.');
                return $this->redirectToRoute('app_frontend_product_show', ['productId' => $productId]);
            }
            $cartItem = new CartItem();
            $cartItem->setProduct($product);
            $cartItem->setQuantity(1);
            $cart->addCartItem($cartItem);  // Add the cart item to the cart
            $em->persist($cartItem);  // Persist the new cart item
        }

        // Persist changes and return to the cart page
        $em->flush();

        return $this->redirectToRoute('app_frontend_cart');
    }

    #[Route('/item/{id}/update', name: 'app_frontend_cart_update', methods: ['POST'])]
    public function update(Request $request, CartItem $item, EntityManagerInterface $em): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);

        if ($quantity < 1) {
            $item->getCart()->removeCartItem($item);
        } else {
            $item->setQuantity($quantity);
        }

        $em->flush();

        return $this->redirectToRoute('app_frontend_cart');
    }

    #[Route('/item/{id}/delete', name: 'app_frontend_cart_delete', methods: ['POST'])]
    public function delete(Request $request, CartItem $item, EntityManagerInterface $em): Response
    {
        $submittedToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $item->getId(), $submittedToken)) {
            $item->getCart()->removeCartItem($item);
            $em->remove($item);
            $em->flush();
            $this->addFlash('success', 'Item removed from cart.');
        }
        return $this->redirectToRoute('app_frontend_cart');
    }
}
