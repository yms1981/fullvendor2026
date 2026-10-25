<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CategoryList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CategoryList>
 */
final class CategoryListFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = CategoryList::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'order_id' => fake()->optional()->randomNumber(),
            'cat_id' => fake()->optional()->randomNumber(),
            'category_id' => fake()->optional()->randomNumber(),
            'language_id' => fake()->optional()->randomNumber(),
            'company_id' => fake()->optional()->randomNumber(),
            'category_name' => fake()->optional()->word,
            'images' => fake()->optional()->word,
            'category_status' => fake()->optional()->randomNumber(),
            'category_created_at' => fake()->optional()->dateTime(),
            'id_kor' => fake()->optional()->randomNumber(),
            'category_default' => fake()->optional()->randomNumber(),
            'items' => fake()->randomNumber(),
        ];
    }
}
