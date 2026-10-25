<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InventoryOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\InventoryOrder>
 */
final class InventoryOrderFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = InventoryOrder::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'tipo_d' => fake()->optional()->word,
            'order_id' => fake()->randomNumber(),
            'order_number' => fake()->optional()->word,
            'company_id' => fake()->optional()->randomNumber(),
            'user_id' => fake()->optional()->randomNumber(),
            'language_id' => fake()->randomNumber(1),
            'customer_id' => fake()->randomNumber(),
            'payment_method' => fake()->randomNumber(1),
            'payment_status' => fake()->randomNumber(1),
            'transaction_id' => fake()->optional()->word,
            'order_comments' => fake()->optional()->text,
            'order_status' => fake()->randomNumber(),
            'delivery_status' => fake()->optional()->randomNumber(),
            'discount' => fake()->randomFloat(config('app.money_decimal_precision')),
            'discount_type' => fake()->randomNumber(1),
            'created' => fake()->optional()->dateTime(),
            'updated' => fake()->datetime(),
            'approved_by' => fake()->optional()->word,
            'delivery_notes' => fake()->optional()->text,
            'warehouse_user_id' => fake()->optional()->randomNumber(),
            'warehouse_assign_date' => fake()->optional()->dateTime(),
            'source' => fake()->word,
            'internal_notes' => fake()->text,
            'token_pass' => fake()->optional()->word,
            'date_confirmed' => fake()->optional()->dateTime(),
            'send_email_order' => fake()->optional()->dateTime(),
            'date_rejected_email' => fake()->optional()->dateTime(),
            'date_expired_email' => fake()->optional()->dateTime(),
            'expiration_days' => fake()->optional()->randomNumber(),
            'amount' => fake()->optional()->word,
            'discount_a' => fake()->optional()->word,
            'total_amount' => fake()->optional()->word,
            'amount_delivered' => fake()->optional()->word,
            'discount_delivered' => fake()->optional()->word,
            'total_delivered' => fake()->optional()->word,
            'business_name' => fake()->optional()->word,
            'name' => fake()->optional()->name,
            'name_status_spanish' => fake()->optional()->word,
            'name_status_english' => fake()->optional()->word,
            'color' => fake()->optional()->word,
        ];
    }
}
