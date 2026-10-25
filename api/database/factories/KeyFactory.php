<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Key;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Key>
 */
final class KeyFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Key::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first() ?? User::factory(),
            'key' => fake()->word,
            'level' => fake()->randomNumber(),
            'ignore_limits' => fake()->randomNumber(1),
            'is_private_key' => fake()->randomNumber(1),
            'ip_addresses' => fake()->optional()->text,
            'date_created' => fake()->dateTime(),
        ];
    }
}
