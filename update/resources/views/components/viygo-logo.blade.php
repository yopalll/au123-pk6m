{{--
    Komponen: Logo VIYGO dengan Cross-Fade Otomatis
    Teknologi: Alpine.js (sudah tersedia via Livewire 3)
    Deskripsi: Menampilkan dua logo bergantian setiap 5 detik
                dengan transisi opacity yang mulus.
    Posisi    : absolute agar tidak menyebabkan layout shift.
    Penggunaan: <x-viygo-logo />
--}}

<a href="{{ route('home') }}"
   class="viygo-logo-wrap flex-shrink-0"
   x-data="viygoLogo()"
   x-init="start()"
   aria-label="VIYGO — Beranda">

    {{-- Logo 1: Icon/Favicon (biru solid) --}}
    <img src="{{ asset('images/logo1.jpeg') }}"
         alt="VIYGO Icon"
         :class="show === 0 ? 'logo-visible' : 'logo-hidden'" />

    {{-- Logo 2: Wordmark (viygö teks) --}}
    <img src="{{ asset('images/logo2.jpeg') }}"
         alt="VIYGO"
         :class="show === 1 ? 'logo-visible' : 'logo-hidden'" />
</a>

<script>
function viygoLogo() {
    return {
        show: 0,
        interval: null,
        start() {
            this.interval = setInterval(() => {
                this.show = this.show === 0 ? 1 : 0;
            }, 5000);
        },
    };
}
</script>
