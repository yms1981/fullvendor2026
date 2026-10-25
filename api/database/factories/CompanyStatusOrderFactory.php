<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanyStatusOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CompanyStatusOrder>
 */
final class CompanyStatusOrderFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = CompanyStatusOrder::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'cod_status' => fake()->optional()->randomNumber(),
            'company_id' => Company::factory(),
            'name' => fake()->optional()->name,
            'email' => fake()->optional()->safeEmail,
        ];
    }
}
