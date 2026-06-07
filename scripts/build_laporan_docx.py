# -*- coding: utf-8 -*-
"""Generate LAPORAN_TUGAS_BESAR.docx (plain text only) for the VIYGO project."""
from docx import Document

doc = Document()


def p(text=''):
    doc.add_paragraph(text)


p('LAPORAN TUGAS BESAR - VIYGO')
p()
p('Mata Kuliah    : _______________________')
p('Dosen Pengampu : _______________________')
p('Tanggal        : _______________________')
p()

p('1. Anggota Kelompok')
p()
p('1. Nama: ____________________  NIM: __________  Peran: Project Lead / Backend')
p('2. Nama: ____________________  NIM: __________  Peran: Frontend / UI')
p('3. Nama: ____________________  NIM: __________  Peran: Database / Scraper')
p('4. Nama: ____________________  NIM: __________  Peran: Testing / Dokumentasi')
p()

p('2. Nama & Jenis Website')
p()
p('Nama Website : VIYGO')
p('Jenis Website : E-commerce / Marketplace Jasa - platform pencarian dan booking '
  'layanan salon kecantikan (bergaya Treatwell).')
p('Pengembangan Lanjut (V2) : berkembang menjadi Beauty, Skincare & Lifestyle Platform '
  'yang menggabungkan Booking Salon, E-commerce Skincare, dan Community.')
p('Tagline : "Beauty meets sustainability - rawat kulit, jaga bumi."')
p()
p('Teknologi yang digunakan: Laravel 12 (PHP 8.3) sebagai backend framework, '
  'Livewire Flux v2 + TailwindCSS v4 (Vite) untuk frontend, MySQL sebagai database, '
  'Leaflet 1.9.4 untuk peta, Laravel Fortify untuk autentikasi (dukungan 2FA), '
  'Midtrans untuk pembayaran, Filament untuk admin panel, scraper berbasis Go (Golang), '
  'dan PestPHP v4 untuk testing. Katalog data bersumber dari hasil scraping kurang lebih '
  '8.750 salon (Treatwell UK).')
p()

p('3. Fitur-Fitur Web')
p()
p('Fitur Pengguna (Customer):')
p('- Pencarian salon berdasarkan treatment, lokasi, dan rating.')
p('- Kategori dan sub-kategori treatment (Hair, Face, Nails, Body, Massage).')
p('- Halaman detail salon lengkap dengan peta interaktif Leaflet.')
p('- Booking 3 langkah: Pilih Layanan, Pilih Tanggal & Jam, lalu Konfirmasi.')
p('- Pembayaran online melalui integrasi Midtrans.')
p('- Review dan rating setelah treatment.')
p('- Kode promo / diskon.')
p('- Dashboard akun: riwayat booking dan pengaturan profil.')
p('- Favorit / wishlist untuk menyimpan salon favorit.')
p('- Autentikasi login/register dengan Two-Factor Authentication (2FA).')
p()
p('Fitur Mitra (Pemilik Salon):')
p('- Pendaftaran mitra melalui halaman /mitra.')
p('- Panel Owner (Filament) untuk mengelola salon, layanan, staff, jadwal, order, dan promo.')
p()
p('Fitur Admin:')
p('- Panel Admin (Filament) untuk mengelola user, kota, kategori, persetujuan mitra, '
  'review, dan statistik.')
p()
p('Rencana Pengembangan (V2):')
p('- E-commerce skincare (katalog, cart, checkout, ongkir via api.co.id).')
p('- Lookbook dan Skincare Finder.')
p('- Empty Return (daur ulang botol menjadi poin reward).')
p('- Forum Community.')
p('- Invoice PDF.')
p()

p('4. Database - Tabel & Relasinya')
p()
p('Proyek terdiri dari kurang lebih 33 migrasi dan 15 model Eloquent.')
p()
p('Tabel Utama:')
p('- users : pengguna sistem dengan role customer / salon_owner / admin.')
p('- kota : master data kota.')
p('- kategori : kategori treatment (Hair, Face, Nails, dll).')
p('- sub_kategori : sub-kategori treatment.')
p('- salon : profil salon (nama, alamat, koordinat GPS, jam buka, rating, slug).')
p('- service : layanan/treatment per salon (nama, harga, durasi).')
p('- staff : stylist/pegawai per salon.')
p('- staff_schedule : jadwal kerja tiap staff.')
p('- salon_images : galeri foto salon.')
p('- promo : promo dan diskon.')
p('- order : transaksi booking.')
p('- order_detail : item layanan per booking (dengan kolom catatan).')
p('- pembayaran : data pembayaran (Midtrans).')
p('- review : rating dan komentar customer.')
p('- mitra_applications : pengajuan pendaftaran salon oleh calon mitra.')
p()
p('Tabel Pivot (relasi many-to-many):')
p('- staff_service : menghubungkan staff dengan service.')
p('- user_promo : menghubungkan user dengan promo (penukaran promo).')
p('- user_favourites : menghubungkan user dengan salon (favorit).')
p('- salon_kategori : menghubungkan salon dengan kategori.')
p('- salon_sub_kategori : menghubungkan salon dengan sub_kategori.')
p('- kategori_sub_kategori : menghubungkan kategori dengan sub_kategori.')
p()
p('Penjelasan Relasi Kunci:')
p('- Kota ke Salon (1:N): satu kota memiliki banyak salon.')
p('- User ke Salon (1:N): satu user (owner) dapat memiliki salon.')
p('- Salon ke Service/Staff/Foto (1:N): satu salon punya banyak layanan, staff, dan foto.')
p('- Staff dan Service (N:M): satu staff bisa mengerjakan banyak layanan dan sebaliknya, '
  'dijembatani pivot staff_service.')
p('- User ke Order ke Order Detail (1:N ke 1:N): satu user membuat banyak order, '
  'tiap order berisi banyak item layanan.')
p('- Order ke Pembayaran/Review (1:1): tiap order memiliki satu pembayaran dan '
  'dapat memiliki satu review.')
p('- User dan Promo (N:M): satu user bisa menukar banyak promo (pivot user_promo).')
p('- User dan Salon/Favorit (N:M): user menyimpan banyak salon favorit (pivot user_favourites).')
p()

p('5. Kesimpulan')
p()
p('VIYGO adalah website marketplace booking salon kecantikan berbasis Laravel 12 dengan '
  'fitur lengkap mulai dari pencarian, booking 3 langkah, pembayaran online, hingga panel '
  'admin dan owner. Struktur database dirancang relasional dengan 15 tabel inti dan 6 tabel '
  'pivot untuk menangani relasi many-to-many, menjadikan sistem skalabel untuk pengembangan '
  'ke arah platform e-commerce skincare (V2).')

out = 'docs/LAPORAN_TUGAS_BESAR.docx'
doc.save(out)
print('SAVED:', out)
