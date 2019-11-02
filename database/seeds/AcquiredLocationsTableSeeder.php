<?php

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcquiredLocationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        DB::table('acquired_locations')->insert([
            ['name' => 'Target', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'LEGO Store', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Walmart', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Unknown', 'created_at' => $now, 'updated_at' => $now]
        ]);
    }
}
