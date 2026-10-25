<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Catalog;
use App\Models\CatalogCart;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Language;
use App\Models\Order;
use App\Models\StatusOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WarehouseUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * @extends Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = Order::class;

    protected function status():int
    {
        $status= StatusOrder::query()->inRandomOrder()->first();
        if(!$status) $status=StatusOrder::factory()->create();
        return $status->id_status;
    }

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        $statusId=$this->status();
        return [
            'tipo_d' => Arr::random(['D','C','I']),
            'order_number' => fake()->optional()->word,
            'company_id' => Company::factory(),
            'seller_id' => Vendor::inRandomOrder()->first(),
            'language_id' => Language::factory(),
            'customer_id' => Customer::factory(),
            'payment_method' => fake()->randomNumber(1),
            'payment_status' => fake()->randomNumber(1),
            'transaction_id' => fake()->optional()->word,
            'delivery_status' => fake()->optional()->randomNumber(),
            'discount' => fake()->randomFloat(config('app.money_decimal_precision')),
            'discount_type' => fake()->randomElement(["1","2"]),
            'created' => fake()->optional()->dateTime(),
            'updated' => fake()->datetime(),
            'approved_by' => fake()->optional()->word,
            'warehouse_assign_date' => fake()->optional()->dateTime(),
            'source' => fake()->word,
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
            'portal_vendor' => fake()->optional()->randomNumber(),
            'virified_byportal' => fake()->optional()->dateTime(),
            'id_kor' => fake()->optional()->randomNumber(),
            'catalog_id' => Catalog::factory(),
            'update_bybackend' => fake()->optional()->randomNumber(),
            'discount_type_since_movil' => fake()->optional()->randomNumber(),
            'discount_movil' => fake()->optional()->randomFloat(2, 0, 999999999999999999),
            'leido' => fake()->optional()->boolean,
            'proforma' => fake()->boolean(),
            'status_id' => $statusId
        ];
    }
}
