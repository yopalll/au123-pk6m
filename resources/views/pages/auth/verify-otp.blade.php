<x-layouts::auth :title="__('Verifikasi Email')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Verifikasi email kamu')"
            :description="__('Kami mengirim kode 6 digit ke ') . $email"
        />

        <!-- Status (kode terkirim ulang, dsb.) -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('otp.verify') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="code"
                :label="__('Kode OTP')"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                pattern="[0-9]*"
                required
                autofocus
                placeholder="••••••"
                class="text-center tracking-[0.5em] text-lg font-semibold"
            />

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Verifikasi') }}
            </flux:button>
        </form>

        <!-- Kirim ulang kode: tombol dengan countdown cooldown -->
        <div
            class="text-center text-sm text-zinc-600 dark:text-zinc-400"
            x-data="{
                remaining: {{ (int) $secondsUntilResend }},
                init() {
                    if (this.remaining > 0) {
                        const t = setInterval(() => {
                            this.remaining--;
                            if (this.remaining <= 0) clearInterval(t);
                        }, 1000);
                    }
                }
            }"
        >
            <span x-show="remaining > 0">
                {{ __('Belum menerima kode?') }}
                <span class="font-medium">{{ __('Kirim ulang dalam') }} <span x-text="remaining"></span>s</span>
            </span>

            <form method="POST" action="{{ route('otp.resend') }}" x-show="remaining <= 0" x-cloak>
                @csrf
                <span>{{ __('Belum menerima kode?') }}</span>
                <flux:link as="button" type="submit" class="font-medium">
                    {{ __('Kirim ulang kode') }}
                </flux:link>
            </form>
        </div>

        <div class="text-center">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:link as="button" type="submit" class="text-sm">
                    {{ __('Keluar') }}
                </flux:link>
            </form>
        </div>
    </div>
</x-layouts::auth>
