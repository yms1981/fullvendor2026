<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StatusOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\StatusOrder>
 */
final class StatusOrderFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = StatusOrder::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'name_status_spanish' => fake()->optional()->word,
            'name_status_english' => fake()->optional()->word,
            'cod_status' => fake()->optional()->randomNumber(),
            'color' => fake()->optional()->word,
            'disabled' => fake()->optional()->randomNumber(),
            'sombrilla' => fake()->optional()->word,
            'color_icono' => fake()->optional()->word,
            'tipo_d' => fake()->optional()->word,
            'cannot_edit' => fake()->optional()->randomNumber(),
            'can_show_data_asigned' => fake()->optional()->randomNumber(),
            'send_email' => fake()->optional()->randomNumber(),
        ];
    }
}
