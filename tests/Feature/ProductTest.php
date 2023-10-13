<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUser();
        $this->admin = $this->createUser(isAdmin: true);
    }

    public function test_homepage_contains_empty_table(): void
    {
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertSee(__('No products found'));
    }

    public function test_homepage_contains_non_empty_table(): void
    {
        $products = Product::create([
            'name' => 'Product 1',
            'price' => 123,
        ]);

        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertDontSee(__('No products found'));
        $response->assertSee('Product 1');
        $response->assertViewHas('products', fn($c) => $c->contains($products));
    }

    public function test_homepage_contains_table_product(): void
    {
        $product = Product::create([
            'name' => 'table',
            'price' => 123,
        ]);

        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertSeeText($product->name);
    }

    public function test_homepage_contains_products_in_order(): void
    {
        $products = Product::factory(2)->create();

        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertSeeTextInOrder($products->pluck('name')->toArray());
    }

    public function test_paginated_products_table_doesnt_contain_11th_record(): void
    {
        $products = Product::factory(11)->create();
        $lastProduct = $products->last();

        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertViewHas('products', fn($c) => ! $c->contains($lastProduct));
    }

    public function test_admin_can_see_products_create_button(): void
    {
        $response = $this->actingAs($this->admin)->get('/products');

        $response->assertOk();
        $response->assertSee('Add new product');
    }

    public function test_non_admin_cannot_see_products_create_button(): void
    {
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertOk();
        $response->assertDontSee('Add new product');
    }

    public function test_admin_can_access_product_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/products/create');

        $response->assertOk();
    }

    public function test_non_admin_cannot_access_product_create_page(): void
    {
        $response = $this->actingAs($this->user)->get('/products/create');

        $response->assertForbidden();
    }

    public function test_create_product_successful(): void
    {
        $product = [
            'name' => 'Product 123',
            'price' => 1234,
        ];
        $response = $this->actingAs($this->admin)->post('/products', $product);

        $response->assertRedirectToRoute('products.index');

        $this->assertDatabaseHas('products', $product);

        $lastProduct = Product::latest()->first();
        $this->assertSame($product['name'], $lastProduct->name);
        $this->assertSame($product['price'], $lastProduct->price);
    }

    public function test_product_edit_contains_correct_values(): void
    {
        $product = Product::factory()->create();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
        ]);
        $this->assertModelExists($product);

        $response = $this->actingAs($this->admin)->get("/products/{$product->id}/edit");

        $response->assertOk();
        $response->assertSee("value=\"{$product->name}\"", false);
        $response->assertSee("value=\"{$product->price}\"", false);
        $response->assertViewHas('product', $product);
    }

    public function test_product_update_validation_error_redirects_back_to_form()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->put("/products/{$product->id}", [
            'name' => '',
            'price' => '',
        ]);

        $response->assertRedirect();
        $response->assertInvalid(['name', 'price']);
    }

    public function test_product_delete_successful(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/products/{$product->id}");

        $response->assertRedirectToRoute('products.index');

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
        $this->assertModelMissing($product);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_api_returns_products_list(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => $product1->name,
            'price' => $product1->price,
        ]);
    }

    public function test_api_product_store_successful(): void
    {
        $product = [
            'name' => 'Products 1',
            'price' => 123,
        ];

        $response = $this->postJson('/api/products', $product);

        $response->assertCreated();
        $response->assertJson($product);
    }

    public function test_api_product_invalid_store_returns_error(): void
    {
        $product = [
            'name' => '',
            'price' => 123,
        ];

        $response = $this->postJson('/api/products', $product);

        $response->assertUnprocessable();
        $response->assertJsonMissingValidationErrors('price');
        $response->assertInvalid('name');
    }

    public function test_api_product_show_successful(): void
    {
        $productData = [
            'name' => 'Product 1',
            'price' => 123,
        ];
        $product = Product::create($productData);

        $response = $this->getJson('/api/products/' . $product->id);

        $response->assertOk();
        $response->assertJsonPath('data.name', $productData['name']);
        $response->assertJsonMissingPath('data.created_at');
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'price',
            ],
        ]);
    }

    public function test_api_product_update_successful(): void
    {
        $productData = [
            'name' => 'Product 1',
            'price' => 123,
        ];
        $product = Product::create($productData);

        $response = $this->putJson('/api/products/' . $product->id, [
            'name' => 'Product updated',
            'price' => 1234,
        ]);

        $response->assertOk();
        $response->assertJsonMissing($productData);
    }

    public function test_api_product_delete_logged_in_admin(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson('/api/products/' . $product->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_api_product_delete_restricted_by_auth(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson('/api/products/' . $product->id);

        $response->assertUnauthorized();
    }

    public function test_product_service_create_returns_product(): void
    {
        $product = (new ProductService())->create('Test product', 1234);

        $this->assertInstanceOf(Product::class, $product);
    }

    public function test_product_service_create_validation(): void
    {
        try {
            $product = (new ProductService())->create('Too big', 1_234_567);
        } catch (\Throwable $th) {
            $this->assertInstanceOf(NumberFormatException::class, $th);
        }
    }

    public function test_product_download_success(): void
    {
        $response = $this->get('/download');

        $response->assertOk();
        $response->assertHeader('Content-Disposition', 'attachment; filename=product-specification.pdf');
    }

    private function createUser(bool $isAdmin = false): User
    {
        return User::factory()->create([
            'is_admin' => $isAdmin,
        ]);
    }
}
