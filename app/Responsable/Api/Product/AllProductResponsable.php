<?php

namespace App\Responsable\Api\Product;

use App\Repositories\Product\ProductRepository;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpFoundation\Response;

class AllProductResponsable implements Responsable
{

    private $repositories;
    /**
     * Create a new class instance.
     */
    public function __construct(ProductRepository $productRepository)
    {
        //
        $this->repositories = (object) [
            'productRepository' => $productRepository
        ];
    }

    public function toResponse($request)
    {
        // 
        $allProducts = $this->repositories->productRepository->allProducts($request->input('per_page', $request->input('perPage', 15)), $request->page ?? 1);
        return response()->json($allProducts, Response::HTTP_OK);
    }
}
