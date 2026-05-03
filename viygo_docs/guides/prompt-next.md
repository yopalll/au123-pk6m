# PANDUAN PENGEMBANGAN LANJUTAN (AI AGENT PROMPT)

> **Untuk AI Agent Berikutnya:** Baca prompt ini secara mendalam sebelum mengeksekusi instruksi apa pun. File ini berisi *blueprint* arsitektur dan peta jalan (roadmap) untuk menyelesaikan web app VIYGO.

---

## 📌 KONTEKS PROYEK

Anda sedang melanjutkan pengembangan **VIYGO**, sebuah *marketplace* pemesanan layanan kecantikan & kebugaran (salon, spa, klinik) yang dikembangkan menyerupai platform industri terkemuka (seperti Treatwell). 

### Stack Teknologi Saat Ini:
- **Backend**: Laravel 13.x (PHP 8.2+)
- **Frontend Utama**: Tailwind CSS v4 + Alpine.js + Livewire 3 (menggunakan komponen **Livewire Flux**)
- **Admin Panel**: Filament v5.6 (Sudah selesai, 100% beroperasi)
- **Database**: MySQL/PostgreSQL (13 model terelasi, 8,750 salon, 190,594 services, dengan implementasi `SoftDeletes`)
- **Map System**: Leaflet 1.9.4 via CDN

**Dokumen Referensi Wajib Baca (BACA INI SEBELUM MENULIS KODE)**:
1. **SCAN SELURUH FILE `.md`**: Sebagai AI Agent, tugas pertama Anda adalah membaca dan memahami **sebanyak mungkin** konteks dari file `.md` yang ada di root repositori.
2. `progress.md`: Mengetahui persis fitur apa yang sudah dan belum selesai (saat ini 75% selesai).
3. `readme-admin.md`: Panduan arsitektur Filament (beserta catatan kelemahan dan skalabilitas).
4. `INTEGRATION_GUIDE.md`, `PROGRESS_REPORT.md`, `LAPORAN_PROYEK.md`: Pahami alur scraping, deviasi database, dan log pengerjaan sebelumnya.
5. **Dokumentasi Midtrans Snap API untuk Laravel**: Lakukan web search atau baca dokumentasi resmi Midtrans Snap API (Frontend Pop-up & Backend Token Generation) sebelum Anda mulai coding fitur Payment Flow. Pahami konsep *Snap Token* dan *Webhook Handling*.

---

## 🎯 OBJEKTIF UTAMA ANDA

Selesaikan fitur-fitur **Prioritas 1 hingga Prioritas 6** yang tersisa di `progress.md`. Kerjakan secara bertahap, berikan prioritas tertinggi pada penyelesaian **Dashboard Salon Owner**, lalu bergerak ke sistem *Booking Pintar* dan *Payment Flow*.

### TUGAS 1: Middleware & Role-Based Access (PRIORITAS TINGGI)
Saat ini semua user otentik bisa mengakses rute `/akun`. Kita perlu batasan yang ketat:
1. Buat custom middleware (misalnya `RoleMiddleware` atau `CheckRole`).
2. Terapkan logika: 
   - User dengan `role = 'customer'` hanya bisa mengakses rute `/akun/*`.
   - User dengan `role = 'salon_owner'` diarahkan (atau hanya bisa mengakses) rute `/owner/*`.
   - User dengan `role = 'admin'` memiliki panel Filament di `/admin`.
3. Daftarkan middleware ini di arsitektur baru Laravel 11/13 (`bootstrap/app.php`).

### TUGAS 2: Dashboard Salon Owner (Filament v5.6)
Jangan membuat dashboard owner dari awal menggunakan Blade murni. **Manfaatkan Filament Panel!**
1. Buat **Panel Filament Kedua** khusus untuk salon owner (misal: `OwnerPanelProvider`).
2. URL panel: `http://viygo.test/owner`.
3. Logika Akses (`canAccessPanel`): Pastikan hanya `role = 'salon_owner'` yang bisa masuk, dan scope data (Eloquent Query) agar owner **HANYA** bisa melihat data (salon, service, staff, order, review) milik mereka sendiri berdasarkan `id_user` yang login.
4. Fitur Panel Owner yang wajib dibuat:
   - **Dashboard Widgets**: Total Booking hari ini, Pendapatan bulan ini.
   - **Manajemen Layanan**: CRUD untuk resource `Service` milik salon mereka.
   - **Manajemen Staf**: CRUD untuk `Staff` dan `StaffSchedule` (jam kerja staf).
   - **Order / Transaksi**: Lihat daftar *booking* yang masuk dan update status (pending -> confirmed -> success).
   - **Galeri Foto**: Upload dan atur gambar salon (`SalonImage`).

### TUGAS 3: Halaman Statis & Footer Links
Perhatikan `resources/views/components/viygo-footer.blade.php`. Terdapat banyak link kosong (`href="#"`):
1. **Perusahaan**: About Us, Careers, Blog, Press. *(Catatan: Link "Blog" harus mengarah ke route `treatment-files`)*.
2. **Bantuan**: Help Centre, Contact Us, Privacy Policy, Terms & Conditions, Cookie Policy. 
   - *(Catatan Khusus: Untuk halaman **Help Centre** dan **Contact Us**, pastikan Anda mencantumkan **email utama helpdesk VIYGO** (misalnya `support@viygo.com` atau `help@viygo.com`) agar user tahu ke mana harus melapor).*
3. Buat controller statis atau view routing di `routes/web.php` untuk merender halaman-halaman ini (gunakan layout `layouts.public`).
4. Jangan lupa *binding* icon social media ke URL *dummy* atau *state* target.
5. **Kebutuhan Gambar (PENTING)**: Halaman statis seperti *About Us* atau *Careers* pasti membutuhkan gambar pendukung. Anda **TIDAK PERLU** men-generate gambar tersebut. Alih-alih, buat sebuah file bernama `README-GAMBAR-STATIS.md` yang ditujukan untuk AI Agent Spesialis Gambar. File tersebut harus berisi list tabel dengan format:
   - **ID Gambar** (contoh: `img-about-hero`)
   - **Lokasi Halaman** (contoh: `/about-us`)
   - **Dimensi/Ukuran** (contoh: `1920x1080`)
   - **Ekstensi** (contoh: `.webp` atau `.jpg`)
   - **Prompt Gambar Lengkap** (Deskripsi detail apa yang harus di-generate oleh AI gambar, menyesuaikan dengan nuansa kecantikan/salon VIYGO).

### TUGAS 4: Booking yang Lebih Pintar & Pembayaran
1. **Sistem Booking**: Saat ini fungsi `BookingController` menggunakan grid waktu statis. Anda harus mengubah logika agar UI booking (`booking/create.blade.php`) mengecek *availability* berdasarkan tabel `staff_schedule` dan memastikan tidak ada tabrakan jadwal (*double booking*) di tabel `order_detail`.
2. **Sistem Pembayaran**: 
   - Implementasikan *payment flow* eksternal menggunakan **Midtrans Snap API (Sandbox)**. 
   - Gunakan fitur Snap Pop-up atau Snap Redirect URL di *frontend* Laravel Anda.
   - Setelah sukses (implementasikan Webhook / Notification Handler Midtrans), rekam data ke tabel `pembayaran` dan ubah status tabel `order` menjadi `confirmed`.

### TUGAS 5: Review System (Ulasan Pelanggan)
1. Pelanggan hanya boleh memberikan ulasan (`Review`) untuk pemesanan yang statusnya `success`.
2. Buat form ulasan di dalam dashboard pelanggan (`/akun/bookings`).
3. Pastikan rating pada ulasan tersebut teragregasi secara asinkron (update kolom `rating` dan `total_review` pada tabel `salon` agar *search engine* dan *sorting* tetap cepat).

### TUGAS 6: Kustomisasi Halaman Login/Register
Saat ini sistem *auth* menggunakan bawaan (Fortify/Breeze/Flux). Tugas Anda adalah:
1. Modifikasi halaman `/login` dan `/register`.
2. Jangan biarkan desain tetap default bawaan framework. Ubah agar sesuai dengan *branding* VIYGO.
3. Ganti logo bawaan dengan aset logo VIYGO yang sudah ada di folder `public` (seperti `icon.png`, `dark.png`, atau `white.png`).

### TUGAS 7: Melengkapi Halaman Dummy di Header (Navbar)
Setelah melakukan *scanning*, terdapat beberapa link di `viygo-navbar.blade.php` yang saat ini hanya berupa *stub* (halaman kosong/belum fungsional):
1. **Gift Card (`/gift-card`)**: Buat UI pembelian Gift Card.
2. **Lookbook (`/lookbook`)**: Buat halaman galeri inspirasi model rambut/kecantikan.
3. **Treatment Files (`/treatment-files`)**: Buat kerangka halaman blog/artikel.
4. **For Salons (`/mitra`)**: Buat halaman *Landing Page B2B* untuk menarik salon bergabung menjadi mitra VIYGO.
Tugas Anda adalah mengisi desain halaman-halaman tersebut agar tidak terlihat kosong. Ikuti instruksi "Kebutuhan Gambar" di Tugas 3 untuk melengkapi aset visual yang dibutuhkan halaman ini.

### TUGAS 8: Penyempurnaan Dashboard Customer (Panel User)
Sebagai aplikasi *marketplace*, fungsionalitas untuk sisi *Customer/User* sangatlah krusial. Saat ini UI di halaman `/akun` sudah ada, namun *backend logic*-nya perlu dipastikan berjalan 100%. Tugas Anda:
1. **Riwayat Pemesanan (Order History)**: Sempurnakan halaman `/akun/bookings`. Pastikan data yang tampil berasal dari relasi model `User -> orders -> order_details` dan terbagi secara dinamis berdasarkan status (Upcoming, Completed, Cancelled).
2. **Kupon & Reward (Promos)**: Sempurnakan fungsionalitas di halaman `/akun/reward`. Pastikan relasi Many-to-Many `user_promo` berjalan sehingga user dapat melihat kupon/promo apa saja yang mereka miliki, sisa kuota, dan status `is_used`.
3. **Fitur Wishlist/Favorit**: Sempurnakan tombol *Favorite* (bentuk hati) pada komponen `salon-card`. Pastikan data tersimpan (buat tabel pivot `user_favourites` jika belum ada) dan muncul dengan benar di rute `/akun/favorit`.
4. **Pembatalan Pesanan**: Berikan fungsionalitas agar user dapat membatalkan *order* mereka sendiri (mengubah status menjadi `cancelled`) asalkan waktu layanan belum dimulai.

### TUGAS 9 (OPSIONAL / BATCH SELANJUTNYA): Ekstensi Skincare & Komunitas
Fitur-fitur di bawah ini adalah cetak biru untuk pengembangan *Batch 2*. Anda **TIDAK PERLU** menyelesaikannya di sesi ini kecuali semua prioritas 1-8 sudah selesai 100%. Persiapkan arsitektur dasarnya jika memungkinkan:
1. **E-commerce Skincare**: Penambahan modul *marketplace* produk fisik (skincare) yang berdiri berdampingan dengan pemesanan layanan (*services*).
2. **Skincare Lookbook**: Penambahan sub-kategori pada `/lookbook` khusus untuk panduan produk dan *before-after* skincare.
3. **Skincare Empty Return (Eco-friendly Logistics)**: Sistem logistik/kurir pengembalian botol kosong skincare via aplikasi. Pelanggan yang mendaur ulang botol akan mendapatkan poin *reward* belanja atau akses membaca koleksi buku eksklusif di salon.
4. **Digital Library Community**: Forum diskusi terintegrasi di dalam web tempat *user* bisa saling memberikan ulasan buku (yang disediakan salon) atau berbagi *tips* skincare, menciptakan komunitas informal yang aktif.
5. **Staff Portal (Dashboard Karyawan)**: Saat ini *Staff* adalah entitas pasif yang dikelola oleh *Salon Owner*. Ke depannya, hubungkan tabel `staff` dengan `users` agar setiap kapster/terapis memiliki akses login (role: `staff`) untuk melihat jadwal kerja harian mereka sendiri tanpa bisa merusak data salon.

---

## 🛠️ INSTRUKSI TEKNIS KHUSUS

1. **JANGAN MERUSAK DESAIN**: Gunakan kelas Tailwind CSS v4 yang sudah terkonfigurasi. Pastikan UI bersifat *responsive* dan selaras dengan desain Treatwell (biru navy `#1B2D6B`, *clean white backgrounds*, *rounded corners* minimal).
2. **GUNAKAN FILAMENT V5.6**: Untuk semua pembuatan Resource di panel owner, pastikan *type hint* sesuai dengan standar v5.6 (contoh: `protected static string | \UnitEnum | null $navigationGroup` dan metode `form(Schema $form): Schema`).
3. **MENGELOLA DATA BESAR**: Jangan pernah membiarkan query database tidak menggunakan pagination. Gunakan `->limit()`, `->paginate()`, dan optimalkan Eager Loading (`with(['relasi'])`) untuk menghindari N+1 query problem, terutama karena database ini memiliki ratusan ribu *record*.
4. **UPDATE DOKUMENTASI**: Setiap kali Anda menyelesaikan sebuah modul besar, selalu perbarui file `progress.md` (ubah status dari `[ ]` menjadi `[x]`) dan perbarui bagian 'Estimasi progress total'.

---

## 🚦 WORKFLOW & ATURAN GIT (SANGAT PENTING)

Untuk menjaga kebersihan repositori dan memudahkan *code review*, Anda **WAJIB** mengikuti alur kerja berikut pada setiap sesi pengembangan:

1. **Satu Fitur = Satu Branch**: Jangan pernah bekerja atau melakukan push langsung ke branch `main` atau `master`. Buat branch baru untuk setiap fitur spesifik (contoh: `git checkout -b feature/owner-panel` atau `feature/midtrans-payment`).
2. **Buat README Per Sesi (Checkpoint Berkelanjutan)**: Setelah menyelesaikan satu tugas/sesi, atau **sebelum token Anda habis**, buat/update sebuah file dokumentasi dengan nama format `README-[NAMA_FITUR].md` (contoh: `README-owner-panel.md`). File ini WAJIB berfungsi sebagai *Checkpoint Berkelanjutan* (State Recovery) untuk agen LLM berikutnya. Harus berisi:
   - File apa saja yang sudah diubah dan dibuat.
   - Logika atau *workaround* yang sudah Anda terapkan.
   - **Status Terakhir**: Apa yang sedang *error* atau di mana Anda berhenti *coding*.
   - **Next Action**: Instruksi eksak apa yang harus dilakukan oleh agen LLM berikutnya saat mereka melanjutkan branch ini.
3. **Commit Terstruktur**: Lakukan *commit* secara berkala di dalam branch fitur tersebut dengan pesan yang deskriptif.

Mulai kerjakan langkah demi langkah dari **Tugas 1 (Middleware)**. Buat branch baru sekarang juga!

*You have full context. Start coding the future of VIYGO!*
