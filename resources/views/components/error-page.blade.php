@props([
    'code'    => '500',
    'title'   => 'Terjadi kesalahan',
    'message' => 'Maaf, ada yang tidak beres. Silakan coba lagi nanti.',
    'icon'    => 'sentiment_dissatisfied',
])
{{--
    Halaman error VIYGO — "Serene Floral Noir".
    Sengaja SELF-CONTAINED (tanpa @vite / komponen app / query DB) supaya tetap
    ter-render meski aplikasi sedang error (500/503) atau aset belum ter-build.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $title }} · VIYGO</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Manrope:wght@400;500;600;700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:        #111316;
            --surface:   #1e2023;
            --on:        #e2e2e6;
            --on-soft:   #b8b8c0;
            --on-faint:  #85858d;
            --primary:   #ffb68b;   /* copper */
            --primary-2: #ffdbc8;
            --on-primary:#3a1d08;
            --secondary: #a5cbea;   /* dusty blue */
            --outline:   rgba(255,255,255,0.08);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            background: var(--bg);
            color: var(--on);
            font-family: 'Manrope', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh; padding: 24px; position: relative; overflow: hidden;
        }
        body::before {
            content: ""; position: fixed; inset: 0; z-index: 0; pointer-events: none;
            background:
                linear-gradient(rgba(17,19,22,0.84), rgba(17,19,22,0.92)),
                url('{{ asset('images/floral-noir.png') }}');
            background-size: cover; background-position: center;
        }
        /* copper glow accent behind the card */
        body::after {
            content: ""; position: fixed; top: 50%; left: 50%; z-index: 0;
            width: 620px; height: 620px; transform: translate(-50%, -60%);
            background: radial-gradient(circle, rgba(255,182,139,0.14), transparent 62%);
            pointer-events: none;
        }
        .card {
            position: relative; z-index: 1;
            width: 100%; max-width: 560px; text-align: center;
            padding: 56px 40px;
            background: rgba(30,32,35,0.5);
            backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--outline);
            border-radius: 16px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.55);
        }
        .brand {
            font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700;
            letter-spacing: 0.18em; color: var(--on); margin-bottom: 36px;
        }
        .brand span { color: var(--primary); }
        .icon-wrap {
            width: 64px; height: 64px; border-radius: 9999px; margin: 0 auto 24px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,182,139,0.12);
            border: 1px solid rgba(255,182,139,0.25);
        }
        .icon-wrap .material-symbols-outlined { font-size: 32px; color: var(--primary); }
        .code {
            font-family: 'Playfair Display', serif; font-weight: 700;
            font-size: 88px; line-height: 1; letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--primary-2), var(--primary));
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent; color: var(--primary);
            margin-bottom: 8px;
        }
        .title {
            font-family: 'Playfair Display', serif; font-weight: 600;
            font-size: 26px; color: var(--on); margin-bottom: 12px;
        }
        .message {
            font-size: 15px; line-height: 1.7; color: var(--on-soft);
            max-width: 400px; margin: 0 auto 32px;
        }
        .actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 26px; border-radius: 9999px;
            font-size: 14px; font-weight: 600; text-decoration: none;
            cursor: pointer; border: 1px solid transparent; transition: all .2s;
        }
        .btn-primary { background: var(--primary); color: var(--on-primary); }
        .btn-primary:hover { background: var(--primary-2); }
        .btn-ghost { background: transparent; color: var(--secondary); border-color: rgba(165,203,234,0.4); }
        .btn-ghost:hover { background: rgba(165,203,234,0.08); }
        .btn .material-symbols-outlined { font-size: 18px; }
        .material-symbols-outlined {
            font-family: 'Material Symbols Outlined'; font-weight: normal; font-style: normal;
            line-height: 1; font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        @media (max-width: 480px) {
            .card { padding: 44px 24px; }
            .code { font-size: 68px; }
            .title { font-size: 22px; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">VIYG<span>Ö</span></div>

        <div class="icon-wrap">
            <span class="material-symbols-outlined">{{ $icon }}</span>
        </div>

        <div class="code">{{ $code }}</div>
        <h1 class="title">{{ $title }}</h1>
        <p class="message">{{ $message }}</p>

        <div class="actions">
            <a href="{{ url('/') }}" class="btn btn-primary">
                <span class="material-symbols-outlined">home</span>
                Kembali ke Beranda
            </a>
            <a href="javascript:history.back()" class="btn btn-ghost">
                <span class="material-symbols-outlined">arrow_back</span>
                Halaman Sebelumnya
            </a>
        </div>

        {{ $slot }}
    </div>
</body>
</html>
