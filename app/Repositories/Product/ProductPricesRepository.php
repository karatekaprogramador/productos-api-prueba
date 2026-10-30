<?php

namespace App\Repositories\Product;

use App\Models\Api\Detail\ProductPrice;
use App\Models\Api\Product;
use App\Repositories\Repository;

class ProductPricesRepository extends Repository
{
    /**
     * Create a new class instance.
     */
    protected const RELATIONS = ['divisa', 'product'];

    public function __construct(ProductPrice $model)
    {
        parent::__construct($model, self::RELATIONS);
    }

    public function getListPriceByProductId(int $productId, int $perPage = 15, int $page = 1) 
    {
        Product::findOrFail($productId);

        return $this->model::with(self::RELATIONS)
            ->where('product_id', $productId)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function createNewPriceByProductId(array $data): ProductPrice
    {
        Product::findOrFail($data['product_id']);

        return $this->model::create([
            'product_id' => $data['product_id'],
            'price' => $data['price'],
            'divisa_id' => $data['divisa_id'],
        ]);
    }
}
