<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = City::whereHas('state.country')->inRandomOrder()->first();

        if (!$city) {
            $country = Country::factory()->create();
            $state = State::factory()->create(['country_id' => $country->id]);
            $city = City::factory()->create(['state_id' => $state->id]);
        }

        return [
            'street' => $this->faker->streetName(),
            'street_number' => $this->faker->buildingNumber(),
            'country_id' => $city->state->country->id,
            'state_id' => $city->state->id,
            'city_id' => $city->id,
            'zone' => $this->faker->optional()->words(3,true),
            'zipcode' => $this->faker->postcode(),
        ];
    }
}
