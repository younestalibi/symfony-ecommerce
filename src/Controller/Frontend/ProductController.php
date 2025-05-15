<?php

namespace App\Controller\Frontend;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/product', name: 'app_frontend_product')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('frontend/product/index.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/product/{productId}', name: 'app_frontend_product_show', methods: ['GET'])]
    public function show(int $productId, ProductRepository $productRepository): Response
    {
        $product = $productRepository->find($productId);

        if (!$product) {
            return $this->redirectToRoute('app_frontend_home');
        }
        return $this->render('frontend/product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
