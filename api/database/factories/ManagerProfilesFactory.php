<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\ManagerProfiles;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ManagerProfiles>
 */
final class ManagerProfilesFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = ManagerProfiles::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => \Arr::random([Company::query()->inRandomOrder()->first()?->company_id,null]),
            'name' => fake()->optional()->name,
            'created_date' => fake()->optional()->dateTime(),
            'modified_date' => fake()->optional()->dateTime(),
            'is_active' => fake()->optional()->randomNumber(),
        ];
    }
}
