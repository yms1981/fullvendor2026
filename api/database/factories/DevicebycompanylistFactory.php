<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Devicebycompanylist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Devicebycompanylist>
 */
final class DevicebycompanylistFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Devicebycompanylist::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'deviceinfo' => fake()->word,
            'so' => fake()->word,
            'version' => fake()->word,
            'created' => fake()->date(),
            'updated_at' => fake()->optional()->datetime(),
            'versionname' => fake()->optional()->word,
            'company_id' => fake()->optional()->randomNumber(),
        ];
    }
}
