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

    public function test_homepage_contains_empty_table(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/products');

        $response->assertStatus(200);
        $response->assertSee(__('No products found'));
    }

    public function test_homepage_contains_non_empty_table(): void
    {
        $user = User::factory()->create();
        $products = Product::create([
            'name' => 'Product 1',
            'price' => 123,
        ]);

        $response = $this->actingAs($user)->get('/products');

        $response->assertStatus(200);
        $response->assertDontSee(__('No products found'));
        $response->assertSee('Product 1');
        $response->assertViewHas('products', fn($c) => $c->contains($products));
    }

    public function test_paginated_products_table_doesnt_contain_11th_record()
    {
        $user = User::factory()->create();
        $products = Product::factory(11)->create();
        $lastProduct = $products->last();

        $response = $this->actingAs($user)->get('/products');

        $response->assertStatus(200);
        $response->assertViewHas('products', fn($c) => ! $c->contains($lastProduct));
    }
}
