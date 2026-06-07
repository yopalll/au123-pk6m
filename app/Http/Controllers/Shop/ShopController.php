<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCollection;
use App\Models\ProductReview;
use App\Models\Wishlist;

class ShopController extends Controller
{
    public function index()
    {
        $featuredProducts = Product::where('is_featured', true)->where('status', 'active')
            ->with('primaryImage')->latest()->limit(8)->get();
        $categories = ProductCategory::whereNull('parent_id')->orderBy('sort_order')->orderBy('nama')->get();
        $collections = ProductCollection::orderBy('sort_order')->orderBy('nama')->get();
        $latestProducts = Product::where('status', 'active')->with('primaryImage')->latest()->limit(12)->get();

        return view('shop.index', compact('featuredProducts', 'categories', 'collections', 'latestProducts'));
    }

    public function kategori(string $slug)
    {
        $category = ProductCategory::where('slug', $slug)->firstOrFail();

        $query = Product::where('id_product_category', $category->id_product_category)
            ->where('status', 'active')->with('primaryImage');

        $this->applyFilters($query);

        $products = $this->applySort($query)->paginate(20)->withQueryString();
        $categories = ProductCategory::orderBy('sort_order')->orderBy('nama')->get();

        return view('shop.kategori', compact('category', 'products', 'categories'));
    }

    public function koleksi(string $slug)
    {
        $collection = ProductCollection::where('slug', $slug)->firstOrFail();

        $query = Product::where('id_collection', $collection->id_collection)
            ->where('status', 'active')->with('primaryImage');

        $this->applyFilters($query);

        $products = $this->applySort($query)->paginate(20)->withQueryString();

        return view('shop.koleksi', compact('collection', 'products'));
    }

    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)->where('status', 'active')
            ->with(['images', 'category', 'collection', 'categories'])
            ->firstOrFail();

        // Review + filter (PRD 4.3.7)
        $reviewQuery = ProductReview::where('id_product', $product->id_product)->with('user');
        if ($f = request('review_filter')) {
            if ($f === 'with_photo') {
                $reviewQuery->whereNotNull('foto_urls');
            } elseif (is_numeric($f)) {
                $reviewQuery->where('rating', (int) $f);
            }
        }
        $reviews = $reviewQuery->latest()->paginate(10)->withQueryString();

        $ratingBreakdown = ProductReview::where('id_product', $product->id_product)
            ->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');

        $related = Product::where('id_product_category', $product->id_product_category)
            ->where('id_product', '!=', $product->id_product)->where('status', 'active')
            ->with('primaryImage')->limit(4)->get();

        $sameCollection = $product->id_collection
            ? Product::where('id_collection', $product->id_collection)
                ->where('id_product', '!=', $product->id_product)->where('status', 'active')
                ->with('primaryImage')->limit(4)->get()
            : collect();

        $isWishlisted = auth()->check() && Wishlist::where('id_user', auth()->id())
            ->where('id_product', $product->id_product)->exists();

        return view('shop.produk-detail', compact(
            'product', 'reviews', 'ratingBreakdown', 'related', 'sameCollection', 'isWishlisted'
        ));
    }

    public function search()
    {
        $q = trim((string) request('q', ''));
        $products = Product::where('status', 'active')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('nama', 'like', "%{$q}%")
                ->orWhere('deskripsi', 'like', "%{$q}%")
                ->orWhere('key_ingredients', 'like', "%{$q}%")))
            ->with('primaryImage')->paginate(20)->withQueryString();

        return view('shop.cari', compact('products', 'q'));
    }

    private function applyFilters($query): void
    {
        if (request('skin_type')) {
            $query->where('skin_type', request('skin_type'));
        }
        if (request('skin_concern')) {
            $query->where('skin_concern', 'like', '%'.request('skin_concern').'%');
        }
        if (request('min_price')) {
            $query->where('harga', '>=', (int) request('min_price'));
        }
        if (request('max_price')) {
            $query->where('harga', '<=', (int) request('max_price'));
        }
    }

    private function applySort($query)
    {
        return match (request('sort', 'terbaru')) {
            'terlaris' => $query->orderByDesc('total_sold'),
            'harga_asc' => $query->orderBy('harga'),
            'harga_desc' => $query->orderByDesc('harga'),
            'rating' => $query->orderByDesc('rating'),
            default => $query->latest(),
        };
    }
}
