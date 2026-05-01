<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StaffSeeder extends Seeder
{
    /**
     * Seed the staff table from JSON data.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('data/staff.json'));
        $staffList = json_decode($json, true) ?? [];

        if (empty($staffList)) {
            $this->command->warn('staff.json is empty, skipping StaffSeeder.');
            return;
        }

        $chunks = array_chunk($staffList, 100);

        foreach ($chunks as $chunk) {
            $rows = array_map(function ($staff) {
                return [
                    'id_staff'    => $staff['id_staff'],
                    'id_salon'    => $staff['id_salon'],
                    'name'        => mb_substr($staff['name'], 0, 150),
                    'profile_url' => $staff['profile_url'],
                    'status'      => $staff['status'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }, $chunk);

            DB::table('staff')->insert($rows);
        }

        $this->command->info("Seeded " . count($staffList) . " staff records.");
    }
}
