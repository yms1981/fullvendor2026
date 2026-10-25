<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Language;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseUser;
use App\Models\WordersList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\WordersList>
 */
final class WordersListFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = WordersList::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        return [
            'tipo_d' => fake()->randomElement(['D','I','C']),
            'order_id' => Order::factory(),
            'order_number' => fake()->optional()->word,
            'company_id' => Company::inRandomOrder()->first()->id,
            'user_id' => User::inRandomOrder()->first() ?? User::factory(),
            'language_id' => Language::factory(),
            'customer_id' => Customer::factory(),
            'payment_method' => fake()->randomNumber(1),
            'payment_status' => fake()->randomNumber(1),
            'transaction_id' => fake()->optional()->word,
            'order_comments' => fake()->optional()->text,
            'order_status' => fake()->randomNumber(),
            'delivery_status' => fake()->optional()->randomNumber(),
            'discount' => fake()->randomFloat(config('app.money_decimal_precision')),
            'discount_type' => fake()->randomNumber(1),
            'created' => fake()->optional()->dateTime(),
            'updated' => fake()->optional()->datetime(),
            'approved_by' => fake()->optional()->word,
            'delivery_notes' => fake()->optional()->text,
            'warehouse_id' => Warehouse::factory(),
            'warehouse_assign_date' => fake()->optional()->dateTime(),
            'source' => fake()->word,
            'internal_notes' => fake()->text,
            'token_pass' => fake()->optional()->word,
            'date_confirmed' => fake()->optional()->dateTime(),
            'send_email_order' => fake()->optional()->dateTime(),
            'date_rejected_email' => fake()->optional()->dateTime(),
            'date_expired_email' => fake()->optional()->dateTime(),
            'expiration_days' => fake()->optional()->randomNumber(),
            'amount' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'discount_a' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'total_amount' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'amount_delivered' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'discount_delivered' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'total_delivered' => fake()->optional()->randomFloat(config('app.money_decimal_precision')),
            'name_status_spanish' => fake()->optional()->word,
            'name_status_english' => fake()->optional()->word,
            'color' => fake()->optional()->word,
            'warehouse_name' => fake()->optional()->word,
        ];
    }
}
