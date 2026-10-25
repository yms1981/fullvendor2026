<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CustomerGroupTypes;
use App\Models\Company;
use App\Models\CustomerGroup;
use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CustomerGroup>
 */
final class CustomerGroupFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = CustomerGroup::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name,
            'percentage_on_price' => fake()->randomElement(CustomerGroupTypes::all()),
            'percent_price_amount' => fake()->numberBetween(1, 100),
            'created_at' => fake()->optional()->dateTime(),
            'group_status' => fake()->randomNumber(1)
        ];
    }
}
