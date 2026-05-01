<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Create owner users for each salon, plus an admin and test customer.
     * Each salon needs an owner user (role: salon_owner).
     */
    public function run(): void
    {
        $salonJson = file_get_contents(database_path('data/salon.json'));
        $salons = json_decode($salonJson, true);

        // 1. Admin user
        DB::table('users')->insert([
            'first_name' => 'Admin',
            'last_name'  => 'Viygo',
            'email'      => 'admin@viygo.com',
            'password'   => Hash::make('password'),
            'role'       => 'admin',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Test customer
        DB::table('users')->insert([
            'first_name' => 'Test',
            'last_name'  => 'Customer',
            'email'      => 'customer@viygo.com',
            'password'   => Hash::make('password'),
            'role'       => 'customer',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Salon owners (one per salon)
        // Pre-hash once and reuse — bcrypt is intentionally slow, calling it
        // 3000+ times would take minutes. All owners share the same default password.
        $hashedPassword = Hash::make('password');

        $ownerChunks = array_chunk($salons, 500);
        foreach ($ownerChunks as $chunk) {
            $ownerRows = [];
            foreach ($chunk as $salon) {
                $ownerRows[] = [
                    'first_name' => 'Owner',
                    'last_name'  => mb_substr($salon['nama_salon'], 0, 100),
                    'email'      => "owner_{$salon['id_salon']}@viygo.com",
                    'password'   => $hashedPassword,
                    'role'       => 'salon_owner',
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            DB::table('users')->insert($ownerRows);
        }

        $totalUsers = 2 + count($salons);
        $this->command->info("Seeded {$totalUsers} user records (1 admin + 1 customer + " . count($salons) . " owners).");
    }
}
