<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // هنا بنشغّل الـ CitySeeder
        $this->call([
            CitySeeder::class,
        ]);
    }
}
