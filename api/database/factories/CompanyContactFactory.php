<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CompanyContact>
 */
final class CompanyContactFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = CompanyContact::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->optional()->name,
            'email' => fake()->optional()->safeEmail,
            'website' => fake()->optional()->word,
            'phone' => fake()->optional()->phoneNumber,
            'cell_phone' => fake()->optional()->phoneNumber,
        ];
    }
}
