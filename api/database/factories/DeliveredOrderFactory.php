<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DeliveredOrder;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\DeliveredOrder>
 */
final class DeliveredOrderFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = DeliveredOrder::class;

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
            'delivered_quantity' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'delivered_date' => fake()->datetime(),
            'delivered_pack' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
        ];
    }
}
