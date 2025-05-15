<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Enum\CartStatus;
use App\Repository\AddressRepository;
use App\Repository\CartRepository;
use App\Service\CheckoutService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/checkout')]
final class CheckoutController extends AbstractController
{

    #[Route(name: 'app_frontend_checkout', methods: ['GET'])]
    public function index(CheckoutService $checkoutService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $activeCart = $checkoutService->getActiveCartForUser($user);

        if (!$activeCart) {
            $this->addFlash('error', 'No active cart found.');
            return $this->redirectToRoute('app_frontend_cart');
        }

        return $this->render('frontend/checkout/index.html.twig', [
            'addresses' => $user->getAddresses(),
            'cart' => $activeCart,
        ]);
    }

    #[Route('/place-order', name: 'app_frontend_checkout_place_order', methods: ['POST'])]
    public function placeOrder(
        Request $request,
        CartRepository $cartRepository,
        CheckoutService $checkoutService
    ): Response {

        $addressId = $request->request->get('address_id');

        if (!$addressId) {
            $this->addFlash('danger', 'Please select a shipping address.');
            return $this->redirectToRoute('app_frontend_checkout');
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $address = $checkoutService->validateUserAddress($addressId, $user);
        } catch (\InvalidArgumentException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }
        

        $activeCart = $cartRepository->findOneBy([
            'user' => $user,
            'status' => CartStatus::ACTIVE,
        ]);


        try {
            $checkoutService->validateCartStock($activeCart);
        } catch (\RuntimeException $e) {
            $this->addFlash('danger', $e->getMessage());
            return $this->redirectToRoute('app_frontend_cart');
        }

        $order = $checkoutService->createOrder($user, $activeCart, $address);

        return $this->redirectToRoute('app_payment_start', ['reference' => $order->getReference()]);
    }
}
