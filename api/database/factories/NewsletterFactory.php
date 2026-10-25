<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Newsletter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Newsletter>
 */
final class NewsletterFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Newsletter::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'email' => fake()->optional()->safeEmail,
            'created' => fake()->optional()->datetime(),
            'status' => fake()->optional()->randomNumber(1),
        ];
    }
}
