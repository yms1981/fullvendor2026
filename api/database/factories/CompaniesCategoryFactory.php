<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CompaniesCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CompaniesCategory>
 */
final class CompaniesCategoryFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = CompaniesCategory::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'category_name' => fake()->optional()->word,
            'category_status' => fake()->optional()->randomNumber(1),
            'category_created_at' => fake()->optional()->dateTime(),
        ];
    }
}
