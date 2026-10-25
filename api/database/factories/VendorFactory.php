<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Vendor>
 */
final class VendorFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Vendor::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'phone_number' => fake()->phoneNumber,
            'cell_number' => fake()->phoneNumber,
            'fax' => fake()->word,
            'created' => fake()->dateTime(),
            'updated_at' => fake()->datetime(),
            'status' => fake()->boolean,
            'all_customers' => fake()->boolean(),
            'proforma' => fake()->boolean(),
            'email_on_salesman_order' => fake()->randomElement([0, 1]),
        ];
    }
}
