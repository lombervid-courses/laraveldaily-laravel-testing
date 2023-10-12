<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
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

        $response->assertStatus(200);
        $response->assertSee(__('No products found'));
    }

    public function test_homepage_contains_non_empty_table(): void
    {
        $products = Product::create([
            'name' => 'Product 1',
            'price' => 123,
        ]);

        $response = $this->actingAs($this->user)->get('/products');

        $response->assertStatus(200);
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

        $response->assertStatus(200);
        $response->assertViewHas('products', fn($c) => ! $c->contains($lastProduct));
    }

    public function test_admin_can_see_products_create_button(): void
    {
        $response = $this->actingAs($this->admin)->get('/products');

        $response->assertStatus(200);
        $response->assertSee('Add new product');
    }

    public function test_non_admin_cannot_see_products_create_button(): void
    {
        $response = $this->actingAs($this->user)->get('/products');

        $response->assertStatus(200);
        $response->assertDontSee('Add new product');
    }

    public function test_admin_can_access_product_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/products/create');

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_product_create_page(): void
    {
        $response = $this->actingAs($this->user)->get('/products/create');

        $response->assertStatus(403);
    }

    public function test_create_product_successful(): void
    {
        $product = [
            'name' => 'Product 123',
            'price' => 1234,
        ];
        $response = $this->actingAs($this->admin)->post('/products', $product);

        $response->assertStatus(302);
        $response->assertRedirectToRoute('products.index');

        $this->assertDatabaseHas('products', $product);

        $lastProduct = Product::latest()->first();
        $this->assertSame($product['name'], $lastProduct->name);
        $this->assertSame($product['price'], $lastProduct->price);
    }

    public function test_product_edit_contains_correct_values(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->get("/products/{$product->id}/edit");

        $response->assertStatus(200);
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

        $response->assertStatus(302);
        $response->assertInvalid(['name', 'price']);
    }

    public function test_product_delete_successful()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/products/{$product->id}");

        $response->assertStatus(302);
        $response->assertRedirectToRoute('products.index');

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_api_returns_products_list(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);
        $response->assertJson([$product->toArray()]);
    }

    public function test_api_product_store_successful(): void
    {
        $product = [
            'name' => 'Products 1',
            'price' => 123,
        ];

        $response = $this->postJson('/api/products', $product);

        $response->assertStatus(201);
        $response->assertJson($product);
    }

    public function test_api_product_invalid_store_returns_error(): void
    {
        $product = [
            'name' => '',
            'price' => 123,
        ];

        $response = $this->postJson('/api/products', $product);

        $response->assertStatus(422);
        $response->assertInvalid('name');
    }

    private function createUser(bool $isAdmin = false): User
    {
        return User::factory()->create([
            'is_admin' => $isAdmin,
        ]);
    }
}
