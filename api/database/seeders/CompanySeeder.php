<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Language;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::create([
            'name' => 'Compania 1',
            'language_id' => Language::where('name', 'English')->first()->language_id,
            'show_plus_taxes_label' => true,
            'plus_taxes_label' => 'Mas Impuestos',
            'ordersend_time' => 3,
            'catalogsend_time' => 4,
            'currency' => Currency::where('name','Dollar')->first()->id,
            'show_inventory' => true,
            'show_semaforo_stock' => true,
            'publish_price_list' => true,
            'sem_red_hasta' => 9,
            'sem_amarillo_desde' => 10,
            'sem_amarillo_hasta' => 20,
            'sem_verde_desde' => 21,
            'sem_red' => 'SIN STOCK',
            'sem_amarillo' => 'CONSULTAR STOCK',
            'sem_verde' => 'DISPONIBLE',
            'email_on_salesman_order' => true
        ]);

        Company::create([
            'name' => 'Compania 2',
            'language_id' => Language::where('name', 'Spanish')->first()->language_id,
            'show_plus_taxes_label' => false,
            'ordersend_time' => 5,
            'catalogsend_time' => 6,
            'currency' => Currency::where('name','Peso')->first()->id,
            'email_client_on_salesman_order' => false,
            'sell_with_inventory' => false,
        ]);

        Company::create([
            'name' => 'Compania 3',
            'language_id' => Language::where('name', 'English')->first()->language_id,
            'show_plus_taxes_label' => true,
            'plus_taxes_label' => 'Incluye impuesto',
            'ordersend_time' => 3,
            'catalogsend_time' => 4,
            'currency' => Currency::where('name','Dollar')->first()->id,
            'show_inventory' => true,
            'show_semaforo_stock' => true,
            'publish_price_list' => true,
            'sem_red_hasta' => 5,
            'sem_amarillo_desde' => 6,
            'sem_amarillo_hasta' => 30,
            'sem_verde_desde' => 31,
            'sem_red' => 'NOT IN STOCK',
            'sem_amarillo' => 'CHECK INVENTORY',
            'sem_verde' => 'AVALAIBLE',
        ]);
    }
}
