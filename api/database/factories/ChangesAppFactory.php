<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChangesApp;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ChangesApp>
 */
final class ChangesAppFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = ChangesApp::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'created' => fake()->dateTime(),
            'read' => fake()->randomNumber(),
            'email' => fake()->optional()->safeEmail,
            'user_id' => User::inRandomOrder()->first(),
        ];
    }
}
