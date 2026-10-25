<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Language;
use App\Models\TermsOfSale;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Customer>
 */
final class CustomerFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Customer::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'language_id' => Language::factory(),
            'company_id' => Company::factory(),
            'name' => fake()->name,
            'business_name' => fake()->words(3, true),
            'tax_id' => fake()->numerify('##########'),
            'term_id' => TermsOfSale::factory(),
            'group_id' => CustomerGroup::factory(),
            'email' => fake()->safeEmail,
            'phone' => fake()->numerify('##########'),
            'cell_phone' => fake()->optional()->word,
            'notes' => fake()->word,
            'dispatch_delivery_address' => fake()->optional()->word,
            'dispatch_shipping_notes' => fake()->optional()->word,
            'catalog_emails' => fake()->boolean(),
            'customer_created_at' => fake()->optional()->dateTime(),
            'customer_status' => fake()->boolean(),
            'cust_id_kor' => fake()->optional()->word,
            'id_kor' => fake()->optional()->randomNumber(),
            'seller_assign_catalog' => Vendor::factory(),
            'discount' => fake()->optional()->numberBetween(0, 100),
            'modified_at' => fake()->optional()->datetime(),
            'password' => bcrypt(fake()->optional()->password),
            'balance' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'sales' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
        ];
    }
}
