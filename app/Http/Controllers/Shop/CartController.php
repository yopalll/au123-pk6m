<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $items = Cart::where('id_user', auth()->id())
            ->with('product.primaryImage')->get()
            ->filter(fn ($i) => $i->product !== null);

        $subtotal = $items->sum(fn ($i) => $i->product->harga * $i->qty);
        $threshold = (int) config('ongkir.free_ongkir_threshold', 100000);

        return view('shop.cart', compact('items', 'subtotal', 'threshold'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'id_product' => 'required|exists:products,id_product',
            'qty' => 'nullable|integer|min:1|max:99',
        ]);

        $qty = (int) ($request->qty ?? 1);
        $cart = Cart::where('id_user', auth()->id())->where('id_product', $request->id_product)->first();

        if ($cart) {
            $cart->increment('qty', $qty);
        } else {
            Cart::create(['id_user' => auth()->id(), 'id_product' => $request->id_product, 'qty' => $qty]);
        }

        $count = (int) Cart::where('id_user', auth()->id())->sum('qty');

        if ($request->wantsJson()) {
            return response()->json(['cart_count' => $count, 'message' => 'Ditambahkan ke keranjang']);
        }

        return back()->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'id_cart' => 'required|integer',
            'qty' => 'required|integer|min:1|max:99',
        ]);

        Cart::where('id_cart', $request->id_cart)
            ->where('id_user', auth()->id())
            ->update(['qty' => $request->qty]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }

    public function remove(int $id)
    {
        Cart::where('id_cart', $id)->where('id_user', auth()->id())->delete();

        return back()->with('success', 'Produk dihapus dari keranjang.');
    }
}
