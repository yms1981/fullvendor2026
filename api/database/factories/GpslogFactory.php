<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Gpslog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Gpslog>
 */
final class GpslogFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Gpslog::class;

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
            'login' => fake()->randomNumber(),
            'infotext' => fake()->text,
            'created' => fake()->datetime(),
        ];
    }
}
