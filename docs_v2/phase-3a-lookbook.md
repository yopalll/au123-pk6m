# Phase 3A — Modul 2: Lookbook Skincare
## Step 3.1

> **Prerequisite:** Phase 2B selesai (produk e-commerce ada, bisa di-tag di lookbook)  
> **Design Reference:**  
> - `docs_v2/design/k1_lookbook_index_editorial_view/` — halaman index  
> - `docs_v2/design/k1.1_lookbook_detail_midnight_muse/` — detail lookbook  
> - `docs_v2/design/k2_editorial_article_the_art_of_nightly_restoration/` — editorial article  
> - `docs_v2/design/m_k1_lookbook_index/` — mobile index  
> - `docs_v2/design/m_k2_editorial_detail/` — mobile detail  
> **Verifikasi:** Admin bisa buat lookbook, public bisa lihat + klik produk, "Shop This Look" berfungsi

---

## KONTEKS

Route `/lookbook` dan model `Lookbook` dasar sudah ada di V1 (konten basic). V2 akan **override/extend** dengan:
- Layout editorial full-width dengan slide
- Product tags yang ter-link ke halaman shop
- "Shop This Look" — tambah semua produk ke cart

---

## SUB-STEP 3.1.1 — Routes

Update/tambah di `routes/web.php`:

```php
// Lookbook V2 (override V1 routes)
Route::get('/lookbook',             [LookbookController::class, 'index'])->name('lookbook.index');
Route::get('/lookbook/{slug}',      [LookbookController::class, 'show'])->name('lookbook.show');
Route::post('/lookbook/{slug}/shop-all', [LookbookController::class, 'shopAll'])
    ->name('lookbook.shopAll')->middleware('auth');
```

---

## SUB-STEP 3.1.2 — Controller

**File:** `app/Http/Controllers/LookbookController.php` (override V1)

```php
<?php
namespace App\Http\Controllers;

use App\Models\{Lookbook, LookbookSlide, LookbookItem};
use App\Models\Cart;

class LookbookController extends Controller
{
    public function index()
    {
        $lookbooks = Lookbook::where('is_published', true)
                             ->orderByDesc('published_at')
                             ->get();

        // Kelompokkan per tema untuk filter
        $temas = $lookbooks->pluck('tema')->unique()->filter()->values();

        // Latest lookbook sebagai hero
        $featured = $lookbooks->first();

        return view('lookbook.index', compact('lookbooks', 'temas', 'featured'));
    }

    public function show(string $slug)
    {
        $lookbook = Lookbook::where('slug', $slug)
                            ->where('is_published', true)
                            ->with(['slides.items.product.primaryImage'])
                            ->firstOrFail();

        // Increment view count
        $lookbook->increment('view_count');

        return view('lookbook.show', compact('lookbook'));
    }

    public function shopAll(string $slug)
    {
        $lookbook = Lookbook::where('slug', $slug)->with('slides.items.product')->firstOrFail();

        $user = auth()->user();
        $added = 0;

        foreach ($lookbook->slides as $slide) {
            foreach ($slide->items as $item) {
                if ($item->product && $item->product->status === 'active' && $item->product->stok > 0) {
                    $cart = Cart::where('id_user', $user->id_user)
                                ->where('id_product', $item->id_product)->first();
                    if ($cart) {
                        $cart->increment('qty');
                    } else {
                        Cart::create(['id_user' => $user->id_user, 'id_product' => $item->id_product, 'qty' => 1]);
                    }
                    $added++;
                }
            }
        }

        return back()->with('success', "{$added} produk dari lookbook ini ditambahkan ke keranjang.");
    }
}
```

---

## SUB-STEP 3.1.3 — Views

### `resources/views/lookbook/index.blade.php`

Gunakan `docs_v2/design/k1_lookbook_index_editorial_view/code.html` sebagai referensi.

Fitur yang harus ada:
- **Grid editorial** dengan cover lookbook (efek hover scale + overlay)
- **Filter chips** per tema (Morning Routine, Night Care, Anti-Aging, dll.)
- **Hero banner** — lookbook terbaru full-width di atas

```html
@extends('layouts.public')

@section('content')
{{-- Hero: Featured lookbook --}}
@if($featured)
<div class="relative h-[60vh] min-h-80 overflow-hidden">
    <img src="{{ $featured->cover_url }}" alt="{{ $featured->judul }}"
         class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
    <div class="absolute bottom-8 left-8 text-white">
        <p class="text-sm uppercase tracking-widest mb-2">Featured</p>
        <h1 class="text-4xl font-bold font-playfair mb-4">{{ $featured->judul }}</h1>
        <a href="{{ route('lookbook.show', $featured->slug) }}"
           class="btn btn-primary-outline">Lihat Lookbook →</a>
    </div>
</div>
@endif

{{-- Filter Chips --}}
<div class="container mx-auto px-4 py-8">
    <div class="flex gap-3 overflow-x-auto pb-2 mb-8">
        <button class="chip chip-active" data-tema="all">Semua</button>
        @foreach($temas as $tema)
        <button class="chip" data-tema="{{ $tema }}">{{ $tema }}</button>
        @endforeach
    </div>

    {{-- Grid Lookbook --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="lookbookGrid">
        @foreach($lookbooks as $lb)
        <a href="{{ route('lookbook.show', $lb->slug) }}"
           class="group block rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-shadow"
           data-tema="{{ $lb->tema }}">
            <div class="relative overflow-hidden aspect-[3/4]">
                <img src="{{ $lb->cover_url }}" alt="{{ $lb->judul }}"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            </div>
            <div class="p-4">
                <span class="text-xs text-primary uppercase tracking-wider">{{ $lb->tema }}</span>
                <h3 class="font-playfair font-semibold text-lg mt-1">{{ $lb->judul }}</h3>
                <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $lb->deskripsi }}</p>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endsection
```

### `resources/views/lookbook/show.blade.php`

Gunakan `docs_v2/design/k1.1_lookbook_detail_midnight_muse/code.html` sebagai referensi.

Fitur yang harus ada:
- **Carousel/slide** — navigasi antar slide dengan panah + dots
- **Product tags** — titik interaktif di gambar, tap/hover → popup nama produk + harga + CTA
- **"Shop This Look"** — sticky button
- **Share buttons** (WhatsApp, Instagram link)

```html
@extends('layouts.public')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Header --}}
    <div class="px-4 py-8 text-center">
        <span class="text-xs text-primary uppercase tracking-widest">{{ $lookbook->tema }}</span>
        <h1 class="text-4xl font-playfair font-bold mt-2">{{ $lookbook->judul }}</h1>
        <p class="text-gray-500 mt-3 max-w-lg mx-auto">{{ $lookbook->deskripsi }}</p>
    </div>

    {{-- Slides --}}
    <div class="relative" id="slideshow">
        @foreach($lookbook->slides as $i => $slide)
        <div class="slide {{ $i === 0 ? 'block' : 'hidden' }}" data-index="{{ $i }}">

            {{-- Hero Image + Product Tags --}}
            <div class="relative">
                <img src="{{ $slide->image_url }}" alt="{{ $slide->judul }}"
                     class="w-full object-cover max-h-[80vh]">

                {{-- Product Tag Pins --}}
                @foreach($slide->items as $item)
                @if($item->product)
                <div class="absolute product-tag-pin cursor-pointer"
                     style="left: {{ $item->position_x }}%; top: {{ $item->position_y }}%;"
                     data-product="{{ $item->product->id_product }}">
                    {{-- Pulsing dot --}}
                    <span class="w-5 h-5 bg-white rounded-full shadow-lg flex items-center justify-center text-xs font-bold relative">
                        +
                        <span class="absolute w-full h-full rounded-full bg-white animate-ping opacity-40"></span>
                    </span>
                    {{-- Popup (show on hover/tap) --}}
                    <div class="product-tag-popup absolute bottom-8 left-1/2 -translate-x-1/2 bg-white rounded-xl shadow-xl p-3 w-48 hidden z-10">
                        <img src="{{ $item->product->primaryImage?->image_url }}" class="w-full h-24 object-cover rounded-lg mb-2">
                        <p class="font-medium text-sm line-clamp-2">{{ $item->product->nama }}</p>
                        <p class="text-primary font-bold text-sm mt-1">Rp {{ number_format($item->product->harga, 0, ',', '.') }}</p>
                        <div class="flex gap-2 mt-2">
                            @if($item->product->stok > 0)
                            <span class="text-xs text-green-600">● In Stock</span>
                            @else
                            <span class="text-xs text-red-500">● Out of Stock</span>
                            @endif
                        </div>
                        <a href="{{ route('shop.produk.show', $item->product->slug) }}"
                           class="block text-center text-xs btn btn-sm btn-primary mt-2">
                            Lihat Produk
                        </a>
                    </div>
                </div>
                @endif
                @endforeach
            </div>

            {{-- Slide Content --}}
            <div class="px-6 py-8 max-w-2xl mx-auto">
                @if($slide->judul)
                <h2 class="text-2xl font-playfair font-semibold mb-3">{{ $slide->judul }}</h2>
                @endif
                @if($slide->deskripsi)
                <p class="text-gray-600 leading-relaxed">{{ $slide->deskripsi }}</p>
                @endif
                @if($slide->tips)
                <div class="mt-4 bg-amber-50 rounded-xl p-4">
                    <p class="text-sm font-medium text-amber-800 mb-1">💡 Skincare Tips</p>
                    <p class="text-sm text-amber-700">{{ $slide->tips }}</p>
                </div>
                @endif

                {{-- Products in this slide --}}
                @if($slide->items->count())
                <div class="mt-6">
                    <p class="text-sm font-medium text-gray-500 mb-3">Produk di slide ini:</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach($slide->items as $item)
                        @if($item->product)
                        <a href="{{ route('shop.produk.show', $item->product->slug) }}"
                           class="flex items-center gap-2 bg-gray-50 rounded-xl p-2 hover:bg-gray-100 transition">
                            <img src="{{ $item->product->primaryImage?->image_url }}"
                                 class="w-10 h-10 rounded-lg object-cover">
                            <div>
                                <p class="text-xs font-medium line-clamp-1 max-w-[120px]">{{ $item->product->nama }}</p>
                                <p class="text-xs text-primary">Rp {{ number_format($item->product->harga, 0, ',', '.') }}</p>
                            </div>
                        </a>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endforeach

        {{-- Slide Navigation --}}
        @if($lookbook->slides->count() > 1)
        <div class="flex items-center justify-center gap-4 pb-8">
            <button id="prevSlide" class="p-2 rounded-full bg-white shadow-md hover:shadow-lg">←</button>
            <div class="flex gap-2" id="slideDots">
                @foreach($lookbook->slides as $i => $slide)
                <button class="w-2 h-2 rounded-full {{ $i === 0 ? 'bg-primary' : 'bg-gray-300' }}"
                        data-slide="{{ $i }}"></button>
                @endforeach
            </div>
            <button id="nextSlide" class="p-2 rounded-full bg-white shadow-md hover:shadow-lg">→</button>
        </div>
        @endif
    </div>

    {{-- Sticky "Shop This Look" --}}
    <div class="sticky bottom-4 flex justify-center pb-4 px-4">
        <form method="POST" action="{{ route('lookbook.shopAll', $lookbook->slug) }}">
            @csrf
            <button type="submit"
                    class="bg-gray-900 text-white px-8 py-3 rounded-full font-medium shadow-xl hover:bg-gray-800 transition">
                🛍️ Shop This Look
            </button>
        </form>
        {{-- Share --}}
        <div class="ml-3 flex gap-2">
            <a href="https://wa.me/?text={{ urlencode(request()->url()) }}" target="_blank"
               class="p-3 bg-green-500 text-white rounded-full">📱</a>
            <button onclick="navigator.clipboard.writeText('{{ request()->url() }}')"
                    class="p-3 bg-gray-200 rounded-full">🔗</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Slideshow JS
let current = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('#slideDots button');

function goTo(n) {
    slides[current].classList.add('hidden'); slides[current].classList.remove('block');
    dots[current].classList.remove('bg-primary'); dots[current].classList.add('bg-gray-300');
    current = (n + slides.length) % slides.length;
    slides[current].classList.remove('hidden'); slides[current].classList.add('block');
    dots[current].classList.add('bg-primary'); dots[current].classList.remove('bg-gray-300');
}

document.getElementById('nextSlide')?.addEventListener('click', () => goTo(current + 1));
document.getElementById('prevSlide')?.addEventListener('click', () => goTo(current - 1));
dots.forEach((dot, i) => dot.addEventListener('click', () => goTo(i)));

// Product tag pins: toggle popup on click/hover
document.querySelectorAll('.product-tag-pin').forEach(pin => {
    pin.addEventListener('click', (e) => {
        e.stopPropagation();
        const popup = pin.querySelector('.product-tag-popup');
        document.querySelectorAll('.product-tag-popup').forEach(p => p.classList.add('hidden'));
        popup.classList.toggle('hidden');
    });
});
document.addEventListener('click', () => {
    document.querySelectorAll('.product-tag-popup').forEach(p => p.classList.add('hidden'));
});
</script>
@endpush
@endsection
```

---

## SUB-STEP 3.1.4 — Filament Resource (Admin Store)

> 🔴 **Filament v5** (lihat [CATATAN-LINGKUNGAN §5](CATATAN-LINGKUNGAN.md)): `form(Schema $form): Schema`,
> Repeater nested untuk slides+tags. Tiru resource V1 yang ada di `app/Filament/`.

Buat `LookbookResource` di `app/Filament/Store/Resources/LookbookResource.php`:

```php
// CRUD Lookbook + Slides + Product Tags
// Gunakan Filament v3 Forms:
// - TextInput: judul, tema
// - RichEditor: deskripsi
// - FileUpload: cover_url
// - Toggle: is_published
// - Repeater untuk slides:
//   - TextInput: judul, tips
//   - Textarea: deskripsi
//   - FileUpload: image_url
//   - Number: sort_order
//   - Repeater (nested) untuk lookbook_items:
//     - Select: id_product (dari Product)
//     - TextInput (number): position_x, position_y
```

---

## SUB-STEP 3.1.5 — Responsive

Pastikan sesuai PRD Section 14.2 tabel Lookbook:

| Elemen | Mobile | Tablet | Desktop |
|--------|--------|--------|---------|
| Grid index | 1 kolom full-width | 2 kolom | 3 kolom |
| Detail slide | Vertical scroll, gambar full-width | Larger images | Horizontal slideshow |
| Product tags | Tap to reveal popup | Hover + tap | Hover tooltip |
| "Shop This Look" | Sticky bottom button | Sticky bottom | Sidebar button |

---

## VERIFIKASI

```
1. Login sebagai admin.store@viygo.id
2. Buka /admin/store/lookbooks → buat lookbook baru:
   - Isi judul, tema, cover_url, is_published=true
   - Tambah 2 slide, setiap slide tag minimal 1 produk
3. Buka /lookbook → lookbook baru tampil di grid
4. Klik lookbook → halaman detail:
   - Slide 1 tampil dengan gambar
   - Product tag pin muncul → klik/hover → popup nama produk + harga
   - Navigasi prev/next slide berfungsi
5. Klik "Shop This Look" → semua produk di semua slide masuk cart
6. Mobile view (375px) → grid 1 kolom, gambar full-width
```

Lanjutkan juga ke **[phase-3b-empty-return.md](phase-3b-empty-return.md)** dan **[phase-3c-community.md](phase-3c-community.md)** (bisa paralel).
