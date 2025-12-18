<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (City::count() === 0) {

            $now = now();

            City::insert([
                [
                    'name'       => 'Abu Dhabi',
                    'name_en'    => 'Abu Dhabi',
                    'emirate'    => 'Abu Dhabi',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name'       => 'Dubai',
                    'name_en'    => 'Dubai',
                    'emirate'    => 'Dubai',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name'       => 'Sharjah',
                    'name_en'    => 'Sharjah',
                    'emirate'    => 'Sharjah',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name'       => 'Ajman',
                    'name_en'    => 'Ajman',
                    'emirate'    => 'Ajman',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name'       => 'Al Ain',
                    'name_en'    => 'Al Ain',
                    'emirate'    => 'Abu Dhabi',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }
    }
}
