<?php

namespace App\Http\Controllers\Orders;

use App\Enums\OrderStatusCodes;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InitialOrderNumber;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\StatusOrder;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait CreatesOrders
{
    protected function getInitialOrderNumber($companyId)
    {
        $initialForCompany = InitialOrderNumber::where('company_id', $companyId)->first();
        return $initialForCompany?->initial_number ?? null;
    }


    public function getNextOrderNumber($companyId, $prefix = 'ORD')
    {
        $prefixLen = strlen($prefix);
        $result = DB::table('orders')
            ->selectRaw('COALESCE(MAX(CAST(SUBSTRING(order_number, ?) AS UNSIGNED)), 0) as orderNumber', [$prefixLen + 1])
            ->where('company_id', $companyId)
            ->where('order_number', 'LIKE', $prefix . '%')
            ->whereRaw('LENGTH(order_number) >= ?', [$prefixLen + 1])
            ->first();

        $maxOrderNumber = $result && $result->orderNumber !== null ? (int) $result->orderNumber : 0;
        $initial = $this->getInitialOrderNumber($companyId);
        if ($initial !== null) {
            $maxOrderNumber = max($maxOrderNumber, (int) $initial);
        }
        return $maxOrderNumber + 1;
    }

    protected function getNextPossibleOrderNumber($companyId, $length, $prefix = 'ORD')
    {
        $nextOrderNumber = $this->getNextOrderNumber($companyId, $prefix);
        return sprintf("%0{$length}d", $nextOrderNumber);
    }

    /**
     * Generates a unique order number for the company in format ORDXXXXXX.
     * Must be called inside a DB transaction. Uses advisory lock (MySQL) to avoid duplicates under concurrency.
     *
     * @param int $companyId
     * @param int $length Number of digits for sequence after prefix (default 5)
     * @param string|null $prefix Prefix including year, e.g. 'ORD25'. If null, uses 'ORD' + current 2-digit year.
     * @return string e.g. 'ORD2500001'
     */
    protected function generateUniqueOrderNumber(int $companyId, int $length = 5, ?string $prefix = null): string
    {
        if ($prefix === null) {
            $prefix = 'ORD' . date('y');
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SELECT GET_LOCK(?, 10)', ['order_number_' . $companyId . '_' . $prefix]);
        }

        try {
            $maxAttempts = 500;
            $nextNum = $this->getNextOrderNumber($companyId, $prefix);

            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                $candidate = $prefix . str_pad((string) $nextNum, $length, '0', STR_PAD_LEFT);

                $exists = Order::where('company_id', $companyId)
                    ->where('order_number', $candidate)
                    ->exists();

                if (!$exists) {
                    return $candidate;
                }

                $nextNum++;
            }

            throw new \RuntimeException('Unable to generate unique order number for company ' . $companyId);
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SELECT RELEASE_LOCK(?)', ['order_number_' . $companyId . '_' . $prefix]);
            }
        }
    }

    /**
     * Creates an order from
     * @param $data
     * @param Customer $customer
     * @param $orderDetails
     * @param $prefixOrder
     * @return mixed
     */
    protected function createOrder($data, $customer, $orderDetails=null, $isShopingCart=false, $prefixOrder = 'ORD') {
        $company = Company::query()->find($customer->company_id);

        // seller id from sellers table
        $sellerId = $data['seller_id'] ?? null;

        $order = new Order();
        $order->customer_id = $customer->customer_id;
        $order->seller_id = $sellerId;
        $order->catalog_id = $data['catalog_id'];

        $order->uuid = $order->generate_uuid();
        $order->order_number = $this->getNextPossibleOrderNumber($customer->company_id, 6, $prefixOrder);
        $order->company_id = $customer->company_id;
        $statusCode=$isShopingCart ? OrderStatusCodes::SHOPPING : OrderStatusCodes::PLACED;

        $order->status_id = StatusOrder::getIdByCode($statusCode);

        $order->source = $data['source'] ?? "API";
        $order->created = Carbon::now();
        $order->language_id = $company->language_id;
        $order->discount = 0;

        $provenance = \App\Support\OrderAuthTokenProvenance::fromRequest(
            request(),
            (int) ($customer->company_id ?? 0) ?: null
        );
        $order->token_create = $data['token_create'] ?? $provenance['token_create'];
        $order->user_id_token = $data['user_id_token'] ?? $provenance['user_id_token'];
        $order->rol_token = $data['rol_token'] ?? $provenance['rol_token'];

        if ($isShopingCart) {
            // Token corto para caber en columna token_pass (varchar 255) y coincidir con catalog_cart.token_kor
            $order->token_pass = bin2hex(random_bytes(32));
        }

        $order->save();

        if(isset($orderDetails))
        {
            // TODO: recorrer $orderDetails y crear cada uno asociado al $order
            foreach ($orderDetails as $detail) {
                OrderDetail::create(array_merge($detail, ['order_id' => $order->order_id]));
            }
        }
        return $order;
    }

    /**
     * @param mixed $data
     * @return object|null
     */
    protected function getOpenedShoppingCart($customerId, $catalogId,$sellerId): ?object
    {
        $shoppingStatus = StatusOrder::getIdByCode(OrderStatusCodes::SHOPPING);
        return $this->getBuilder()
            ->where('customer_id', $customerId)
            ->where('seller_id', $sellerId)
            ->where('catalog_id', $catalogId)
            ->where('status_id', $shoppingStatus) //Shopping status_orders id
            ->orderBy('order_id', 'desc')
            ->first();
    }
}
