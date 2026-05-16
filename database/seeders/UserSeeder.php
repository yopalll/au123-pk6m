<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * UserSeeder — load dari users.json hasil scraper Go.
 *
 * Format users.json:
 *   [
 *     {"id_user": 1, "first_name": "Admin", ..., "password": "password", "role": "admin", ...},
 *     {"id_user": 3, "first_name": "Owner", "last_name": "GHOST Hair", "email": "owner_1@viygo.com",
 *      "password": "password", "role": "salon_owner", "id_salon": 1, ...}
 *   ]
 *
 * Password di JSON disimpan **plain-text** (untuk debugging/visibility).
 * Saat di-insert ke MySQL, password di-HASH dgn bcrypt agar Laravel auth jalan.
 *
 * Bcrypt mahal — utk efisiensi, password yg sama di-hash sekali lalu re-use.
 *
 * Kalau users.json kosong/tidak ada → fallback bootstrap minimal:
 *   1 admin + 1 customer (password: 'password')
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/users.json');

        if (!file_exists($path) || filesize($path) <= 5) {
            $this->bootstrapMinimal();
            return;
        }

        $users = json_decode(file_get_contents($path), true) ?? [];
        if (empty($users)) {
            $this->bootstrapMinimal();
            return;
        }

        // Cache hash per password unik (bcrypt mahal!)
        $hashCache = [];
        $hashOf = function (string $plain) use (&$hashCache): string {
            if (!isset($hashCache[$plain])) {
                $hashCache[$plain] = Hash::make($plain);
            }
            return $hashCache[$plain];
        };

        $now = now();
        $rolesCount = ['admin' => 0, 'customer' => 0, 'salon_owner' => 0];

        $chunks = array_chunk($users, 500);
        foreach ($chunks as $chunk) {
            $rows = [];
            foreach ($chunk as $u) {
                $role = $u['role'] ?? 'customer';
                $rolesCount[$role] = ($rolesCount[$role] ?? 0) + 1;

                $rows[] = [
                    'id_user'    => $u['id_user'],
                    'first_name' => mb_substr($u['first_name'] ?? 'User', 0, 100),
                    'last_name'  => mb_substr($u['last_name'] ?? '', 0, 100),
                    'email'      => $u['email'],
                    // PLAIN-TEXT di JSON → HASH bcrypt di MySQL
                    'password'   => $hashOf($u['password'] ?? 'password'),
                    'role'       => $role,
                    'is_active'  => $u['is_active'] ?? true,
                    'phone_number' => $u['phone_number'] ?? null,
                    'profile_url'  => $u['profile_url'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('users')->insert($rows);
        }

        $this->command->info(sprintf(
            'Seeded %d users (admin: %d, customer: %d, salon_owner: %d) — passwords hashed with bcrypt.',
            count($users),
            $rolesCount['admin'],
            $rolesCount['customer'],
            $rolesCount['salon_owner']
        ));
    }

    /**
     * Fallback kalau users.json belum ada / kosong (mis. user belum jalankan
     * scraper). Bikin minimal 1 admin + 1 customer agar Filament admin bisa login.
     */
    private function bootstrapMinimal(): void
    {
        $now = now();
        DB::table('users')->insert([
            [
                'id_user'    => 1,
                'first_name' => 'Admin',
                'last_name'  => 'Viygo',
                'email'      => 'admin@viygo.com',
                'password'   => Hash::make('password'),
                'role'       => 'admin',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_user'    => 2,
                'first_name' => 'Test',
                'last_name'  => 'Customer',
                'email'      => 'customer@viygo.com',
                'password'   => Hash::make('password'),
                'role'       => 'customer',
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        $this->command->warn('users.json tidak ada/kosong — bootstrap minimal: admin + customer (password: "password").');
    }
}
