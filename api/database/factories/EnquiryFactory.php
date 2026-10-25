<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Enquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Enquiry>
 */
final class EnquiryFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Enquiry::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'name' => fake()->optional()->name,
            'email' => fake()->optional()->safeEmail,
            'phone' => fake()->optional()->phoneNumber,
            'subject' => fake()->optional()->title,
            'message' => fake()->optional()->text,
            'created' => fake()->optional()->datetime(),
            'status' => fake()->optional()->boolean,
        ];
    }
}
