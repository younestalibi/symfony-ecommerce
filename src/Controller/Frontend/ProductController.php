<?php

namespace App\Controller\Frontend;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/product', name: 'app_frontend_product')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $filters = [
            'availability' => $request->query->get('availability', null),  // e.g. 'in_stock', 'pre_order', 'out_of_stock'
            'price_from' => $request->query->get('price_from', null),
            'price_to' => $request->query->get('price_to', null),
        ];
    
        $sort = $request->query->get('sort', null); // e.g. 'price_desc'
        $search = $request->query->get('q', null);

        $products = $productRepository->findByFiltersAndSort($filters, $sort,$search);
    
        return $this->render('frontend/product/index.html.twig', [
            'products' => $products,
            'filters' => $filters,
            'sort' => $sort,
            'search' => $search,
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
