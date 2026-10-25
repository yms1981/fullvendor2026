<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Unit>
 */
final class UnitFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Unit::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'unit' => fake()->word,
            'description' => fake()->text(100),
            'company_id' => Company::factory(),
            'status' => fake()->randomElement([0, 1]),
            'decimales' => fake()->randomDigit(),
            'formato' => fake()->randomElement(['entero', 'decimal']),
        ];
    }
}
