<?php

namespace App\Console\Commands;

use Database\Seeders\structure\ManagerProfileSeeder;
use Database\Seeders\structure\SellerProfileSeeder;
use Database\Seeders\structure\StatusOrdersTableSeeder;
use Illuminate\Console\Command;

class RunCustomSeeders extends Command
{
    protected $signature = 'db:seed:custom';
    protected $description = 'Run custom seeders from structure folder';

    public function handle()
    {
        (new ManagerProfileSeeder())->run();
        (new SellerProfileSeeder())->run();
        (new StatusOrdersTableSeeder())->run();
    }
}
