<?php
// src/Twig/CartExtension.php
namespace App\Twig;

use App\Service\CartService;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CartExtension extends AbstractExtension
{
    private CartService $cartService;
    private Security $security;

    public function __construct(CartService $cartService, Security $security)
    {
        $this->cartService = $cartService;
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('current_cart', [$this, 'getCurrentCart']),
        ];
    }

    public function getCurrentCart()
    {
        $user = $this->security->getUser();
        if (!$user) {
            return null;
        }

        return $this->cartService->getActiveCart($user);
    }
}
