<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AppUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AppUpdate>
 */
final class AppUpdateFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = AppUpdate::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'fecha' => fake()->optional()->dateTime(),
            'company_id' => \App\Models\Company::factory(),
            'updatenumber' => fake()->randomNumber()
        ];
    }
}
