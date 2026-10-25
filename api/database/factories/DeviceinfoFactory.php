<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Deviceinfo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Deviceinfo>
 */
final class DeviceinfoFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Deviceinfo::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'deviceinfo' => fake()->unique()->word,
            'so' => fake()->word,
            'version' => fake()->word,
            'created' => fake()->date(),
            'updated_at' => fake()->optional()->datetime(),
            'versionname' => fake()->optional()->word,
        ];
    }
}
