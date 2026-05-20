<x-layouts.public title="Account Settings">
<div class="max-w-2xl mx-auto px-6 py-10">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('akun.index') }}" class="text-[#4BA3CC] hover:underline text-sm">← Account</a>
        <h1 class="text-2xl text-[#1B2D6B]">Account Settings</h1>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('password_success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm">
            {{ session('password_success') }}
        </div>
    @endif

    <form action="{{ route('akun.pengaturan.update') }}" method="POST" class="space-y-5">
        @csrf @method('PUT')

        <div class="bg-white border border-gray-200 rounded-2xl p-6 space-y-4">
            <h2 class="font-semibold text-gray-900 border-b border-gray-100 pb-3">Personal Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name', auth()->user()->first_name) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors @error('first_name') border-red-400 @enderror" />
                    @error('first_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name', auth()->user()->last_name) }}"
                           class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors @error('last_name') border-red-400 @enderror" />
                    @error('last_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors @error('email') border-red-400 @enderror" />
                @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit"
                class="px-8 py-3 bg-[#1B2D6B] text-white font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
            Save Changes
        </button>
    </form>

    {{-- Change Password --}}
    <form action="{{ route('akun.pengaturan.password') }}" method="POST" class="space-y-5 mt-8">
        @csrf @method('PUT')

        <div class="bg-white border border-gray-200 rounded-2xl p-6 space-y-4">
            <h2 class="font-semibold text-gray-900 border-b border-gray-100 pb-3">Change Password</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Current Password</label>
                <input type="password" name="current_password"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors @error('current_password') border-red-400 @enderror" />
                @error('current_password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">New Password</label>
                <input type="password" name="password"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors @error('password') border-red-400 @enderror" />
                @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Confirm New Password</label>
                <input type="password" name="password_confirmation"
                       class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:border-[#4BA3CC] transition-colors" />
            </div>
        </div>

        <button type="submit"
                class="px-8 py-3 bg-[#1B2D6B] text-white font-semibold rounded-full hover:bg-[#4BA3CC] transition-colors">
            Update Password
        </button>
    </form>
</div>
</x-layouts.public>
