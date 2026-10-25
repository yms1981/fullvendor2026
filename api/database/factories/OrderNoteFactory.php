<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderNoteFactory extends Factory
{
    protected $model = OrderNote::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'note' => $this->faker->paragraph,
        ];
    }
}
