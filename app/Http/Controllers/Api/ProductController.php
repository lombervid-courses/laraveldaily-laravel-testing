<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only('destroy');
    }

    public function index(): ResourceCollection
    {
        return ProductResource::collection(Product::all());
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        return response()->json(
            Product::create($request->validated()),
            Response::HTTP_CREATED,
        );
    }

    public function show(Product $product): JsonResource
    {
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResource
    {
        $product->update($request->validated());

        return new ProductResource($product);
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
