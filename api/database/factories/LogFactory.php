<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Log>
 */
final class LogFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Log::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first() ?? User::factory(),
            'company_id' => Company::factory(),
            'log_type' => fake()->word,
            'notes' => fake()->optional()->text,
            'created_on' => fake()->optional()->dateTime(),
        ];
    }
}
