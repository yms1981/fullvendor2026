<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Navbar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Navbar>
 */
final class NavbarFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Navbar::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'url_nav' => fake()->optional()->word,
            'identify' => fake()->optional()->word,
            'name_eng' => fake()->optional()->word,
            'name_spa' => fake()->optional()->word,
            'father_id' => fake()->optional()->randomNumber(),
            'icon' => fake()->optional()->word,
            'order' => fake()->optional()->randomNumber(),
            'config' => fake()->optional()->randomNumber(),
            'is_active_to_rol' => fake()->optional()->randomNumber(),
        ];
    }
}
