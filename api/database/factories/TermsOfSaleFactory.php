<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\TermsOfSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\TermsOfSale>
 */
final class TermsOfSaleFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = TermsOfSale::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name,
            'created_at' => fake()->optional()->dateTime(),
            'term_status' => fake()->randomNumber(1),
        ];
    }
}
