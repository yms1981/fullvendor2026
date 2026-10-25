<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StatusCart;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\StatusCart>
 */
final class StatusCartFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = StatusCart::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'name_spa' => fake()->optional()->word,
            'name_eng' => fake()->optional()->word,
            'color' => fake()->optional()->word,
            'cod' => fake()->optional()->word,
            'class' => fake()->optional()->word,
            'selected_default' => fake()->optional()->word,
            'allow_toremove' => fake()->optional()->randomNumber(),
            'multiple_cart' => fake()->optional()->randomNumber(),
            'nosubmit_order' => fake()->optional()->randomNumber(),
        ];
    }
}
