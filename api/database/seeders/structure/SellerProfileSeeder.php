<?php

namespace Database\Seeders\structure;

use App\Models\SellerProfiles;
use Illuminate\Database\Seeder;

class SellerProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createSellerProfile('Vendedor');
        $this->createSellerProfile('Vendedor catalogo', false, false, false, true);
        $this->createSellerProfile('Vendedor precio', false, false, true);
        $this->createSellerProfile('Vendedor catalogo_cliente', false, false, false, true, true);
        $this->createSellerProfile('Vendedor descuentos', true, true);
        $this->createSellerProfile('Vendedor full', true, true, true, true, true);
    }

    /**
     * @param string $name
     * @param bool $orderDiscount
     * @param bool $orderNetDiscount
     * @param bool $canChangePrice
     * @param bool $canSendCatalog
     * @param bool $canCreateCustomer
     * @return void
     */
    public function createSellerProfile(string $name, bool $orderDiscount = false, bool $orderNetDiscount = false, bool $canChangePrice = false, bool $canSendCatalog = false, bool $canCreateCustomer = false): void
    {
        SellerProfiles::create([
            'profile_name' => $name,
            'order_discount' => $orderDiscount,
            'order_net_discount' => $orderNetDiscount,
            'can_change_price' => $canChangePrice,
            'can_send_catalog' => $canSendCatalog,
            'can_create_customer' => $canCreateCustomer,
            'status' => true,
        ]);
    }
}
