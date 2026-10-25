<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProRepetido;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProRepetido>
 */
final class ProRepetidoFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = ProRepetido::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => fake()->optional()->randomNumber(),
            'sku' => fake()->optional()->word,
            'cuenta' => fake()->randomNumber(),
        ];
    }
}
