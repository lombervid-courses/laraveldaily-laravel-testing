<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(Product::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        return response()->json(
            Product::create($request->validated()),
            Response::HTTP_CREATED,
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
