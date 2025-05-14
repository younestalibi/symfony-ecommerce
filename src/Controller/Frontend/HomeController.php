<?php

namespace App\Controller\Frontend;

use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_frontend_home')]
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        // Get all products and categories
        $products = $productRepository->findAll();
        $categories = $categoryRepository->findAll();
        return $this->render('frontend/home/index.html.twig', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
