<?php

namespace Database\Seeders;

use Database\Seeders\structure\StatusOrdersTableSeeder;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(FullvendorMVCSeeder::class);
        $this->call(StatusOrdersTableSeeder::class);
        $this->call(OrderFlowSeeder::class);
    }
}
