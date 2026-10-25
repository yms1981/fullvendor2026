<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProdSeparado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProdSeparado>
 */
final class ProdSeparadoFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = ProdSeparado::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'pro_id' => fake()->randomNumber(),
            'category_value' => fake()->optional()->word,
            'company_id' => fake()->optional()->randomNumber(),
        ];
    }
}
