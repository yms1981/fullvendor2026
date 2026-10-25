<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductsListadoActivo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProductsListadoActivo>
 */
final class ProductsListadoActivoFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = ProductsListadoActivo::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'pro_id' => fake()->randomNumber(),
            'product_id' => fake()->optional()->randomNumber(),
            'language_id' => fake()->randomNumber(),
            'company_id' => fake()->optional()->randomNumber(),
            'warehouse_user_id' => fake()->optional()->randomNumber(),
            'sku' => fake()->optional()->word,
            'name' => fake()->optional()->name,
            'sale_price' => fake()->word,
            'category_id' => fake()->optional()->word,
            'barcode' => fake()->optional()->word,
            'barcode_images' => fake()->optional()->word,
            'tags' => fake()->optional()->text,
            'descriptions' => fake()->optional()->text,
            'unit_type' => fake()->optional()->word,
            'stock' => fake()->word,
            'minimum_stock' => fake()->word,
            'notify_minimum_stock' => fake()->randomNumber(1),
            'currency_type' => fake()->optional()->word,
            'fob_price' => fake()->word,
            'purchase_price' => fake()->word,
            'locations' => fake()->optional()->word,
            'created_by' => fake()->optional()->word,
            'created_on' => fake()->optional()->dateTime(),
            'modified_on' => fake()->datetime(),
            'status' => fake()->randomNumber(1),
            'Img' => fake()->optional()->word,
            'imgURL' => fake()->optional()->word,
            'simgURL' => fake()->optional()->word,
            'id_kor' => fake()->optional()->randomNumber(),
            'cat_id' => fake()->optional()->word,
            'cats_id' => fake()->optional()->word,
            'sell_with_inventory' => fake()->optional()->randomNumber(),
        ];
    }
}
