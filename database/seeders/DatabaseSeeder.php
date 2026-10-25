<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events stay on: slugs, pass numbers and org ids are filled by them.
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
