<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Inventory>
 */
final class InventoryFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Inventory::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'product_id' => Product::factory(),
            'kor_id' => fake()->optional()->randomNumber(),
            'qty' => fake()->optional()->randomNumber(),
            'sale_price' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'purchased_price' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'modify_date' => fake()->optional()->dateTime(),
        ];
    }
}
