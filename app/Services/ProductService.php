<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Brick\Math\Exception\NumberFormatException;

class ProductService
{
    public function create(string $name, float|int $price): Product
    {
        if ($price > 1_000_000) {
            throw new NumberFormatException('Price to big');
        }

        return Product::create([
            'name' => $name,
            'price' => $price,
        ]);
    }
}
