<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Category;
use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Category>
 */
final class CategoryFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Category::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            "company_id" => Company::factory(),
            'category_name' => fake()->word,
            'category_status' => fake()->boolean(),
            'category_created_at' => fake()->optional()->dateTime(),
            'category_default' => fake()->boolean(),
        ];
    }
}
