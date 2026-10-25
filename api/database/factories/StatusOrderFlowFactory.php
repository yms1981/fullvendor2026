<?php

namespace Database\Factories;

use App\Models\OrderFlow;
use App\Models\StatusOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StatusOrderFlow>
 */
class StatusOrderFlowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_flow_id' => OrderFlow::factory(),
            'status_order_id' => StatusOrder::factory(),
        ];
    }
}
