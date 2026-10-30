<?php

namespace App\Repositories\Product;

use App\Models\Api\Product;
use App\Repositories\Repository;

class ProductRepository extends Repository
{


    protected const RELATIONS = ['divisa'];
    /**
     * Create a new class instance.
     */
    public function __construct(Product $model)
    {
        parent::__construct($model, self::RELATIONS);
    }


    public function allProducts(int $perPage = 15, int $page = 1)
    {
        return $this->model::with(self::RELATIONS)
            ->select(['id', 'name', 'description', 'price', 'tax_cost', 'manufacturing_cost', 'divisa_id'])
            ->paginate($perPage, ['*'], 'page', $page);
    }


    public function getProductById(int $id): Product
    {
        return $this->model::with(self::RELATIONS)->findOrFail($id);
    }


    public function updateProduct(int $id, array $data): Product
    {
        $product = $this->model::findOrFail($id);

        $product->update([
            'name' => $data['name'] ?? $product->name,
            'description' => $data['description'] ?? $product->description,
            'price' => $data['price'] ?? $product->price,
            'tax_cost' => $data['tax_cost'] ?? $product->tax_cost,
            'manufacturing_cost' => $data['manufacturing_cost'] ?? $product->manufacturing_cost,
            'divisa_id' => $data['divisa_id'] ?? $product->divisa_id,
        ]);

        return $product->fresh(self::RELATIONS);
    }


    public function deleteProduct(int $id): bool
    {
        $product = $this->model::findOrFail($id);

        return $product->delete();
    }

    public function createNewProduct(array $data): Product
    {
        return $this->model::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'price' => $data['price'],
            'tax_cost' => $data['tax_cost'] ?? 0,
            'manufacturing_cost' => $data['manufacturing_cost'] ?? 0,
            'divisa_id' => $data['divisa_id'],
        ]);
    }
}
