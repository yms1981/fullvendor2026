<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DevicesCompanytemp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\DevicesCompanytemp>
 */
final class DevicesCompanytempFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = DevicesCompanytemp::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => fake()->randomNumber(),
            'deviceinfo' => fake()->optional()->text,
        ];
    }
}
