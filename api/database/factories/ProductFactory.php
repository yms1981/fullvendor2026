<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Language;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Product>
 */
final class ProductFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Product::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'product_id' => fake()->randomNumber(),
            "language_id" => Language::factory(),
            'company_id' => Company::factory(),
            'sku' => fake()->unique()->words(3, true),
            'name' => fake()->name,
            'sale_price' => fake()->randomFloat(config('app.money_decimal_precision')),
            'category_id' => Category::factory(),
            'barcode' => fake()->optional()->word,
            'barcode_images' => fake()->optional()->word,
            'tags' => fake()->optional()->text,
            'descriptions' => fake()->optional()->text,
            'stock' => fake()->numberBetween(0,3000),
            'minimum_stock' => fake()->randomFloat(config('app.money_decimal_precision'),0,99999),
            'notify_minimum_stock' => fake()->randomNumber(1),
            'currency_type' => fake()->optional()->word,
            'fob_price' => fake()->randomFloat(config('app.money_decimal_precision'),0,999999),
            'purchase_price' => fake()->randomFloat(config('app.money_decimal_precision'),0,999999),
            'locations' => fake()->optional()->word,
            'created_by' => fake()->optional()->word,
            'created_on' => fake()->optional()->dateTime(),
            'modified_on' => fake()->datetime(),
            'status' => fake()->boolean(),
            'Img' => fake()->optional()->word,
            'imgURL' => fake()->optional()->word,
            'simgURL' => fake()->optional()->word,
            'id_kor' => fake()->optional()->randomNumber(),
            'cat_id' => fake()->optional()->word,
            'cats_id' => fake()->optional()->word,
            'sell_with_inventory' => fake()->optional()->randomNumber(),
            'image_updated' => fake()->optional()->randomNumber(),
            'price_reduced' => fake()->randomFloat(config('app.money_decimal_precision'),0,99999999999999),
            'sem_red_hasta' => fake()->optional()->randomNumber(),
            'sem_amarillo_desde' => fake()->optional()->randomNumber(),
            'sem_amarillo_hasta' => fake()->optional()->randomNumber(),
            'sem_verde_desde' => fake()->optional()->randomNumber(),
            'sem_verde_hasta' => fake()->optional()->randomNumber(),
            'unit_id' => Unit::factory()
        ];
    }
}
