<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Cm>
 */
final class CmFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Cm::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'pageTitle' => fake()->optional()->word,
            'pageText' => fake()->optional()->word,
            'link' => fake()->optional()->url,
            'content' => fake()->optional()->word,
            'created' => fake()->optional()->datetime(),
            'modified' => fake()->optional()->datetime(),
        ];
    }
}
