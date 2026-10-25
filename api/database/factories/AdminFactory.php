<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Admin>
 */
final class AdminFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Admin::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'created' => fake()->optional()->datetime(),
            'edited' => fake()->datetime(),
            'status' => fake()->optional()->randomDigit(),
            'user_id' => User::factory(),
        ];
    }
}
