<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\SellerProfiles;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\SellerProfiles>
 */
final class SellerProfilesFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = SellerProfiles::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'profile_name' => fake()->optional()->word,
            'company_id' => Company::factory(),
            'order_discount' => fake()->randomNumber(1),
            'order_disc_price' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'order_net_discount' => fake()->randomNumber(1),
            'order_net_disc_price' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'can_change_price' => fake()->randomNumber(1),
            'can_send_catalog' => fake()->randomNumber(1),
            'can_create_customer' => fake()->randomNumber(1),
            'created' => fake()->optional()->dateTime(),
            'status' => fake()->optional()->boolean,
            'default' => fake()->optional()->randomNumber(),
        ];
    }
}
