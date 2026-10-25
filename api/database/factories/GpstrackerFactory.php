<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Gpstracker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Gpstracker>
 */
final class GpstrackerFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Gpstracker::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => fake()->randomNumber(),
            'user_id' => fake()->randomNumber(),
            'longitude' => fake()->longitude,
            'latitude' => fake()->latitude,
            'created' => fake()->datetime(),
            'login' => fake()->optional()->randomNumber(),
            'address' => fake()->optional()->address,
            'deviceinfo' => fake()->optional()->text,
        ];
    }
}
