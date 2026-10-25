<?php

class Form  extends Company_Controller
{
    use IsCRUD;
    public function __construct()
    {
        parent::__construct();
        $this->initCrud('order', 'company/order/', 'orders');
        $this->langArr = $this->getModuleLang('cmp-orders');
    }

    protected function getDataForView($view=null,$dataForView=[]) {
        $dataForView = parent::getDataForView($view,$dataForView);

        $pageTexts = $dataForView['pageTexts'] ?? [];
        $pageTexts = $pageTexts + $this->langArr;
        $dataForView['pageTexts'] =  $pageTexts;

        return $dataForView;
    }

    /**
     * @return array<string, array{sortname: string, phonecode: string, phonemask: string}>
     */
    protected function fetchCountryDataMap(): array
    {
        try {
            $countryList = $this->getObjectFromApi('country', [
                'props' => ['id', 'name', 'sortname', 'phonecode', 'phonemask'],
                'all' => true,
            ]);
        } catch (\Throwable $e) {
            return [];
        }

        $map = [];
        if (!is_array($countryList)) {
            return $map;
        }

        foreach ($countryList as $country) {
            $name = is_object($country) ? ($country->name ?? '') : ($country['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $map[$name] = [
                'sortname'  => is_object($country) ? ($country->sortname ?? '') : ($country['sortname'] ?? ''),
                'phonecode' => is_object($country) ? ($country->phonecode ?? '') : ($country['phonecode'] ?? ''),
                'phonemask' => is_object($country) ? ($country->phonemask ?? '') : ($country['phonemask'] ?? ''),
            ];
        }

        return $map;
    }

    /**
     * @param object|array $customer
     * @return array<string, mixed>
     */
    protected function buildCustomerFormPayload($customer): array
    {
        $row = is_object($customer) ? json_decode(json_encode($customer), true) : (array) $customer;

        $group = $row['customer_group'] ?? null;
        $groupPrice = '-';
        if (is_array($group) && !empty($group['name'])) {
            $pctOnPrice = $group['percentage_on_price'] ?? '';
            $pctAmount = $group['percent_price_amount'] ?? '';
            $groupPrice = trim($group['name'] . ' (' . $pctOnPrice . ') ' . $pctAmount . '%');
        }

        $sellerId = 0;
        $sellers = $row['sellers'] ?? [];
        if (is_array($sellers) && !empty($sellers[0]['id'])) {
            $sellerId = (int) $sellers[0]['id'];
        } elseif (is_array($sellers) && !empty($sellers[0]['seller_id'])) {
            $sellerId = (int) $sellers[0]['seller_id'];
        }

        return [
            'error'         => false,
            'customer_id'   => (int) ($row['customer_id'] ?? 0),
            'email'         => (string) ($row['email'] ?? ''),
            'phone'         => (string) ($row['phone'] ?? ''),
            'cell_phone'    => (string) ($row['cell_phone'] ?? ''),
            'tax_id'        => (string) ($row['tax_id'] ?? ''),
            'business_name' => (string) ($row['business_name'] ?? ''),
            'name'          => (string) ($row['name'] ?? ''),
            'commercial_address'  => (string) ($row['commercial_address'] ?? ''),
            'commercial_state'    => (string) ($row['commercial_state'] ?? ''),
            'commercial_city'     => (string) ($row['commercial_city'] ?? ''),
            'commercial_zone'     => (string) ($row['commercial_zone'] ?? ''),
            'commercial_zip_code' => (string) ($row['commercial_zip_code'] ?? ''),
            'commercial_country'  => (string) ($row['commercial_country'] ?? ''),
            'dispatch_address'    => (string) ($row['dispatch_address'] ?? ''),
            'dispatch_state'      => (string) ($row['dispatch_state'] ?? ''),
            'dispatch_city'       => (string) ($row['dispatch_city'] ?? ''),
            'dispatch_zone'       => (string) ($row['dispatch_zone'] ?? ''),
            'dispatch_zip_code'   => (string) ($row['dispatch_zip_code'] ?? ''),
            'dispatch_country'    => (string) ($row['dispatch_country'] ?? ''),
            'dispatch_delivery_address' => (string) ($row['dispatch_delivery_address'] ?? ''),
            'discount'      => $row['discount'] ?? 0,
            'discount_type' => '1',
            'group_price'   => $groupPrice,
            'seller_id'     => $sellerId,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param object|array|null $customer
     * @return array<string, mixed>
     */
    protected function enrichOrderFormData(array $data, $customer = null): array
    {
        $initialCustomer = null;
        if ($customer !== null) {
            $initialCustomer = $this->buildCustomerFormPayload($customer);
        }

        $data['countryDataMap'] = $this->fetchCountryDataMap();
        $data['orderFormConfig'] = [
            'searchUrl'           => company_url('orders/searchCustomer'),
            'customerFormDataUrl' => company_url('orders/customerFormData'),
            'flagsBaseUrl'        => rtrim(base_url('images/flags/'), '/') . '/',
            'initialCustomerId'   => $initialCustomer['customer_id'] ?? 0,
            'initialCustomer'     => $initialCustomer,
            'countryDataMap'      => $data['countryDataMap'],
        ];

        return $data;
    }

    /**
     * Alias snake_case para la vista de orden (status_order, order_details, etc.).
     *
     * @param object|mixed $order
     * @return object|mixed
     */
    protected function normalizeOrderForView($order)
    {
        if (!is_object($order)) {
            return $order;
        }
        if (!isset($order->status_order) && isset($order->statusOrder)) {
            $order->status_order = $order->statusOrder;
        }
        if (!isset($order->order_status_histories) && isset($order->orderStatusHistories)) {
            $order->order_status_histories = $order->orderStatusHistories;
        }
        if (!isset($order->order_status_histories) || !is_array($order->order_status_histories)) {
            $order->order_status_histories = [];
        }
        foreach ($order->order_status_histories as $h) {
            if (!is_object($h)) {
                continue;
            }
            if (!isset($h->status) && isset($h->status_id)) {
                $h->status = (object) ['color' => '#ccc', 'name_status_spanish' => '', 'name_status_english' => ''];
            }
            if (!isset($h->status) && isset($h->statusOrder)) {
                $h->status = $h->statusOrder;
            }
        }
        if (!isset($order->order_details) && isset($order->orderDetails)) {
            $order->order_details = $order->orderDetails;
        }
        if (!isset($order->order_details) || !is_array($order->order_details)) {
            $order->order_details = [];
        }
        foreach ($order->order_details as $d) {
            if (!is_object($d)) {
                continue;
            }
            if (!isset($d->total_amount) && isset($d->totalAmount)) {
                $d->total_amount = $d->totalAmount;
            }
            if (!isset($d->sale_price) && isset($d->salePrice)) {
                $d->sale_price = $d->salePrice;
            }
            $sp = $d->sale_price ?? $d->salePrice ?? 0;
            $qty = $d->qty ?? 0;
            if ((!isset($d->total_amount) || $d->total_amount === null || $d->total_amount === '') && (is_numeric($sp) && is_numeric($qty))) {
                $d->total_amount = (float) $sp * (float) $qty;
            }
        }
        if (!isset($order->total) && isset($order->total_amount)) {
            $order->total = $order->total_amount;
        }
        if (!isset($order->subtotal) && isset($order->amount)) {
            $order->subtotal = $order->amount;
        }
        if (!isset($order->descuento) && isset($order->discount)) {
            $order->descuento = $order->discount;
        }
        if (empty($order->created) || (is_string($order->created) && strpos($order->created, '1970-01-01') === 0)) {
            $order->created = $order->updated ?? $order->created ?? date('Y-m-d H:i:s');
        }

        return $order;
    }
}
