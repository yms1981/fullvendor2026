<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CatalogRequest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CatalogRequest>
 */
final class CatalogRequestFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = CatalogRequest::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->first() ?? User::factory(),
            'customer_id' => Customer::factory(),
            'descriptions' => fake()->optional()->word,
            'created' => fake()->optional()->dateTime(),
            'updated' => fake()->datetime(),
            'status' => fake()->randomNumber(1),
        ];
    }
}
