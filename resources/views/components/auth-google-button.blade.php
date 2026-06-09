@props(['label' => __('Continue with Google')])

{{-- Pemisah "or" --}}
<div class="relative flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
    <span class="flex-1 h-px bg-zinc-200 dark:bg-white/10"></span>
    <span>{{ __('or') }}</span>
    <span class="flex-1 h-px bg-zinc-200 dark:bg-white/10"></span>
</div>

<a href="{{ route('google.redirect') }}"
   class="flex items-center justify-center gap-3 w-full py-2.5 rounded-full border border-zinc-300 dark:border-white/15 text-sm font-medium text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-white/5 transition">
    <svg class="w-4 h-4" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path fill="#4285F4" d="M23.52 12.27c0-.82-.07-1.6-.2-2.36H12v4.46h6.47a5.53 5.53 0 0 1-2.4 3.63v3h3.88c2.27-2.09 3.57-5.17 3.57-8.73z"/>
        <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.76-2.11-6.7-4.94H1.3v3.1A12 12 0 0 0 12 24z"/>
        <path fill="#FBBC05" d="M5.3 14.31a7.2 7.2 0 0 1 0-4.62v-3.1H1.3a12 12 0 0 0 0 10.82l4-3.1z"/>
        <path fill="#EA4335" d="M12 4.74c1.76 0 3.34.6 4.59 1.79l3.43-3.43A11.97 11.97 0 0 0 12 0 12 12 0 0 0 1.3 6.59l4 3.1C6.24 6.85 8.88 4.74 12 4.74z"/>
    </svg>
    <span>{{ $label }}</span>
</a>
