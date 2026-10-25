<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Catalog;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Catalog>
 */
final class CatalogFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Catalog::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'catalog_name' => fake()->word,
            "company_id" => Company::factory(),
            "cat_id" => Category::factory(),
            'product_name' => fake()->optional()->text,
            'status' => fake()->randomNumber(),
            'created' => fake()->optional()->dateTime(),
            'customer_emails' => fake()->optional()->word,
            'email_sent_on' => fake()->optional()->dateTime(),
            'attach' => fake()->optional()->word,
            'dayexpired' => fake()->randomNumber(),
            'default_catalog' => fake()->optional()->randomNumber(),
            'cabeza_pagina' => fake()->optional()->word,
        ];
    }
}
