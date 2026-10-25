<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CategoryCount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CategoryCount>
 */
final class CategoryCountFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = CategoryCount::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => fake()->optional()->randomNumber(),
            'items' => fake()->randomNumber(),
            'category_value' => fake()->optional()->word,
        ];
    }
}
