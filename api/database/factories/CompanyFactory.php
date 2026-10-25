<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CompaniesCategory;
use App\Models\Company;
use App\Models\Language;
use App\Models\ManagerProfiles;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Company>
 */
final class CompanyFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Company::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_category_id' => CompaniesCategory::factory(),
            'language_id' => Language::factory(),
            'name' => fake()->optional()->name,
            'short_name' => fake()->optional()->word,
            'email' => fake()->optional()->safeEmail,
            'password' => bcrypt(fake()->optional()->password),
            'phone_number' => fake()->optional()->phoneNumber,
            'cell_number' => fake()->optional()->word,
            'fax' => fake()->optional()->word,
            'image' => fake()->optional()->word,
            'tax_condition' => fake()->optional()->word,
            'tax_id' => fake()->optional()->word,
            'max_number_of_clients' => fake()->optional()->randomNumber(),
            'max_number_of_orders' => fake()->optional()->randomNumber(),
            'max_number_of_vendors' => fake()->optional()->randomNumber(),
            'total_price' => fake()->optional()->randomFloat(config('app.money_decimal_precision', 2)),
            'website' => fake()->optional()->word,
            'status' => fake()->randomElement([0,1]),
            'slug_url' => fake()->optional()->word,
            // keep currency nullable to avoid FK issues across migrations
            'currency' => null,
            // parent company optional; use null to avoid invalid foreign keys
            'createdby_comp_id' => null,
            'token' => fake()->optional()->text,
            'ordersend_time' => fake()->optional()->randomNumber(),
            'catalogsend_time' => fake()->optional()->randomNumber(),
            'format_id' => fake()->optional()->randomNumber(),
            'sell_with_inventory' => fake()->optional()->randomNumber(),
            'rol_id' => ManagerProfiles::factory(),
            'show_inventory' => fake()->randomNumber(),
            'show_semaforo_stock' => fake()->optional()->randomNumber(),
            'email_client_on_salesman_order' => fake()->randomNumber(1),
            'publish_price_list' => fake()->randomNumber(1),
            'show_plus_taxes_label' => fake()->randomNumber(1),
            'sellers_ids' => fake()->optional()->word,
            'sem_red_hasta' => fake()->optional()->randomNumber(),
            'sem_amarillo_desde' => fake()->optional()->randomNumber(),
            'sem_amarillo_hasta' => fake()->optional()->randomNumber(),
            'sem_verde_desde' => fake()->optional()->randomNumber(),
            'sem_verde_hasta' => fake()->optional()->randomNumber(),
            'sem_red' => fake()->optional()->word,
            'sem_amarillo' => fake()->optional()->word,
            'sem_verde' => fake()->optional()->word,
            'plus_taxes_label' => fake()->optional()->word
        ];
    }
}
