<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImgNodatum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ImgNodatum>
 */
final class ImgNodatumFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = ImgNodatum::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'pro_id' => fake()->optional()->randomNumber(),
            'product_id' => fake()->optional()->randomNumber(),
            'sku' => fake()->optional()->word,
            'company_id' => fake()->optional()->randomNumber(),
        ];
    }
}
