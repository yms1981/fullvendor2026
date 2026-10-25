<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Company;
use App\Models\OrderCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\OrderCategory>
 */
final class OrderCategoryFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = OrderCategory::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'catalog_id' => Catalog::factory(),
            'company_id' => Company::factory(),
            'order' => fake()->optional()->randomNumber(),
            'created_at' => fake()->optional()->datetime(),
        ];
    }
}
