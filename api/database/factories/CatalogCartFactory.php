<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatusCodes;
use App\Models\Catalog;
use App\Models\CatalogCart;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\StatusOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CatalogCart>
 */
final class CatalogCartFactory extends OrderFactory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CatalogCart::class;

    protected function status(): int
    {
        $shoppingStatus =StatusOrder::getIdByCode(OrderStatusCodes::SHOPPING);
        return $shoppingStatus!==false ? $shoppingStatus :StatusOrder::query()->inRandomOrder()->first()->id_status;
    }
}
