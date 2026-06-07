<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(Request $request, string $slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        $request->validate([
            'id_product_order' => 'required|exists:product_orders,id_product_order',
            'rating' => 'required|integer|min:1|max:5',
            'judul' => 'nullable|string|max:255',
            'komentar' => 'nullable|string|max:2000',
            'foto' => 'nullable|array|max:3',
            'foto.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Order milik user & sudah delivered/completed
        $order = ProductOrder::where('id_product_order', $request->id_product_order)
            ->where('id_user', auth()->id())
            ->whereIn('status', ['delivered', 'completed'])
            ->firstOrFail();

        // Produk harus ada di order itu
        abort_unless($order->items()->where('id_product', $product->id_product)->exists(), 403, 'Produk tidak ada di pesanan ini.');

        $already = ProductReview::where('id_user', auth()->id())
            ->where('id_product', $product->id_product)
            ->where('id_product_order', $order->id_product_order)
            ->exists();
        if ($already) {
            return back()->with('error', 'Kamu sudah me-review produk ini.');
        }

        $fotoUrls = [];
        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $foto) {
                $fotoUrls[] = $foto->store('reviews', 'public');
            }
        }

        ProductReview::create([
            'id_user' => auth()->id(),
            'id_product' => $product->id_product,
            'id_product_order' => $order->id_product_order,
            'rating' => $request->rating,
            'judul' => $request->judul,
            'komentar' => $request->komentar,
            'foto_urls' => $fotoUrls ?: null,
            'is_verified_purchase' => true,
        ]);

        // Update agregat rating produk
        $product->update([
            'rating' => round((float) ProductReview::where('id_product', $product->id_product)->avg('rating'), 2),
            'total_review' => ProductReview::where('id_product', $product->id_product)->count(),
        ]);

        return back()->with('success', 'Review berhasil dikirim!');
    }
}
