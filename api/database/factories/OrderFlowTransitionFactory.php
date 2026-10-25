<?php

namespace Database\Factories;

use App\Models\OrderFlow;
use App\Models\StatusOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderFlowTransition>
 */
class OrderFlowTransitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word,
            'origin_status_order_id' => StatusOrder::factory(),
            'destination_status_order_id' => StatusOrder::factory(),
            'order_flow_id' => OrderFlow::factory(),
        ];
    }
}
