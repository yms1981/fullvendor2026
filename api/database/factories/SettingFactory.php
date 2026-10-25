<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Setting>
 */
final class SettingFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Setting::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'logo' => fake()->optional()->word,
            'favicon' => fake()->optional()->word,
            'title' => fake()->optional()->title,
            'address' => fake()->optional()->address,
            'email' => fake()->optional()->safeEmail,
            'phone' => fake()->optional()->phoneNumber,
            'website' => fake()->optional()->word,
            'facebook' => fake()->optional()->word,
            'twitter' => fake()->optional()->word,
            'linkedin' => fake()->optional()->word,
            'instagram' => fake()->optional()->word,
        ];
    }
}
