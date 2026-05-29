<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCartWithItems($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => new CartResource($cart),
            ],
        ]);
    }

    public function store(AddCartItemRequest $request): JsonResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $quantity = $request->integer('quantity', 1);

        $cart = $this->cartService->addItem($request->user(), $product, $quantity);

        return response()->json([
            'success' => true,
            'message' => 'Producto agregado al carrito.',
            'data' => [
                'cart' => new CartResource($cart),
            ],
        ], 201);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->cartService->updateItem(
            $request->user(),
            $cartItem,
            $request->integer('quantity')
        );

        return response()->json([
            'success' => true,
            'message' => 'Carrito actualizado.',
            'data' => [
                'cart' => new CartResource($cart),
            ],
        ]);
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->cartService->removeItem($request->user(), $cartItem);

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado del carrito.',
            'data' => [
                'cart' => new CartResource($cart),
            ],
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->clear($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Carrito vaciado.',
            'data' => [
                'cart' => new CartResource($cart),
            ],
        ]);
    }
}
