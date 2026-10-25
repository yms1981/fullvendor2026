<?php

namespace App\Http\Resources;

use App\Enums\OrderStatusCodes;
use App\Models\Currency;
use App\Models\Customer;
use App\Support\DisplayDateFormat;
use App\Models\FormatNumeric;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceOrderMailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customer = $this->customer;
        $currency = $this->company->currencyModel()->sole() ?? Currency::default();
        $formatNumeric = $this->company->format_numeric ?? FormatNumeric::default();
        $formatDate = DisplayDateFormat::resolvePhpFormat();
        $expirationDays = (int) ($request->input('expiration_days') ?: $this->expiration_days ?: 7);
        $codStatus = $this->relationLoaded('statusOrder')
            ? $this->statusOrder?->cod_status
            : null;

        return [
            'order_number' => $this->order_number,
            'descriptions' => $request->get('descriptions', $this->getDescriptionDefault($customer,$formatDate)),
            'expiration_date' => Carbon::now()->addDays($expirationDays)->format($formatDate),

            'address_dispatch' =>$this->address?->fullAddress(), //if null, use another address?
            'order_details' => $this->order_details->map(function ($detail) use ($formatNumeric) {
                $product = $detail->product;
                $image = $product?->images?->first()?->ImgURL ?? null;

                return [
                    'price' => $formatNumeric->formatNumber($detail->sale_price), //formated
                    'quantity' => $detail->qty,
                    'comments' => $detail->comments,
                    'product' => [
                        'name' => $product?->name,
                        'sku' => $product?->sku,
                        'image' => $image,
                    ],
                ];
            }),
            'total_info' => $this->getTotals(true), //formated
            'company' => [
                'name' => $this->company->name,
                'email' => $this->company->email,
                'phone' => $this->company->phone_number,
                'address' => $this->company->address,
                'image' => $this->company->image ?? null,
                'currencySymbol' => $currency->symbol,
                'plusTaxesLabel' => ($this->company->show_plus_taxes_label && $this->company->plus_taxes_label)
                    ? $this->company->plus_taxes_label
                    : 'Más Impuestos',
            ],
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->commercial_address, //es esta address ?
                'dispatch_notes' => $customer->dispatch_shipping_notes,
            ],
            'seller' => [
                'name' => $this->seller->user->name ?? null,
                'phone' => $this->seller->phone_number ?? null,
            ],
            'show_buttons' => (int) $codStatus !== OrderStatusCodes::APPROVED->value,
            'show_information' => $request->boolean('show_information'),
            'hide_price' => $request->boolean('hide_price'),
            'confirm_url' => $request->input('confirm_url', ''),
            'reject_url' => $request->input('reject_url', ''),
        ];
    }

    private function getDescriptionDefault(Customer $customer, $formatDate): string
    {
        return sprintf(
                __('cmp-orders.description_defecto_order'),
                $customer->name . ' (' . $customer->business_name . ')',
                Carbon::parse($this->created)->format($formatDate)
            );
    }
}
