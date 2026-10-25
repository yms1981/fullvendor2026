<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CartImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CartImage>
 */
final class CartImageFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = CartImage::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => fake()->optional()->randomNumber(),
            'img_id' => fake()->randomNumber(),
            'pro_id' => fake()->randomNumber(),
        ];
    }
}
