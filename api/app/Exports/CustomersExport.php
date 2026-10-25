<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Columnas alineadas con la plantilla de importación (customercsv.xlsx / ConfigCrudImportSchemas::customers).
 */
class CustomersExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $customers;

    public function __construct($customers)
    {
        $this->customers = $customers;
    }

    public function collection()
    {
        return $this->customers->map(function ($customer) {
            $commercial = $customer->commercialAddresses->first();
            $dispatch = $customer->dispatchAddresses->first();

            $sellers = '';
            if ($customer->sellers) {
                $sellers = $customer->sellers
                    ->map(static function ($seller) {
                        $user = $seller->user ?? null;
                        if (is_object($user) && isset($user->name)) {
                            return (string) $user->name;
                        }

                        return '';
                    })
                    ->filter()
                    ->implode(', ');
            }

            $commercialStreet = $commercial
                ? trim((string) ($commercial->street ?? '').' '.($commercial->street_number ?? ''))
                : '';
            $dispatchStreet = $dispatch
                ? trim((string) ($dispatch->street ?? '').' '.($dispatch->street_number ?? ''))
                : '';

            $termName = is_object($customer->terms_of_sale ?? null)
                ? (string) ($customer->terms_of_sale->name ?? '')
                : '';
            $groupName = is_object($customer->customer_group ?? null)
                ? (string) ($customer->customer_group->name ?? '')
                : '';

            return [
                // 0–11: mismos índices que ConfigCrudImportSchemas::customers / CustomerCsvImportSupport
                $customer->cust_id_kor,
                $customer->name,
                $customer->business_name,
                $customer->tax_id,
                $customer->discount,
                $termName !== '' ? $termName : $customer->term_id,
                $groupName !== '' ? $groupName : $customer->group_id,
                $customer->email,
                $customer->phone,
                $customer->cell_phone,
                $sellers,
                $customer->notes,

                // 12–18 commercial
                $commercialStreet !== '' ? $commercialStreet : ($customer->commercial_address ?? null),
                $customer->commercial_delivery_address,
                $this->resolvePlaceName($commercial, 'country'),
                $this->resolvePlaceName($commercial, 'state'),
                $this->resolvePlaceName($commercial, 'city'),
                is_object($commercial) ? ($commercial->zone ?? null) : ($customer->commercial_zone ?? null),
                is_object($commercial) ? ($commercial->zipcode ?? null) : ($customer->commercial_zip_code ?? null),

                // 19–26 dispatch
                $dispatchStreet !== '' ? $dispatchStreet : ($customer->dispatch_address ?? null),
                $customer->dispatch_delivery_address,
                $this->resolvePlaceName($dispatch, 'country'),
                $this->resolvePlaceName($dispatch, 'state'),
                $this->resolvePlaceName($dispatch, 'city'),
                is_object($dispatch) ? ($dispatch->zone ?? null) : ($customer->dispatch_zone ?? null),
                is_object($dispatch) ? ($dispatch->zipcode ?? null) : ($customer->dispatch_zip_code ?? null),
                is_object($dispatch) ? ($dispatch->notes ?? null) : ($customer->dispatch_shipping_notes ?? null),

                // 27–28
                $customer->catalog_emails,
                $customer->customer_status,
            ];
        });
    }

    /**
     * Resuelve país/estado/ciudad sin romper cuando hay columnas string homónimas a la relación.
     *
     * @param  mixed  $address
     */
    private function resolvePlaceName($address, string $field): ?string
    {
        if (! is_object($address)) {
            return null;
        }

        if ($field === 'state' && isset($address->state_text) && trim((string) $address->state_text) !== '') {
            return trim((string) $address->state_text);
        }
        if ($field === 'city' && isset($address->city_text) && trim((string) $address->city_text) !== '') {
            return trim((string) $address->city_text);
        }

        $related = null;
        try {
            $related = $address->getRelationValue($field);
        } catch (\Throwable $e) {
            $related = null;
        }

        if (is_object($related) && isset($related->name)) {
            return (string) $related->name;
        }

        $attrs = $address->getAttributes();
        if (isset($attrs[$field]) && trim((string) $attrs[$field]) !== '') {
            return trim((string) $attrs[$field]);
        }
        $labelKey = $field.'_label';
        if (isset($attrs[$labelKey]) && trim((string) $attrs[$labelKey]) !== '') {
            return trim((string) $attrs[$labelKey]);
        }

        return null;
    }

    public function headings(): array
    {
        // Etiquetas alineadas con plantilla customercsv (orden = índices de importación).
        return [
            'Id ERP',
            'name',
            'business_name',
            'tax_id',
            'discount',
            'Terms of sales',
            'Group Name',
            'email',
            'phone',
            'cell_phone',
            'Seller Assigned Names',
            'notes',
            'commercial_address',
            'commercial_delivery_address',
            'commercial_country',
            'commercial_state',
            'commercial_city',
            'commercial_zone',
            'commercial_zip_code',
            'dispatch_address',
            'dispatch_delivery_address',
            'dispatch_country',
            'dispatch_state',
            'dispatch_city',
            'dispatch_zone',
            'dispatch_zip_code',
            'dispatch_shipping_notes',
            'catalog_emails',
            'customer_status',
        ];
    }
}
