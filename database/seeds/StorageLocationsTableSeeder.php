<?php

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StorageLocationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        DB::table('storage_locations')->insert([
            [
                'name' => 'Guest Room Closet',
                'city' => 'Gilbert',
                'state' => 'Arizona',
                'zip_code' => 85295,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Collection',
                'city' => 'Gilbert',
                'state' => 'Arizona',
                'zip_code' => 85295,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Corey Gehman',
                'city' => 'Chandler',
                'state' => 'Arizona',
                'zip_code' => 85248,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Unknown',
                'city' => 'Unknown',
                'state' => 'Unknown',
                'zip_code' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
