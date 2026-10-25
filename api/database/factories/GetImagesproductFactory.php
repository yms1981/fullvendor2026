<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GetImagesproduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\GetImagesproduct>
 */
final class GetImagesproductFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = GetImagesproduct::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => fake()->optional()->randomNumber(),
            'product_id' => fake()->optional()->randomNumber(),
            'qty_images' => fake()->randomNumber(),
            'group_images' => fake()->optional()->word,
        ];
    }
}
