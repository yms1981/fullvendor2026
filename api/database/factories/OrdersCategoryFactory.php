<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OrdersCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\OrdersCategory>
 */
final class OrdersCategoryFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = OrdersCategory::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'order_category_id' => fake()->randomNumber(),
            'category_id' => fake()->randomNumber(),
            'catalog_id' => fake()->randomNumber(),
            'company_id' => fake()->optional()->randomNumber(),
            'order' => fake()->optional()->randomNumber(),
            'catalog_name' => fake()->word,
        ];
    }
}
