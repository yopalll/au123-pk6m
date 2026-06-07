<?php

namespace Database\Seeders;

use App\Constants\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminStoreSeeder extends Seeder
{
    public function run(): void
    {
        // role & is_active ada di $guarded → tidak bisa di-mass-assign.
        // firstOrCreate TANPA role, lalu set eksplisit + save().
        $user = User::firstOrCreate(
            ['email' => 'admin.store@viygo.id'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Store VIYGO',
                'password' => Hash::make('ViygoStore2026!'),
                'email_verified_at' => now(),
            ]
        );

        $user->role = UserRole::ADMIN_STORE;
        $user->is_active = true;
        $user->save();

        $this->command->info('Admin Store user berhasil dibuat: admin.store@viygo.id');
        $this->command->warn('INGAT: Ganti password default setelah deploy ke production!');
    }
}
