<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ODetailList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ODetailList>
 */
final class ODetailListFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = ODetailList::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'detail_id' => fake()->randomNumber(),
            'order_id' => fake()->randomNumber(),
            'product_id' => fake()->randomNumber(),
            'sku' => fake()->optional()->word,
            'name' => fake()->optional()->name,
            'qty' => fake()->word,
            'delivered_quantity' => fake()->word,
            'discount' => fake()->word,
            'discount_type' => fake()->randomNumber(1),
            'sale_price' => fake()->word,
            'fob_price' => fake()->word,
            'purchase_price' => fake()->word,
            'comment' => fake()->text,
            'created' => fake()->datetime(),
            'amount_sales' => fake()->optional()->word,
            'discount_amount' => fake()->optional()->word,
            'total_amount' => fake()->optional()->word,
            'amount_delivered' => fake()->optional()->word,
            'discount_delivered' => fake()->optional()->word,
            'total_delivered' => fake()->optional()->word,
        ];
    }
}
