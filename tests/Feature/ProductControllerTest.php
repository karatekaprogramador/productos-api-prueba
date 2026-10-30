<?php

namespace Tests\Feature;

use App\Models\Api\Currency\Divisas;
use App\Models\Api\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $viewerUser;
    private User $unauthorizedUser;
    private Divisas $divisa;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'products.view',
            'products.view.price',
            'products.create',
            'products.update',
            'products.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        $adminRole->syncPermissions($permissions);
        $viewerRole->syncPermissions(['products.view', 'products.view.price']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);

        $this->viewerUser = User::factory()->create();
        $this->viewerUser->assignRole($viewerRole);

        $this->unauthorizedUser = User::factory()->create();

        $this->divisa = Divisas::create([
            'name' => 'Dolar',
            'symbol' => '$',
            'exchange_rate' => 1,
        ]);
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 100.00,
            'divisa_id' => $this->divisa->id,
        ], $overrides));
    }

    // ==================== INDEX ====================

    public function test_index_returns_paginated_products(): void
    {
        $this->createProduct(['name' => 'Product 1']);
        $this->createProduct(['name' => 'Product 2']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/products?page=1&perPage=15');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertStatus(401);
    }

    // ==================== SHOW ====================

    public function test_show_returns_product_by_id(): void
    {
        $product = $this->createProduct();

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['product'])
            ->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('product.name', $product->name);
    }

    public function test_show_returns_404_for_nonexistent_product(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/products/999');

        $response->assertStatus(404);
    }

    public function test_show_forbidden_without_view_permission(): void
    {
        $product = $this->createProduct();

        $response = $this->actingAs($this->unauthorizedUser)
            ->getJson("/api/products/{$product->id}");

        $response->assertStatus(403);
    }

    // ==================== STORE ====================

    public function test_store_creates_product(): void
    {
        $payload = [
            'name' => 'New Product',
            'description' => 'New Description',
            'price' => 50.00,
            'divisa_id' => $this->divisa->id,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/products', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Product created successfully.')
            ->assertJsonPath('product.name', 'New Product');

        $this->assertDatabaseHas('products', ['name' => 'New Product']);
    }

    public function test_store_validation_fails_with_missing_fields(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description', 'price', 'divisa_id']);
    }

    public function test_store_forbidden_without_create_permission(): void
    {
        $payload = [
            'name' => 'New Product',
            'description' => 'New Description',
            'price' => 50.00,
            'divisa_id' => $this->divisa->id,
        ];

        $response = $this->actingAs($this->viewerUser)
            ->postJson('/api/products', $payload);

        $response->assertStatus(403);
    }

    // ==================== UPDATE ====================

    public function test_update_modifies_product(): void
    {
        $product = $this->createProduct();

        $payload = [
            'name' => 'Updated Product',
            'price' => 200.00,
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/products/{$product->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Product updated successfully.')
            ->assertJsonPath('product.name', 'Updated Product');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Product']);
    }

    public function test_update_partial_fields(): void
    {
        $product = $this->createProduct(['name' => 'Original Name']);

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/products/{$product->id}", ['description' => 'Updated Desc']);

        $response->assertStatus(200)
            ->assertJsonPath('product.name', 'Original Name')
            ->assertJsonPath('product.description', 'Updated Desc');
    }

    public function test_update_returns_404_for_nonexistent_product(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->putJson('/api/products/999', ['name' => 'Test']);

        $response->assertStatus(404);
    }

    public function test_update_forbidden_without_update_permission(): void
    {
        $product = $this->createProduct();

        $response = $this->actingAs($this->viewerUser)
            ->putJson("/api/products/{$product->id}", ['name' => 'Hacked']);

        $response->assertStatus(403);
    }

    // ==================== DESTROY ====================

    public function test_destroy_deletes_product(): void
    {
        $product = $this->createProduct();

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Product deleted successfully.');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_destroy_returns_404_for_nonexistent_product(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/api/products/999');

        $response->assertStatus(404);
    }

    public function test_destroy_forbidden_without_delete_permission(): void
    {
        $product = $this->createProduct();

        $response = $this->actingAs($this->viewerUser)
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(403);
    }
}
