# README — Gambar untuk Halaman Statis VIYGO

> **Untuk: AI Agent Spesialis Generasi Gambar.**
> File ini berisi *manifest* gambar yang dibutuhkan untuk halaman statis VIYGO (About, Careers, Press, Help, Contact, Lookbook, dll).
> **Tugas Anda:** generate setiap gambar berikut sesuai `Prompt`, simpan ke `public/images/static/{ID}.{ext}`, dan kembalikan daftar file yang sudah dibuat.
>
> **Brand cues:** Treatwell-style — biru navy `#1B2D6B`, biru cyan aksen `#4BA3CC`, latar putih bersih, warm natural lighting, *modern editorial*, tidak boleh terlihat seperti stock photo basi. Hindari teks tertanam di gambar.

---

## Lokasi penyimpanan

Semua file ditujukan ke:
```
d:\VIYGO-GO\VIYGO\public\images\static\{id}.{ext}
```

Halaman blade saat ini menggunakan placeholder gradient (`bg-gradient-to-br from-[#E8F4FB] to-[#C5E1F0]`) dengan emoji. Setelah Anda meng-upload gambar, agent berikutnya akan mengganti placeholder dengan `<img src="{{ asset('images/static/{id}.{ext}') }}">`.

---

## Manifest Gambar

| ID Gambar | Lokasi Halaman | Dimensi | Ekstensi | Prompt Lengkap |
|-----------|----------------|---------|----------|----------------|
| `img-about-hero` | `/about` (blok hero gradient) | `1920x720` | `.webp` | Foto editorial setengah-tubuh seorang therapist wanita profesional di salon UK modern, sedang memberikan treatment wajah ke client wanita. Pencahayaan natural lembut dari jendela kiri. Latar putih bersih dengan aksen biru navy & cyan halus. Mood premium, tenang, terpercaya. Tidak ada teks. |
| `img-about-mission` | `/about` (section "Our Mission") | `800x600` | `.webp` | Close-up tangan therapist mempersiapkan alat kecantikan steril di atas meja marmer putih. Botol skincare dengan label minimalis di latar. Estetika clean Scandinavian. Cahaya soft. |
| `img-careers-team` | `/careers` (hero block) | `1920x720` | `.webp` | Foto tim startup tech UK yang bekerja di kantor terang dengan tanaman hijau. Suasana ramah, kolaboratif, beragam (mixed gender, mixed ethnicities). Latar belakang ada whiteboard dengan sketsa UI. Mood modern, *forward-looking*. |
| `img-careers-perks` | `/careers` (section perks) | `800x600` | `.webp` | Studio kantor tech kecil dengan ruang santai (sofa, kopi, tanaman). Tidak ada orang. Estetika boutique startup. |
| `img-press-room` | `/press` (header) | `1920x500` | `.webp` | Mockup laptop terbuka di meja kayu dengan halaman website news terlihat di layar (blur). Suasana morning newspaper editorial. Mood serius tapi modern. |
| `img-help-hero` | `/help` (header — opsional) | `1920x500` | `.webp` | Customer support specialist UK tersenyum hangat ke kamera, headset di kepala, di latar kantor minimalist. Mood ramah & approachable. Bukan stock photo *plastik*. |
| `img-contact-hero` | `/contact` (header — opsional) | `1920x500` | `.webp` | Telepon iPhone modern di atas meja kayu cerah dengan secangkir kopi dan sebuah notebook leather. Top-down view. Hangat, mengundang komunikasi. |
| `img-lookbook-hair-1` | `/lookbook` (kartu Hair) | `600x800` | `.webp` | Profile shot wanita UK dengan hair styling terbaru — long balayage atau modern bob. Studio lighting, *high fashion editorial*. Latar abu-abu lembut. |
| `img-lookbook-hair-2` | `/lookbook` (kartu Hair alt) | `600x800` | `.webp` | Pria UK dengan modern fade haircut, side-profile. Latar abu-abu. Mood urban editorial. |
| `img-lookbook-nails` | `/lookbook` (kartu Nails) | `600x600` | `.webp` | Close-up sepasang tangan wanita dengan nail-art chrome silver minimalis di atas meja marmer putih. Cahaya neon halus. |
| `img-lookbook-makeup` | `/lookbook` (kartu Makeup) | `600x800` | `.webp` | Close-up wajah wanita dengan smokey eye makeup dan glossy lip nude. Editorial beauty shot, latar gelap. |
| `img-lookbook-brows` | `/lookbook` (kartu Brows) | `600x800` | `.webp` | Close-up alis wanita yang baru selesai threading, ekspresi sedikit tersenyum. *Macro beauty*. Cahaya natural. |
| `img-lookbook-skin` | `/lookbook` (kartu Skin) | `600x800` | `.webp` | Wanita dengan kulit glow setelah facial, mata tertutup, ekspresi tenang. Studio beauty lighting. |
| `img-lookbook-massage` | `/lookbook` (kartu Massage) | `600x800` | `.webp` | Wanita berbaring di meja massage di salon spa, batu hangat di punggung, lighting low-key candle. Mood luxe wellness. |
| `img-treatment-files-hero` | `/treatment-files` (hero) | `1920x600` | `.webp` | Tablet menampilkan artikel beauty editorial bersama secangkir herbal tea, di samping setangkai eucalyptus. Top-down. Cahaya morning. |
| `img-treatment-files-feature` | `/treatment-files` (artikel featured) | `1200x800` | `.webp` | Tangan therapist memilih botol serum di rak skincare botanical. Atmospheric editorial mood. |
| `img-treatment-files-thumb-1` | `/treatment-files` (thumbnail Hair) | `600x400` | `.webp` | Wanita dengan rambut salon-fresh, profile shot. Editorial. |
| `img-treatment-files-thumb-2` | `/treatment-files` (thumbnail Nails) | `600x400` | `.webp` | Manicure tools tertata rapi di meja salon. |
| `img-treatment-files-thumb-3` | `/treatment-files` (thumbnail Massage) | `600x400` | `.webp` | Tangan therapist memberikan back massage. Soft focus. |
| `img-mitra-hero` | `/mitra` (hero) | `1920x720` | `.webp` | Pemilik salon wanita UK profesional tersenyum di kasir salonnya yang modern, *POS tablet* di tangan. Mood entrepreneurial, confident, premium boutique. Latar belakang interior salon real. |
| `img-mitra-benefit-1` | `/mitra` (benefit card "Reach New Customers") | `400x300` | `.webp` | Smartphone menunjukkan grafik booking yang meningkat. Top-down. |
| `img-mitra-benefit-2` | `/mitra` (benefit card "Easy Booking") | `400x300` | `.webp` | Tablet di atas meja kasir salon menunjukkan kalender booking yang penuh slot. |
| `img-gift-card-hero` | `/gift-card` (kartu visual) | `800x500` | `.webp` | *Mockup* gift card biru navy dengan logo VIYGO ringan, di atas wrapping paper kraft. Mood premium gift unboxing. Cahaya warm. |

---

## Catatan teknis

1. **Format `.webp`** dipilih untuk semua gambar besar (hero, feature). Browser modern UK (Chrome, Safari, Firefox, Edge) sudah mendukung 100%.
2. **Ukuran**: setiap dimensi di tabel sudah memperhitungkan retina (2x). Jangan men-generate >2x ukuran tabel.
3. **Aksesibilitas**: agent berikutnya wajib menambahkan `alt=""` deskriptif yang menjelaskan isi gambar dalam Bahasa Inggris.
4. **Optimasi**: kompres dengan kualitas ~75–82 untuk seimbangkan ketajaman vs ukuran file. Target <250 KB untuk hero, <80 KB untuk thumbnail.
5. **Tidak boleh ada teks tertanam** dalam gambar (selain dari elemen branding sangat halus, dan itu pun sebaiknya hindari karena menghambat lokalisasi/dark mode).
6. **Konsistensi palet**: semua hero pages sebaiknya memiliki nada warna yang dapat berdampingan dengan biru navy `#1B2D6B`. Jika gambar didominasi oleh warna hangat (kuning/jingga) yang konflik dengan navy, generate ulang.

---

## Checklist hand-off

Setelah semua gambar di-upload, AI agent berikutnya yang membaca dokumen ini WAJIB:

- [ ] Buat folder `public/images/static/` jika belum ada.
- [ ] Verifikasi setiap file ada di lokasi yang benar.
- [ ] Update halaman blade berikut: `static/about.blade.php`, `static/careers.blade.php`, `static/press.blade.php`, `static/help.blade.php`, `static/contact.blade.php`, `lookbook/index.blade.php`, `treatment-files/index.blade.php`, `mitra/index.blade.php`, `gift-card/index.blade.php` — ganti placeholder gradient/emoji dengan `<img src="{{ asset('images/static/{id}.{ext}') }}">`.
- [ ] Tambahkan attribute `loading="lazy"` ke setiap `<img>` non-hero.
- [ ] Dokumentasikan perubahan di `README-STATIC-PAGES.md`.

---

**Status manifest ini:** ✅ Lengkap untuk batch pertama. Tambahan halaman/skincare lookbook (Tugas 9) akan ditambahkan terpisah.

**Author:** Claude (Opus 4.7) — 2 Mei 2026.
