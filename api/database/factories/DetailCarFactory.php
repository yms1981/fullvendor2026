<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CatalogCart;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DetailCar;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\DetailCar>
 */
final class DetailCarFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = DetailCar::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'token_car' => fake()->optional()->word,
            'customer_id' => Customer::factory(),
            'company_id' =>Company::factory(),
            'qty' => fake()->optional()->randomNumber(),
            'created' => fake()->optional()->dateTime(),
            'sale_price' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'comment' => fake()->optional()->word,
            'cart_id' => CatalogCart::factory(),
            'session_id' => fake()->optional()->randomNumber(),
            'cookie_id' => fake()->optional()->randomNumber(),
        ];
    }
}
