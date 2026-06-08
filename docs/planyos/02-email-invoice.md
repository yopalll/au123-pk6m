# Plan 02 — Email Invoice (kirim invoice PDF ke email)

## 1. Tujuan
Saat pembayaran **shop order sukses**, sistem otomatis **mengirim email berisi invoice PDF**
(attachment) ke email customer. Selain itu, di halaman detail pesanan ada tombol
**"Kirim invoice ke email"** untuk kirim ulang manual.

Saat ini invoice PDF **sudah ada** dan hanya bisa di-_download_
(`ProductOrderController@invoice` → `Pdf::loadView('pdf.invoice-produk')->download(...)`).
Kita pakai ulang view PDF yang sama, tinggal di-_render_ jadi attachment email.

## 2. Alur
```
Pembayaran sukses (ProductPaymentController@finish, status settlement/capture)
        │
        ▼
   order->status = 'paid'  ──►  Mail::to(order.user.email)->send(new ProductInvoiceMail($order))
                                      │
                                      ├─ render 'pdf.invoice-produk' jadi string PDF (dompdf)
                                      ├─ attachData(pdf, "VIYGO-Invoice-{kode}.pdf")
                                      └─ body email: ringkasan order + tombol "Lihat pesanan"
```
Manual: tombol di `shop/pesanan-detail.blade.php` → `POST shop.order.invoice.email` → kirim Mailable yang sama.

## 3. Keputusan desain
- **Sinkron** (tanpa queue) — server cuma `php artisan serve`. Kirim langsung di request.
  Supaya kegagalan email **tidak menggagalkan** pembayaran, panggilan `Mail::send` dibungkus
  `try/catch` + `Log::error` (best-effort). Pembayaran tetap sukses walau email error.
- Pakai ulang **view PDF yang sudah ada** (`pdf.invoice-produk`) → tidak duplikasi layout.
- Mailable generik `ProductInvoiceMail` menerima `ProductOrder` dan merender PDF di dalam `attachments()`.
- Kirim juga untuk **booking** (opsional, template `pdf.invoice-booking` sudah ada) lewat
  `BookingInvoiceMail` dengan pola sama — diimplementasikan bila waktu memungkinkan; prioritas shop.

## 4. File yang dibuat / diubah

### 4.1 Mailable — `app/Mail/ProductInvoiceMail.php` (BARU)
```php
class ProductInvoiceMail extends Mailable
{
    public function __construct(public ProductOrder $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invoice Pesanan #'.$this->order->kode_order.' — VIYGO');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invoice-produk', with: ['order' => $this->order]);
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.invoice-produk', ['order' => $this->order])->setPaper('a4');
        return [
            Attachment::fromData(fn () => $pdf->output(), 'VIYGO-Invoice-'.$this->order->kode_order.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
```
> Pastikan `$order` sudah di-_load_ relasi (`items`, `user`, `pembayaran`, `address`) sebelum dikirim,
> karena dompdf merender di luar request asal.

### 4.2 View body email — `resources/views/emails/invoice-produk.blade.php` (BARU)
HTML email: sapaan ke `$order->user->first_name`, nomor order, total, status "Lunas",
tabel ringkas item, tombol "Lihat Pesanan" (link ke `shop.order.show`), catatan invoice PDF terlampir.

### 4.3 Hook auto-send saat bayar sukses — `ProductPaymentController@finish` (UBAH)
Di dalam blok sukses, setelah `DB::transaction(...)`:
```php
try {
    $order->load(['items', 'user', 'pembayaran', 'address']);
    Mail::to($order->user->email)->send(new ProductInvoiceMail($order));
} catch (\Throwable $e) {
    Log::error('Gagal kirim email invoice', ['kode' => $order->kode_order, 'msg' => $e->getMessage()]);
}
```
(Hal yang sama bisa ditaruh di webhook notifikasi Midtrans bila ada, agar tetap terkirim
walau user menutup tab. Untuk sekarang cukup di `finish`.)

### 4.4 Tombol kirim ulang manual — route + controller method (UBAH)
- Route: `Route::post('/shop/pesanan/{kode}/invoice/email', [ProductOrderController::class, 'emailInvoice'])->name('shop.order.invoice.email')->middleware('auth');`
- Method `emailInvoice(string $kode)`:
  - ambil order milik `auth()->id()` (`firstOrFail`), guard hanya untuk status `paid`/selesai,
  - `Mail::to($order->user->email)->send(new ProductInvoiceMail($order))`,
  - `back()->with('success', 'Invoice dikirim ke '.$order->user->email)`.

### 4.5 Tombol di UI — `resources/views/shop/pesanan-detail.blade.php` (UBAH)
Tambah form kecil di sebelah tombol "Download Invoice":
```blade
<form method="POST" action="{{ route('shop.order.invoice.email') ... }}">
  @csrf
  <button>Kirim invoice ke email</button>
</form>
```

## 5. Edge cases
- Email user kosong/invalid → `try/catch` mencegah crash; tercatat di log.
- Order belum `paid` saat kirim manual → tolak dengan pesan.
- `MAIL_MAILER=log` → email + (info attachment) tampil di `storage/logs/laravel.log`
  (di driver log, attachment tidak benar-benar terkirim tapi proses render PDF tetap jalan,
  jadi error template ketahuan saat dev).

## 6. Cara test manual
1. Checkout 1 produk sampai halaman pembayaran, selesaikan (sandbox Midtrans atau paksa status).
2. Cek `storage/logs/laravel.log` → ada email "Invoice Pesanan #...".
3. Buka detail pesanan, klik **Kirim invoice ke email** → flash sukses + entri baru di log.

## 7. Definition of Done
- [ ] `ProductInvoiceMail` merender PDF tanpa error.
- [ ] Pembayaran sukses memicu email invoice (terlihat di log), tanpa menggagalkan pembayaran bila email error.
- [ ] Tombol kirim ulang manual berfungsi & ada guard status.
