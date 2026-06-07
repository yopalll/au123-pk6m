# Phase 2A — Modul 5: Rincian Booking + Invoice PDF
## Step 2.1

> **Prerequisite:** Phase 1D selesai (`barryvdh/laravel-dompdf` terinstall)  
> **Design Reference:** `docs_v2/design/e2.1_order_booking_history/` (desktop) + `docs_v2/design/m_e1_account_overview/` (mobile)  
> **Verifikasi:** `/akun/bookings/{kode}` tampil benar, Download Invoice → PDF ter-download

---

## KONTEKS

Modul ini **tidak butuh data produk** — hanya extends fitur booking V1 yang sudah ada.
Model V1 yang dipakai: `Order`, `Pembayaran`, `OrderDetail`, `Salon`, `Service` (BUKAN "treatment").

> 🔴 **WAJIB baca [CATATAN-LINGKUNGAN §4](CATATAN-LINGKUNGAN.md) dulu.** Nama relasi/kolom V1 sudah dikonfirmasi:
>
> | Asumsi lama (salah) | Nama V1 yang BENAR |
> |---|---|
> | `orderDetails` | **`details`** (hasMany) |
> | `detail->treatment` | **`detail->service`** |
> | `order->subtotal` | **hitung** `$order->details->sum('subtotal')` |
> | `order->total` | **`order->total_pembayaran`** |
> | `order->diskon` | **`order->total_diskon`** |
> | `detail->harga` | **`detail->harga_at_order`** |
>
> ⚠️ **Salon & Pembayaran:** baca `app/Models/Salon.php` & `app/Models/Pembayaran.php` untuk
> memastikan nama kolom (kemungkinan `nama_salon`, bukan `nama`). Sesuaikan view/PDF.

**Files yang akan diubah/dibuat:**
1. `routes/web.php` — 2 route baru (masuk ke group akun yang SUDAH ADA)
2. `app/Http/Controllers/AkunController.php` — 2 method baru
3. `resources/views/akun/booking-detail.blade.php` — view baru
4. `resources/views/pdf/invoice-booking.blade.php` — template PDF baru

---

## SUB-STEP 2.1.1 — Routes

> Group akun V1 SUDAH ADA: `Route::prefix('akun')->name('akun.')` di dalam
> `middleware(['auth','verified'])` + `role:customer`. **Masukkan 2 route ini ke group itu**
> (jangan buat group baru). Path jadi `/akun/bookings/{kode}` dgn name `akun.booking.detail`.

```php
// Di dalam group akun yang sudah ada (lihat web.php baris ~75-89):
Route::get('/bookings/{kode}', [AkunController::class, 'bookingDetail'])
    ->name('booking.detail');
Route::get('/bookings/{kode}/invoice', [AkunController::class, 'downloadInvoice'])
    ->name('booking.invoice');
// → name final otomatis jadi 'akun.booking.detail' & 'akun.booking.invoice'
```

---

## SUB-STEP 2.1.2 — Controller Methods

Tambahkan 2 method ke `app/Http/Controllers/AkunController.php`:

```php
use Barryvdh\DomPDF\Facade\Pdf;

public function bookingDetail(string $kode)
{
    $user = auth()->user();

    // Query booking + relasi lengkap
    // Sesuaikan nama tabel/model dengan struktur V1 Anda
    $order = \App\Models\Order::where('kode_order', $kode)
        ->where('id_user', $user->id_user)
        ->with([
            'salon',
            'pembayaran',
            'details.service',   // OrderDetail->service (BUKAN treatment)
            'details.staff',
        ])
        ->firstOrFail();

    return view('akun.booking-detail', compact('order'));
}

public function downloadInvoice(string $kode)
{
    $user = auth()->user();

    $order = \App\Models\Order::where('kode_order', $kode)
        ->where('id_user', $user->id_user)
        ->with(['salon', 'pembayaran', 'details.service', 'details.staff'])
        ->firstOrFail();

    $pdf = Pdf::loadView('pdf.invoice-booking', compact('order'))
        ->setPaper('a4', 'portrait');

    return $pdf->download("VIYGO-Invoice-{$kode}.pdf");
}
```

---

## SUB-STEP 2.1.3 — View Rincian Booking

**File:** `resources/views/akun/booking-detail.blade.php`

Gunakan design reference: `docs_v2/design/e2.1_order_booking_history/code.html`

Struktur konten halaman (sesuai PRD Section 8.3.1):

```html
@extends('layouts.public')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    {{-- HEADER: Kode Order + Status Badge --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <p class="text-sm text-gray-500">Kode Pesanan</p>
            <h1 class="text-2xl font-bold font-playfair">{{ $order->kode_order }}</h1>
        </div>
        <span class="mt-2 sm:mt-0 px-4 py-2 rounded-full text-sm font-medium
            {{ match($order->status) {
                'pending'   => 'bg-yellow-100 text-yellow-800',
                'confirmed' => 'bg-blue-100 text-blue-800',
                'success', 'completed' => 'bg-green-100 text-green-800',
                'cancelled' => 'bg-red-100 text-red-800',
                default     => 'bg-gray-100 text-gray-800',
            } }}">
            {{ ucfirst($order->status) }}
        </span>
    </div>

    {{-- INFO SALON --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="font-semibold text-lg mb-4">Informasi Salon</h2>
        <div class="flex gap-4">
            <div class="flex-1">
                <p class="font-medium">
                    <a href="{{ route('salon.show', $order->salon->slug) }}" class="hover:underline">
                        {{ $order->salon->nama_salon }}
                    </a>
                </p>
                <p class="text-sm text-gray-500 mt-1">{{ $order->salon->alamat }}</p>
                <p class="text-sm text-gray-500">{{ $order->salon->phone_number }}</p>
            </div>
        </div>
        {{-- Mini Map (Leaflet) --}}
        @if($order->salon->latitude && $order->salon->longitude)
        <div id="map" class="w-full h-40 rounded-xl mt-4"
             data-lat="{{ $order->salon->latitude }}"
             data-lng="{{ $order->salon->longitude }}">
        </div>
        @endif
    </div>

    {{-- RINCIAN SERVICE --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="font-semibold text-lg mb-4">Rincian Service</h2>
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2">Service</th>
                        <th class="text-left py-2">Staff</th>
                        <th class="text-left py-2">Durasi</th>
                        <th class="text-right py-2">Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->details as $detail)
                    <tr class="border-b last:border-0">
                        <td class="py-3">{{ $detail->service->nama }}</td>
                        <td class="py-3 text-gray-600">{{ $detail->staff?->nama ?? '-' }}</td>
                        <td class="py-3 text-gray-600">{{ $detail->service->durasi ?? '-' }} min</td>
                        <td class="py-3 text-right">Rp {{ number_format($detail->harga_at_order, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- Mobile: card-based --}}
        <div class="sm:hidden space-y-3">
            @foreach($order->details as $detail)
            <div class="border rounded-xl p-4">
                <p class="font-medium">{{ $detail->service->nama }}</p>
                <div class="flex justify-between text-sm text-gray-500 mt-1">
                    <span>{{ $detail->staff?->nama ?? '-' }} · {{ $detail->service->durasi ?? '-' }} min</span>
                    <span>Rp {{ number_format($detail->harga_at_order, 0, ',', '.') }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @if($order->catatan)
        <p class="text-sm text-gray-500 mt-4 italic">Catatan: {{ $order->catatan }}</p>
        @endif
    </div>

    {{-- RINGKASAN PEMBAYARAN --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="font-semibold text-lg mb-4">Ringkasan Pembayaran</h2>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span>Subtotal</span>
                <span>Rp {{ number_format($order->details->sum('subtotal'), 0, ',', '.') }}</span>
            </div>
            @if($order->total_diskon > 0)
            <div class="flex justify-between text-green-600">
                <span>Diskon Promo</span>
                <span>- Rp {{ number_format($order->total_diskon, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="flex justify-between font-bold text-base border-t pt-2 mt-2">
                <span>Total Pembayaran</span>
                <span>Rp {{ number_format($order->total_pembayaran, 0, ',', '.') }}</span>
            </div>
        </div>
        @if($order->pembayaran)
        <div class="mt-4 pt-4 border-t text-sm text-gray-500">
            <p>Metode: {{ $order->pembayaran->metode_pembayaran ?? '-' }}</p>
            <p>Status: <span class="font-medium">{{ ucfirst($order->pembayaran->status_pembayaran) }}</span></p>
            @if($order->pembayaran->id_transaksi)
            <p>ID Transaksi: {{ $order->pembayaran->id_transaksi }}</p>
            @endif
        </div>
        @endif
    </div>

    {{-- TIMELINE STATUS --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="font-semibold text-lg mb-4">Status</h2>
        <div class="relative">
            @php
            $timeline = [
                ['label' => 'Booking Dibuat',     'status' => ['pending','confirmed','success','completed'], 'time' => $order->created_at],
                ['label' => 'Pembayaran Berhasil', 'status' => ['confirmed','success','completed'],           'time' => $order->pembayaran?->tanggal_bayar],
                ['label' => 'Dikonfirmasi Salon',  'status' => ['success','completed'],                       'time' => $order->confirmed_at ?? null],
                ['label' => 'Treatment Selesai',   'status' => ['completed'],                                  'time' => $order->completed_at ?? null],
            ];
            @endphp
            @foreach($timeline as $step)
            @php $done = in_array($order->status, $step['status']); @endphp
            <div class="flex items-start gap-4 mb-4">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0
                    {{ $done ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400' }}">
                    {{ $done ? '✓' : '○' }}
                </div>
                <div>
                    <p class="font-medium text-sm {{ $done ? '' : 'text-gray-400' }}">{{ $step['label'] }}</p>
                    @if($done && $step['time'])
                    <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($step['time'])->format('d M Y, H:i') }}</p>
                    @elseif(!$done)
                    <p class="text-xs text-gray-400">Menunggu</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- TOMBOL AKSI --}}
    <div class="flex flex-col sm:flex-row gap-3 sm:sticky sm:bottom-4">
        {{-- Download Invoice --}}
        <a href="{{ route('akun.booking.invoice', $order->kode_order) }}"
           class="flex-1 btn btn-primary flex items-center justify-center gap-2">
            📄 Download Invoice PDF
        </a>

        {{-- Batalkan --}}
        @if(in_array($order->status, ['pending', 'confirmed']))
        <form method="POST" action="{{ route('booking.cancel', $order->kode_order) }}">
            @csrf @method('DELETE')
            <button type="submit" onclick="return confirm('Yakin batalkan booking ini?')"
                    class="w-full sm:w-auto btn btn-outline-danger">
                ❌ Batalkan Booking
            </button>
        </form>
        @endif

        {{-- Review --}}
        @if(in_array($order->status, ['success', 'completed']))
        <a href="{{ route('salon.review', $order->salon->slug) }}"
           class="btn btn-outline flex items-center justify-center gap-2">
            ⭐ Tulis Review
        </a>
        @endif
    </div>
</div>
@endsection
```

---

## SUB-STEP 2.1.4 — Template PDF Invoice

**File:** `resources/views/pdf/invoice-booking.blade.php`

```html
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
    .header { background: #111316; color: white; padding: 24px; display: flex; justify-content: space-between; }
    .logo { font-size: 24px; font-weight: bold; letter-spacing: 2px; }
    .invoice-label { font-size: 18px; text-align: right; }
    .section { padding: 20px 24px; border-bottom: 1px solid #e5e7eb; }
    .section-title { font-weight: bold; font-size: 13px; text-transform: uppercase; color: #666; margin-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; }
    th { text-align: left; font-size: 11px; color: #666; border-bottom: 1px solid #e5e7eb; padding: 6px 0; }
    td { padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
    .total-row td { border-top: 2px solid #333; border-bottom: none; font-weight: bold; padding-top: 12px; }
    .footer { background: #f9fafb; padding: 16px 24px; text-align: center; font-size: 11px; color: #888; }
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="logo">VIYGO</div>
    <div class="invoice-label">
        <div>INVOICE</div>
        <div style="font-size: 13px; margin-top: 4px; opacity: 0.8">{{ $order->kode_order }}</div>
    </div>
</div>

<!-- Info Pelanggan + Salon -->
<div style="display: flex; gap: 0;">
    <div class="section" style="flex:1; border-right: 1px solid #e5e7eb;">
        <div class="section-title">Pelanggan</div>
        <p>{{ $order->user->first_name }} {{ $order->user->last_name }}</p>
        <p style="color: #666;">{{ $order->user->email }}</p>
        <p style="color: #666;">{{ $order->user->phone ?? '-' }}</p>
    </div>
    <div class="section" style="flex:1;">
        <div class="section-title">Salon</div>
        <p>{{ $order->salon->nama_salon }}</p>
        <p style="color: #666;">{{ $order->salon->alamat }}</p>
        <p style="color: #666;">{{ $order->salon->phone_number }}</p>
    </div>
</div>

<!-- Tabel Service -->
<div class="section">
    <div class="section-title">Rincian Service</div>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Service</th>
                <th>Staff</th>
                <th>Durasi</th>
                <th style="text-align: right;">Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->details as $i => $detail)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $detail->service->nama }}</td>
                <td>{{ $detail->staff?->nama ?? '-' }}</td>
                <td>{{ $detail->service->durasi ?? '-' }} min</td>
                <td style="text-align: right;">Rp {{ number_format($detail->harga_at_order, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Ringkasan -->
<div class="section">
    <table style="width: 50%; margin-left: auto;">
        <tr>
            <td>Subtotal</td>
            <td style="text-align:right;">Rp {{ number_format($order->details->sum('subtotal'), 0, ',', '.') }}</td>
        </tr>
        @if($order->total_diskon > 0)
        <tr>
            <td>Diskon</td>
            <td style="text-align:right;">- Rp {{ number_format($order->total_diskon, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td>TOTAL</td>
            <td style="text-align:right;">Rp {{ number_format($order->total_pembayaran, 0, ',', '.') }}</td>
        </tr>
    </table>
</div>

<!-- Info Pembayaran -->
<div class="section">
    <div class="section-title">Informasi Pembayaran</div>
    <p>Metode: {{ $order->pembayaran?->metode_pembayaran ?? '-' }}</p>
    <p>Status: {{ ucfirst($order->pembayaran?->status_pembayaran ?? '-') }}</p>
    @if($order->pembayaran?->tanggal_bayar)
    <p>Tanggal Bayar: {{ \Carbon\Carbon::parse($order->pembayaran->tanggal_bayar)->format('d M Y, H:i') }}</p>
    @endif
    @if($order->pembayaran?->id_transaksi)
    <p>ID Transaksi: {{ $order->pembayaran->id_transaksi }}</p>
    @endif
</div>

<!-- Footer -->
<div class="footer">
    <p>Terima kasih telah menggunakan VIYGO</p>
    <p style="margin-top: 4px;">Dicetak pada {{ now()->format('d M Y, H:i') }}</p>
    <p style="margin-top: 4px; color: #aaa;">VIYGO — Beauty, Skincare & Lifestyle Platform</p>
</div>

</body>
</html>
```

---

## SUB-STEP 2.1.5 — Link di Halaman Riwayat Booking

Di `resources/views/akun/bookings.blade.php` (halaman list booking yang sudah ada di V1), tambahkan link "Lihat Detail" ke setiap booking:

```html
<a href="{{ route('akun.booking.detail', $booking->kode_order) }}"
   class="text-sm text-primary hover:underline">
    Lihat Rincian →
</a>
```

---

## SUB-STEP 2.1.6 — Responsive

Pastikan `booking-detail.blade.php` responsive:
- Mobile: tombol aksi di sticky bottom bar, tabel service → card-based (sudah di code di atas)
- Tablet (768px+): side-by-side info salon + map
- Desktop (1024px+): timeline horizontal (opsional polish)

---

## VERIFIKASI

```
1. Login sebagai customer yang sudah punya booking
2. Buka /akun/bookings → klik "Lihat Rincian" → halaman detail tampil dengan:
   - Kode order, status badge
   - Info salon + mini map
   - Tabel service + harga
   - Ringkasan pembayaran
   - Timeline status
3. Klik "Download Invoice PDF" → file PDF ter-download
   - Cek konten PDF: logo VIYGO, info pelanggan, tabel service, total
4. Test di mobile (DevTools 375px) → tabel service tampil sebagai cards
5. Status 'pending' → tombol "Batalkan" muncul
6. Status 'completed' → tombol "Tulis Review" muncul
```

Setelah selesai, lanjutkan ke **[phase-2b-ecommerce.md](phase-2b-ecommerce.md)**.
