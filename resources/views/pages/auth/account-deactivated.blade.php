<x-layouts::auth :title="__('Account deactivated')">
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-center gap-3 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-500/10">
                <flux:icon.no-symbol class="size-6 text-red-600 dark:text-red-400" />
            </div>

            <flux:heading size="lg" class="text-center">
                {{ __('Page expired') }}
            </flux:heading>

            <flux:text class="text-center">
                {{ __('Your session has ended because your account has been deactivated by an administrator. If you believe this is a mistake, please contact support.') }}
            </flux:text>
        </div>

        <div class="flex flex-col gap-3">
            <flux:button :href="route('login')" variant="primary" class="w-full" wire:navigate>
                {{ __('Back to login') }}
            </flux:button>

            <flux:button :href="route('home')" variant="ghost" class="w-full" wire:navigate>
                {{ __('Return home') }}
            </flux:button>
        </div>
    </div>
</x-layouts::auth>
