<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MailsLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\MailsLog>
 */
final class MailsLogFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = MailsLog::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'customers' => fake()->word,
            'subject' => fake()->title,
            'content' => fake()->word,
            'fecha' => fake()->dateTime(),
        ];
    }
}
