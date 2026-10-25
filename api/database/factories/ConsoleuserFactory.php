<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Consoleuser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Consoleuser>
 */
final class ConsoleuserFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Consoleuser::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'email' => fake()->safeEmail,
            'password' => bcrypt(fake()->optional()->password),
            'rol' => fake()->optional()->word,
        ];
    }
}
