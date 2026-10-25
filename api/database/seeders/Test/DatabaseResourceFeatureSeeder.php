<?php

namespace Database\Seeders\Test;

use App\Models\Company;
use App\Models\StatusOrder;
use App\Models\User;
use Database\Seeders\structure\StatusOrdersTableSeeder;
use Illuminate\Database\Seeder;

class DatabaseResourceFeatureSeeder extends Seeder
{

    public function run(): void
    {
        Company::factory(30)->create();
        User::factory(10)->create();
        if(StatusOrder::count() == 0)
            $this->call(StatusOrdersTableSeeder::class);
    }

}
