<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Consoleconfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Consoleconfig>
 */
final class ConsoleconfigFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Consoleconfig::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'token' => fake()->unique()->sha256(),
            'company_id' => Company::factory(),
            'username' => fake()->optional()->userName,
            'password' => bcrypt(fake()->optional()->password),
            'email' => fake()->optional()->safeEmail,
            'address' => fake()->optional()->address,
            'telephone' => fake()->optional()->phoneNumber,
            'app' => fake()->optional()->word,
            'rol' => fake()->optional()->word,
        ];
    }
}
