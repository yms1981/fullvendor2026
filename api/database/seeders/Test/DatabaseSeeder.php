<?php

namespace Database\Seeders\Test;

use App\Models\Company;
use App\Models\StatusOrder;
use App\Models\User;
use Database\Seeders\structure\StatusOrdersTableSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    public function run():void
    {
        $users = User::factory(10)->create();
        $companies = Company::factory(30)->create();
        if(StatusOrder::count() == 0)
            $this->call(StatusOrdersTableSeeder::class);

        $users->each(function ($user) use ($companies) {
            $user->companies()->attach($companies->random(rand(1, 3))->pluck('id'));
        });
    }

}
