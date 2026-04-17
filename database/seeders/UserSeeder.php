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
        $ownerChunks = array_chunk($salons, 100);
        foreach ($ownerChunks as $chunk) {
            $ownerRows = [];
            foreach ($chunk as $salon) {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '', $salon['nama_salon']));
                $ownerRows[] = [
                    'first_name' => 'Owner',
                    'last_name'  => $salon['nama_salon'],
                    'email'      => "owner_{$salon['id_salon']}@viygo.com",
                    'password'   => Hash::make('password'),
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
