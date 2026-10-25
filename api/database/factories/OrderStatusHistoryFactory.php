<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\StatusOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\OrderStatusHistory>
 */
final class OrderStatusHistoryFactory extends Factory
{
    /**
    * The name of the factory's corresponding model.
    *
    * @var string
    */
    protected $model = OrderStatusHistory::class;

    /**
    * Define the model's default state.
    *
    * @return array
    */
    public function definition(): array
    {
        /** @var StatusOrder $statusOrder */
        $statusOrder=StatusOrder::query()->count()>0?StatusOrder::query()->inRandomOrder()->first():StatusOrder::factory()->create()->first();
        return [
            'order_id' => Order::factory(),
            'company_id' => Company::factory(),
            'user_id' => User::inRandomOrder()->first(),
            'cod_status' => $statusOrder->cod_status,
        ];
    }
}
