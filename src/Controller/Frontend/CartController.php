<?php

namespace App\Controller\Frontend;

use App\Entity\Cart;
use App\Entity\User;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Enum\CartStatus;
use App\Repository\CartRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
final class CartController extends AbstractController
{
    #[Route('/', name: 'app_frontend_cart')]
    public function index(): Response
    {
        return $this->render('frontend/cart/index.html.twig');
    }

    #[Route('/add/{productId}', name: 'app_frontend_cart_add', methods: ['POST'])]
    public function addToCart(int $productId, Request $request, CartService $cartService, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $quantity = (int) $request->request->get('quantity', 1);
        $product = $em->getRepository(Product::class)->find($productId);

        if (!$product) {
            $this->addFlash('error', 'Product not found.');
            return $this->redirectToRoute('app_frontend_product_show', ['productId' => $productId]);
        }

        try {
            $cartService->addProductToCart($user, $product, $quantity);
            $this->addFlash('success', 'Product added to cart.');
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
        }
        return $this->redirectToRoute('app_frontend_cart');
    }

    #[Route('/item/{id}/update', name: 'app_frontend_cart_update', methods: ['POST'])]
    public function update(Request $request, CartItem $item, CartService $cartService): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);
        $cartService->updateItemQuantity($item, $quantity);

        return $this->redirectToRoute('app_frontend_cart');
    }

    #[Route('/item/{id}/delete', name: 'app_frontend_cart_delete', methods: ['POST'])]
    public function delete(Request $request, CartItem $item, CartService $cartService): Response
    {
        $submittedToken = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $item->getId(), $submittedToken)) {
            $cartService->removeItem($item);
            $this->addFlash('success', 'Item removed from cart.');
        }
        return $this->redirectToRoute('app_frontend_cart');
    }
}
