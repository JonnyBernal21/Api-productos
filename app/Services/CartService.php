<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function getOrCreateActiveCart(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function getCartWithItems(User $user): Cart
    {
        $cart = $this->getOrCreateActiveCart($user);

        return $cart->load(['items.product.category']);
    }

    public function addItem(User $user, Product $product, int $quantity): Cart
    {
        if ($product->stock < 1) {
            throw ValidationException::withMessages([
                'product_id' => ['El producto no tiene stock disponible.'],
            ]);
        }

        if ($quantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Solo hay {$product->stock} unidades disponibles."],
            ]);
        }

        $cart = $this->getOrCreateActiveCart($user);

        $item = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        $newQuantity = ($item?->quantity ?? 0) + $quantity;

        if ($newQuantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Solo puedes agregar hasta {$product->stock} unidades de este producto."],
            ]);
        }

        CartItem::updateOrCreate(
            ['cart_id' => $cart->id, 'product_id' => $product->id],
            [
                'quantity' => $newQuantity,
                'unit_price' => $product->price,
            ]
        );

        return $this->getCartWithItems($user);
    }

    public function updateItem(User $user, CartItem $cartItem, int $quantity): Cart
    {
        $this->ensureCartItemBelongsToUser($user, $cartItem);

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => ['La cantidad debe ser al menos 1.'],
            ]);
        }

        $product = $cartItem->product;

        if ($quantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => ["Solo hay {$product->stock} unidades disponibles."],
            ]);
        }

        $cartItem->update([
            'quantity' => $quantity,
            'unit_price' => $product->price,
        ]);

        return $this->getCartWithItems($user);
    }

    public function removeItem(User $user, CartItem $cartItem): Cart
    {
        $this->ensureCartItemBelongsToUser($user, $cartItem);

        $cartItem->delete();

        return $this->getCartWithItems($user);
    }

    public function clear(User $user): Cart
    {
        $cart = $this->getOrCreateActiveCart($user);
        $cart->items()->delete();

        return $this->getCartWithItems($user);
    }

    public function clearCart(Cart $cart): void
    {
        $cart->items()->delete();
    }

    protected function ensureCartItemBelongsToUser(User $user, CartItem $cartItem): void
    {
        if ($cartItem->cart?->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'cart_item' => ['El producto no pertenece a tu carrito.'],
            ]);
        }
    }
}
