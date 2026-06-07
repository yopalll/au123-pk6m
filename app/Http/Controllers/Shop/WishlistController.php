<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index()
    {
        $items = Wishlist::where('id_user', auth()->id())
            ->with('product.primaryImage')->latest()->get();

        return view('shop.wishlist', compact('items'));
    }

    public function toggle(Request $request)
    {
        $request->validate(['id_product' => 'required|exists:products,id_product']);

        $existing = Wishlist::where('id_user', auth()->id())
            ->where('id_product', $request->id_product)->first();

        if ($existing) {
            $existing->delete();
            $wishlisted = false;
        } else {
            Wishlist::create(['id_user' => auth()->id(), 'id_product' => $request->id_product]);
            $wishlisted = true;
        }

        if ($request->wantsJson()) {
            return response()->json(['wishlisted' => $wishlisted]);
        }

        return back()->with('success', $wishlisted ? 'Ditambahkan ke wishlist.' : 'Dihapus dari wishlist.');
    }

    /**
     * Wishlist publik (read-only) untuk dibagikan via link.
     */
    public function share(User $user)
    {
        $items = Wishlist::where('id_user', $user->id_user)
            ->with('product.primaryImage')->latest()->get();

        return view('shop.wishlist-share', compact('items', 'user'));
    }
}
