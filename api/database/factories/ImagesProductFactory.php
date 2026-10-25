<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImagesProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ImagesProduct>
 */
final class ImagesProductFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = ImagesProduct::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'img_id' => fake()->optional()->randomNumber(),
            'images' => fake()->optional()->word,
            'created' => fake()->optional()->dateTime(),
            'pro_id' => fake()->randomNumber(),
            'company_id' => fake()->optional()->randomNumber(),
            'imageBlob' => fake()->optional()->word,
        ];
    }
}
