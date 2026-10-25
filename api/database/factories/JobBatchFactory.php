<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\JobBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\JobBatch>
 */
final class JobBatchFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = JobBatch::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->word,
            'name' => fake()->name,
            'total_jobs' => fake()->randomNumber(),
            'pending_jobs' => fake()->randomNumber(),
            'failed_jobs' => fake()->randomNumber(),
            'failed_job_ids' => fake()->word,
            'options' => fake()->optional()->word,
            'cancelled_at' => fake()->optional()->randomNumber(),
            'created_at' => fake()->unixTime(),
            'finished_at' => fake()->optional()->randomNumber(),
        ];
    }
}
