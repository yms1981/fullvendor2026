<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImplodeImg;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ImplodeImg>
 */
final class ImplodeImgFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = ImplodeImg::class;

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
            'imploded' => fake()->optional()->word,
        ];
    }
}
