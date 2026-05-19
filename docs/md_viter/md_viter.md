# Alur Pendaftaran (Booking) Salon di Viygo

Dokumen ini menjelaskan **alur lengkap** saat seorang user melakukan booking di sebuah salon — mulai dari klik tombol "Book Now" sampai status booking jadi `confirmed`. Setiap langkah dijelaskan: **endpoint** yang dipanggil, **controller/method** yang menangani, **validasi** yang dijalankan, dan **tabel database** mana yang dituju.

---

## Peta Singkat (Bird's-Eye View)

```
[1] Halaman Salon              GET  /salon/{slug}
        │
        ▼ klik "Book Now"
[2] Wizard Booking             GET  /salon/{slug}/booking
        │   • pilih service(s)
        │   • pilih tanggal & staff (opsional)
        │   • AJAX cek slot   POST /salon/{slug}/booking/slots
        │   • AJAX cek promo  POST /booking/validate-promo
        │
        ▼ klik "Confirm Booking"
[3] Submit Booking             POST /salon/{slug}/booking
        │   • validasi + cek race condition
        │   • INSERT ke tabel `order` (status=pending)
        │   • INSERT ke tabel `order_detail` (1 baris per service)
        │   • INCREMENT `promo.used_counter` (kalau pakai promo)
        │
        ▼ redirect
[4] Halaman Pembayaran         GET  /booking/{kode}/payment
        │   • render halaman Midtrans Snap
        │   • frontend POST /booking/{kode}/payment/token
        │   • backend hit API Midtrans → dapat snap_token
        │   • INSERT/UPDATE tabel `pembayaran` (status=pending)
        │
        ▼ user bayar di Snap popup
[5a] Frontend callback         POST /booking/{kode}/payment/finish
[5b] Webhook server-to-server  POST /midtrans/webhook
        │   • verifikasi status ke Midtrans API
        │   • UPDATE `pembayaran.status_pembayaran` = completed
        │   • UPDATE `order.status` = confirmed
        │
        ▼ redirect
[6] Halaman Konfirmasi         GET  /booking/{kode}/konfirmasi
```

---

## Detail Setiap Langkah

### [1] User Mendarat di Halaman Salon

- **URL:** `GET /salon/{slug}`
- **Route:** [routes/web.php:27](routes/web.php#L27)
- **Controller:** `SalonController@show`
- **Data dibaca dari DB:** tabel `salon`, `service`, `staff`, `salon_image`, `review`
- **Tujuan:** user lihat profil salon → klik tombol **Book Now** → diarahkan ke wizard booking.

---

### [2] Wizard Booking (Halaman Formulir)

- **URL:** `GET /salon/{slug}/booking`
- **Route:** [routes/web.php:63](routes/web.php#L63)
- **Controller:** [`BookingController@create`](app/Http/Controllers/BookingController.php#L29)
- **Middleware:** `auth` — user **wajib login** dulu.
- **Yang ditampilkan:**
  - Daftar service salon (untuk dipilih, bisa multi-select)
  - Daftar staff aktif (untuk dipilih, opsional → kalau kosong = "any available staff")
  - Date picker
  - Input promo code
  - Input catatan
- **View:** `resources/views/booking/create.blade.php`

#### [2a] AJAX — Cek Slot Tersedia

Saat user pilih service + tanggal, frontend memanggil:

- **URL:** `POST /salon/{slug}/booking/slots`
- **Controller:** [`BookingController@getSlots`](app/Http/Controllers/BookingController.php#L45)
- **Input JSON:**
  ```json
  {
    "service_ids": [12, 15],
    "date": "2026-05-20",
    "staff_id": 7
  }
  ```
- **Validasi:**
  - `service_ids` wajib array (min 1, max 20)
  - tiap id harus ada di tabel `service`
  - `date` harus hari ini atau ke depan
- **Logika:**
  - Hitung total durasi gabungan semua service
  - Service `BookingSlotService` menghitung slot yang available, mempertimbangkan jam buka salon, durasi service, dan slot yang sudah terbooking
- **Output JSON:** daftar slot waktu (`["09:00", "09:30", "10:00", ...]`)

#### [2b] AJAX — Validasi Promo Code

Kalau user input promo code:

- **URL:** `POST /booking/validate-promo`
- **Controller:** [`BookingController@validatePromo`](app/Http/Controllers/BookingController.php#L86)
- **Logika:**
  - Cek promo ada & belum expired (`Promo::byCode`)
  - Cek minimum transaksi (`meetsMinimum`)
  - Cek stok promo (`isSoldOut`)
  - Hitung diskon (`calculateDiscount`)
- **Output JSON:** `{valid, id_promo, nama_promo, diskon, tipe}`

> ⚠️ Pada step ini promo **belum** dipotong stok — hanya validasi. Stok dipotong saat order di-create di step [3].

---

### [3] Submit Booking — Data Masuk ke Database

- **URL:** `POST /salon/{slug}/booking`
- **Route:** [routes/web.php:65](routes/web.php#L65)
- **Controller:** [`BookingController@store`](app/Http/Controllers/BookingController.php#L121)
- **Input form:**
  ```
  id_service[]   = [12, 15]
  tanggal        = "2026-05-20"
  waktu          = "09:00"
  id_staff       = 7         (opsional, null = auto-pick)
  catatan        = "..."     (opsional)
  kode_promo     = "PROMO10" (opsional)
  ```
- **Validasi server-side:**
  - Semua service masih `status=active` & milik salon ini
  - Tanggal valid (today atau setelahnya)
  - Staff (kalau dipilih) masih aktif di salon
  - Slot masih tersedia (re-check, bukan trust input)
  - Promo masih valid & belum habis stoknya

#### Inti Transaksi Database (locked dengan `DB::transaction`)

Untuk mencegah **double-booking** (BUG-A01), insert dibungkus transaction + `lockForUpdate`:

1. **Lock** baris `order_detail` yang konflik (staff yang sama + tanggal yang sama)
2. **Re-verify** slot di dalam lock
3. **Increment** `promo.used_counter` kalau pakai promo
4. **INSERT** ke tabel `order`:

   | Kolom              | Diisi dengan                                      |
   |--------------------|---------------------------------------------------|
   | `id_user`          | `auth()->id()`                                    |
   | `id_salon`         | `salon.id_salon`                                  |
   | `id_promo`         | `promo->id_promo` atau `null`                     |
   | `kode_order`       | `'VYG-' . random(8)` — mis. `VYG-E6UFTSOY`       |
   | `date_order`       | `$data['tanggal']`                                |
   | `total_pembayaran` | total setelah diskon (GBP)                        |
   | `total_diskon`     | nominal diskon (GBP)                              |
   | `status`           | `'pending'`                                       |

5. **INSERT** ke tabel `order_detail` — **1 baris per service**, jam-nya berurutan:

   | Kolom            | Contoh                            |
   |------------------|-----------------------------------|
   | `id_order`       | FK ke `order`                     |
   | `id_service`     | FK ke `service`                   |
   | `id_staff`       | FK ke `staff` (atau auto-pick)    |
   | `start_time`     | `09:00` untuk service-1, `09:30` untuk service-2, dst. |
   | `end_time`       | dihitung dari `start + durasi`    |
   | `harga_at_order` | snapshot harga saat order (untuk history, biar ga berubah kalau harga di-update) |
   | `subtotal`       | sama dengan `harga_at_order`      |
   | `catatan`        | hanya disimpan di service pertama |
   | `status`         | `'pending'`                       |

6. **Redirect** → `/booking/{kode_order}/payment` dengan flash message.

> 💡 **Snapshot harga**: lapangan `harga_at_order` sengaja menyimpan harga **pada saat booking**, bukan FK live. Jadi kalau salon naikkan harga besok, riwayat booking lama tetap tercatat di harga lama.

---

### [4] Halaman Pembayaran (Midtrans Snap)

- **URL:** `GET /booking/{kode}/payment`
- **Controller:** [`PaymentController@show`](app/Http/Controllers/PaymentController.php#L45)
- **Cek:** order milik user yang sedang login & masih `status=pending`. Kalau tidak → 404.
- **View:** `resources/views/booking/payment.blade.php` — load Midtrans Snap JS + client key.

#### Generate Snap Token

Frontend AJAX:

- **URL:** `POST /booking/{kode}/payment/token`
- **Controller:** [`PaymentController@createSnapToken`](app/Http/Controllers/PaymentController.php#L64)
- **Yang terjadi:**
  1. Kirim payload ke API Midtrans:
     - `transaction_details`: `order_id`, `gross_amount` (GBP → IDR pakai rate dari config)
     - `customer_details`: nama, email, phone user
     - `item_details`: array service (`id`, `name`, `price`, `quantity`)
  2. Dapat `snap_token` dari Midtrans
  3. **INSERT/UPDATE** tabel `pembayaran` (1 baris per order):

     | Kolom               | Diisi dengan                |
     |---------------------|----------------------------|
     | `id_order`          | FK ke `order`               |
     | `id_user`           | `order->id_user`            |
     | `metode_pembayaran` | `'midtrans_snap'`           |
     | `snap_token`        | token dari Midtrans         |
     | `jumlah_bayar`      | `order->total_pembayaran`   |
     | `status_pembayaran` | `'pending'`                 |
     | `midtrans_order_id` | `kode_order` (atau + suffix `-R{timestamp}` kalau retry) |

  4. Return JSON `{snap_token, payment_id}` → frontend panggil `window.snap.pay(token, ...)`

> 🐛 **Saat ini bermasalah**: API Midtrans return HTTP 401 ("unauthorized") karena key di `.env` belum diganti dengan key valid dari dashboard sandbox.

---

### [5] User Bayar di Midtrans Snap Popup

User pilih metode pembayaran di popup Snap (kartu, VA, GoPay, dll) dan menyelesaikan transaksi. Hasilnya **dikomunikasikan dua jalur** (redundan biar reliable):

#### [5a] Callback Frontend (synchronous, dari browser user)

- **URL:** `POST /booking/{kode}/payment/finish`
- **Controller:** [`PaymentController@finish`](app/Http/Controllers/PaymentController.php#L161)
- **Yang terjadi:**
  1. Server **verifikasi ulang** status ke API Midtrans (`Transaction::status`) — bukan trust data dari frontend
  2. Berdasarkan `transaction_status`:
     - `capture`/`settlement` → `pembayaran.status=completed`, `order.status=confirmed`
     - `pending` → tetap pending
     - `deny`/`expire`/`cancel`/`failure` → `pembayaran.status=failed`
  3. Update tabel `pembayaran`: `id_transaksi`, `jumlah_bayar`, `tanggal_bayar`
  4. Update tabel `order`: `status` (kalau berhasil)
- **Output:** `{status: "confirmed"}` → frontend redirect ke konfirmasi.

#### [5b] Webhook Server-to-Server (asynchronous, dari Midtrans)

- **URL:** `POST /midtrans/webhook`
- **Controller:** [`PaymentController@webhook`](app/Http/Controllers/PaymentController.php#L257)
- **Kenapa ada dua jalur?** Kalau user tutup browser sebelum redirect, jalur [5a] tidak jalan. Webhook ini **safety net** dari Midtrans.
- **Keamanan:**
  - Tidak trust body POST langsung — pakai `Midtrans\Notification` yang re-fetch dari Midtrans pakai server key
  - **Verifikasi signature SHA512** sebelum tulis ke DB
  - **Idempotency guard** (SEC-04): kalau `transaction_id` yang sama sudah pernah completed, skip — biar tidak double-process
  - `lockForUpdate` di order → concurrent webhook untuk order yang sama di-serialize

---

### [6] Halaman Konfirmasi

- **URL:** `GET /booking/{kode}/konfirmasi`
- **Controller:** [`BookingController@konfirmasi`](app/Http/Controllers/BookingController.php#L289)
- **Tampilan:** ringkasan booking (salon, tanggal, jam, services, staff, harga, status).
- **Status terakhir:** order = `confirmed`, payment = `completed`.

---

## Ringkasan Tabel Database yang Terlibat

| Tabel              | Kapan ditulis                          | Field penting                                         |
|--------------------|----------------------------------------|-------------------------------------------------------|
| `order`            | Step [3] — submit booking              | `kode_order`, `id_user`, `id_salon`, `status`         |
| `order_detail`     | Step [3] — 1 baris per service         | `id_service`, `id_staff`, `start_time`, `end_time`    |
| `pembayaran`       | Step [4] — generate snap token         | `snap_token`, `midtrans_order_id`, `status_pembayaran`|
| `pembayaran`       | Step [5] — selesai bayar (update)      | `id_transaksi`, `tanggal_bayar`, `status_pembayaran`  |
| `order`            | Step [5] — selesai bayar (update)      | `status` = `confirmed`                                |
| `promo`            | Step [3] — kalau pakai promo (update)  | `used_counter++`                                      |

---

## Alur Pembatalan (Bonus)

- **URL:** `POST /booking/{kode}/batal`
- **Controller:** [`BookingController@batal`](app/Http/Controllers/BookingController.php#L299)
- **Cek:**
  - Order milik user
  - Status masih `pending` atau `confirmed`
  - Appointment **belum dimulai** (pakai `start_time` dari detail pertama, bukan midnight — BUG-A07)
- **Kalau sudah bayar (`confirmed`):**
  - Trigger `Midtrans\Transaction::refund` dengan `refund_key` unik (BUG-A08)
  - Update `pembayaran.status_pembayaran` = `refunded`
- **Update:** `order.status` = `canceled`

---

## Catatan Keamanan / Race Condition

| Issue                         | Mitigasi                                                              |
|-------------------------------|-----------------------------------------------------------------------|
| Double-booking (2 user grab slot bersamaan) | `DB::transaction` + `lockForUpdate` di `order_detail` (BUG-A01) |
| Promo dipakai melebihi stok   | Re-check `used_counter` di dalam transaction sebelum increment        |
| Snap token leak / replay      | `pembayaran` simpan per order, `snap_token` di-update tiap retry      |
| Webhook spoofing              | Verifikasi SHA512 signature + re-fetch dari Midtrans API              |
| Webhook duplikat (network retry) | Idempotency guard berdasarkan `transaction_id`                     |
| Order_id collision di Midtrans (retry payment) | Suffix `-R{timestamp}`, webhook strip suffix dengan regex |
| Snapshot harga                | Field `harga_at_order` di `order_detail` mengunci harga saat order    |
| Order amount > 999,999,999 IDR| `convertGbpToIdr` lempar `DomainException` (BUG-A10)                  |
