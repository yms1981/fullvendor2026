<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Banner>
 */
final class BannerFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Banner::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'bannerHeading' => fake()->optional()->text,
            'bannerDescription' => fake()->optional()->text,
            'bannerImage' => fake()->optional()->text,
        ];
    }
}
