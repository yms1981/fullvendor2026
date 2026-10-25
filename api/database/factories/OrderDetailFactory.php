<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\OrderDetail>
 */
final class OrderDetailFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = OrderDetail::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'qty' => fake()->randomNumber(),
            'delivered_quantity' => fake()->randomFloat(config('app.money_decimal_precision')),
            'discount' => fake()->randomFloat(config('app.money_decimal_precision')),
            'discount_type' => fake()->randomNumber(1),
            'sale_price' => fake()->randomFloat(config('app.money_decimal_precision')),
            'fob_price' => fake()->randomFloat(config('app.money_decimal_precision')),
            'sale_price_app' => fake()->randomFloat(config('app.money_decimal_precision')),
            'purchase_price' => fake()->randomFloat(config('app.money_decimal_precision')),
            'comment' => fake()->text,
            'created' => fake()->datetime(),
            'id_kor' => fake()->randomNumber(),
            'comments' => fake()->text,
            'modify_date' => fake()->optional()->datetime(),
            'fixed_pricedate' => fake()->optional()->datetime(),
            'amount_sales' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'discount_amount' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'total_amount' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'amount_delivered' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'discount_delivered' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'total_delivered' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'amount' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'discountt' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'delivered_date' => fake()->optional()->datetime(),
            'total' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'groupcustomer' => fake()->optional()->word,
            'tipolista' => fake()->optional()->word,
            'perc_price' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'salesp' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'impprice' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'totalprice' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'import_price' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'pack' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'delivered_pack' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'company_id' => Company::factory(),
        ];
    }
}
