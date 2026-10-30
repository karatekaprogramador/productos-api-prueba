<?php

namespace Tests\Feature;

use App\Models\Api\Currency\Divisas;
use App\Models\Api\Detail\ProductPrice;
use App\Models\Api\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ImprovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_costs_are_saved_updated_and_listed_and_any_authenticated_user_can_list(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['products.create', 'products.update'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $editor = User::factory()->create();
        $editor->givePermissionTo(['products.create', 'products.update']);
        $currency = Divisas::create(['name' => 'Dollar', 'symbol' => '$', 'exchange_rate' => 1]);
        $payload = ['name' => 'Keyboard', 'description' => 'USB', 'price' => 25.50, 'tax_cost' => 2.50, 'manufacturing_cost' => 10.25, 'divisa_id' => $currency->id];

        $id = $this->actingAs($editor)->postJson('/api/products', $payload)->assertCreated()->json('product.id');
        $this->assertDatabaseHas('products', ['id' => $id, 'tax_cost' => 2.50, 'manufacturing_cost' => 10.25]);
        $this->actingAs($editor)->putJson("/api/products/$id", ['tax_cost' => 3.50, 'manufacturing_cost' => 11.25])->assertOk();
        $this->assertDatabaseHas('products', ['id' => $id, 'tax_cost' => 3.50, 'manufacturing_cost' => 11.25]);
        $this->actingAs(User::factory()->create())->getJson('/api/products?per_page=1')
            ->assertOk()->assertJsonPath('data.0.id', $id)->assertJsonStructure(['data' => [['tax_cost', 'manufacturing_cost']]]);
        $this->actingAs($editor)->postJson('/api/products', array_merge($payload, ['price' => 1.234]))->assertUnprocessable();
    }

    public function test_soft_deleted_product_preserves_prices_and_is_inaccessible(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['products.delete', 'products.create', 'products.view', 'products.view.price'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $user = User::factory()->create();
        $user->givePermissionTo(['products.delete', 'products.create', 'products.view', 'products.view.price']);
        $currency = Divisas::create(['name' => 'Dollar', 'symbol' => '$', 'exchange_rate' => 1]);
        $product = Product::create(['name' => 'Product', 'description' => 'Description', 'price' => 10, 'divisa_id' => $currency->id]);
        $price = ProductPrice::create(['product_id' => $product->id, 'price' => 10, 'divisa_id' => $currency->id]);

        $this->actingAs($user)->deleteJson("/api/products/$product->id")->assertOk();
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('products_prices', ['id' => $price->id]);
        $this->actingAs($user)->getJson("/api/products/$product->id")->assertNotFound();
        $this->actingAs($user)->getJson("/api/products/$product->id/prices")->assertNotFound();
        $this->actingAs($user)->postJson("/api/products/$product->id/prices", ['price' => 10, 'divisa_id' => $currency->id])->assertNotFound();
        $this->actingAs($user)->getJson('/api/products/999999/prices')->assertNotFound();
    }

    public function test_login_token_can_be_revoked_and_invalid_username_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'person@example.com', 'password' => 'password']);
        $token = $this->postJson('/api/login', ['email_username' => $user->email, 'password' => 'password'])
            ->assertOk()->json('token');
        $this->withToken($token)->postJson('/api/logout')->assertOk();
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/products')->assertUnauthorized();
        $this->postJson('/api/login', ['email_username' => 'username', 'password' => 'password'])->assertUnprocessable();
    }
}
