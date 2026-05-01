@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="viygo-serif text-2xl font-semibold" style="color: var(--viygo-navy);">{{ $title }}</h1>
    @if (!empty($description))
        <p class="text-sm text-zinc-500 mt-1">{{ $description }}</p>
    @endif
</div>
