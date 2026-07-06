<?php

namespace App\Http\Controllers\Api\Shopping;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Post;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $cart = Cart::with(['items.product.postImages'])->firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'data' => $cart->items->map(function ($item) {
                $product = $item->product;

                return [
                    'id' => $item->id,
                    'product_id' => $product ? $product->id : null,
                    'title' => $product && $product->content ? (strlen($product->content) > 50 ? substr($product->content, 0, 50).'...' : $product->content) : 'Unknown Product',
                    'price' => (float) $item->price,
                    'original_price' => $product ? (float) $product->price : 0,
                    'quantity' => $item->quantity,
                    'image' => $product && $product->postImages->count() > 0 ? $product->postImages->first()->url : null,
                    'slug' => $product ? 'product-'.$product->id : null,
                ];
            }),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $user = $request->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        $product = Post::find($request->post_id);
        $quantity = $request->input('quantity', 1);

        $cartItem = $cart->items()->where('post_id', $product->id)->first();

        if ($cartItem) {
            $cartItem->increment('quantity', $quantity);
        } else {
            $cart->items()->create([
                'post_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price ?? 0,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Item added to cart']);
    }

    public function update(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:cart_items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $item = CartItem::find($request->item_id);
        if ($item->cart->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $item->update(['quantity' => $request->quantity]);

        return response()->json(['success' => true]);
    }

    public function remove(Request $request)
    {
        $request->validate([
            'item_id' => 'required|exists:cart_items,id',
        ]);

        $item = CartItem::find($request->item_id);
        if ($item->cart->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $item->delete();

        return response()->json(['success' => true]);
    }
}
