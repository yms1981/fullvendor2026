<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Detalle de cliente (JSON) para el modal de alta de usuario empresa.
 */
class Customer_detail extends Company_Controller
{
    use IsAction;

    public function run($customerId = null): void
    {
        if (! $this->input->is_ajax_request()) {
            show_404();

            return;
        }

        $id = (int) $customerId;
        if ($id < 1) {
            $this->output->set_status_header(422);
            $this->output->set_content_type('application/json');
            echo json_encode(['error' => true, 'message' => 'invalid']);

            return;
        }

        $relations = [
            'addresses.country:id,name',
            'addresses.state:id,name',
            'addresses.city:id,name',
            'customer_group:group_id,name',
        ];

        $customer = $this->getObjectFromApi('customer/' . $id, ['relations' => $relations]);

        if ($this->hasError($customer)) {
            $customer = $this->getObjectFromApi('customer/' . $id, []);
        }

        if ($this->hasError($customer)) {
            $this->output->set_status_header(404);
            $this->output->set_content_type('application/json');
            echo json_encode(['error' => true, 'message' => 'not_found']);

            return;
        }

        $flagsBaseUrl = rtrim((string) base_url(), '/') . '/images/flags/';
        $langCli = $this->getLangJsonSection('cmp-clientes');
        $cardVars = $this->buildModalCustomerCardVars($customer, $flagsBaseUrl);
        $cardVars['cardLabels'] = [
            'business' => (string) ($langCli['nombre_empresa'] ?? ''),
            'contact' => (string) ($langCli['nombre'] ?? ''),
            'address' => (string) ($langCli['col_commercial_address'] ?? ''),
            'phone' => (string) ($langCli['col_phone'] ?? $langCli['phones'] ?? ''),
            'cell' => (string) ($langCli['col_cellphone'] ?? $langCli['cell_phone'] ?? ''),
        ];
        $html = $this->load->view('company/users/customer_modal_card_body', $cardVars, true);

        $dataForClient = json_decode(json_encode($customer), true);
        if (! is_array($dataForClient)) {
            $dataForClient = [];
        }

        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        $payload = [
            'card_html' => $html,
            'html' => $html,
            'data' => $dataForClient,
        ];
        $json = json_encode($payload, $flags);
        if ($json === false) {
            $cidFallback = 0;
            if (is_object($customer)) {
                $cidFallback = (int) ($customer->customer_id ?? 0);
            } elseif (is_array($customer)) {
                $cidFallback = (int) ($customer['customer_id'] ?? 0);
            }
            $payload = [
                'card_html' => $html,
                'html' => $html,
                'data' => ['customer_id' => $cidFallback],
            ];
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        $this->output->set_content_type('application/json', 'utf-8');
        $this->output->set_output($json);
    }

    /**
     * @param object|array<string,mixed> $customer
     * @return array<string,mixed>
     */
    private function buildModalCustomerCardVars($customer, string $flagsBaseUrl): array
    {
        $c = is_array($customer) ? (object) $customer : $customer;

        $addr = $this->pickCommercialAddress($c);
        $countryData = ['sortname' => '', 'phonecode' => '', 'phonemask' => '', 'name' => ''];

        $street = '';
        $state = '';
        $city = '';
        $zone = '';
        $zip = '';
        $countryName = '';

        if ($addr !== null) {
            $street = trim(trim((string) ($this->addrProp($addr, 'street') ?? '')) . ' ' . trim((string) ($this->addrProp($addr, 'street_number') ?? '')));
            $state = $this->relationName($this->addrProp($addr, 'state'));
            $city = $this->relationName($this->addrProp($addr, 'city'));
            $zone = trim((string) ($this->addrProp($addr, 'zone') ?? ''));
            $zip = trim((string) ($this->addrProp($addr, 'zipcode') ?? ''));
            $co = $this->addrProp($addr, 'country');
            if (is_object($co) || is_array($co)) {
                $coO = is_array($co) ? (object) $co : $co;
                $countryName = trim((string) ($coO->name ?? ''));
                $countryData['name'] = $countryName;
            }
        }

        $cid = 0;
        if ($addr !== null) {
            $co = $this->addrProp($addr, 'country');
            if (is_object($co)) {
                $cid = (int) ($co->id ?? 0);
            } elseif (is_array($co)) {
                $cid = (int) ($co['id'] ?? 0);
            }
        }
        if ($cid > 0) {
            $fullCo = $this->getObjectFromApi('country/' . $cid, [
                'props' => ['id', 'name', 'sortname', 'phonecode', 'phonemask'],
            ]);
            if (! $this->hasError($fullCo) && is_object($fullCo)) {
                $countryData['name'] = trim((string) ($fullCo->name ?? $countryData['name']));
                $countryData['sortname'] = (string) ($fullCo->sortname ?? '');
                $countryData['phonecode'] = $fullCo->phonecode ?? '';
                $countryData['phonemask'] = (string) ($fullCo->phonemask ?? '');
            }
        }

        $fnUpper = static function (string $s): string {
            $s = trim($s);
            if ($s === '') {
                return '';
            }

            return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
        };

        $segments = [];
        foreach ([$street, $state, $city, $zone, $zip, $countryName] as $segment) {
            $t = trim((string) $segment);
            if ($t !== '') {
                $segments[] = $fnUpper($t);
            }
        }
        $addressFull = implode(', ', $segments);

        $phoneRaw = trim((string) ($c->phone ?? ''));
        $cellRaw = trim((string) ($c->cell_phone ?? $c->cellphone ?? ''));

        if ($countryName === '' && trim((string) ($c->commercial_country ?? '')) !== '') {
            $countryName = trim((string) $c->commercial_country);
            $countryData['name'] = $countryName;
        }

        $countryData = $this->enrichCountryDataForPhones($c, $countryData);

        return [
            'business_name' => trim((string) ($c->business_name ?? '')),
            'contact_name' => trim((string) ($c->name ?? '')),
            'address_full' => $addressFull,
            'phone_raw' => $phoneRaw,
            'cell_raw' => $cellRaw,
            'countryData' => $countryData,
            'flagsBaseUrl' => $flagsBaseUrl,
        ];
    }

    /**
     * Completa código/máscara de país para teléfonos si solo tenemos el nombre (p. ej. columna commercial_country del listado).
     *
     * @param object $c
     * @param array<string, mixed> $countryData
     * @return array<string, mixed>
     */
    private function enrichCountryDataForPhones($c, array $countryData): array
    {
        $hasCode = ($countryData['phonecode'] ?? '') !== '' && $countryData['phonecode'] !== null;
        $hasMask = trim((string) ($countryData['phonemask'] ?? '')) !== '';
        if ($hasCode || $hasMask) {
            return $countryData;
        }

        $nm = trim((string) ($countryData['name'] ?? ''));
        if ($nm === '') {
            $nm = trim((string) ($c->commercial_country ?? ''));
            if ($nm !== '') {
                $countryData['name'] = $nm;
            }
        }
        if ($nm === '') {
            return $countryData;
        }

        $list = $this->getObjectFromApi('country', [
            'all' => true,
            'props' => ['name', 'sortname', 'phonecode', 'phonemask'],
        ]);
        if ($this->hasError($list)) {
            return $countryData;
        }
        $rows = [];
        if (is_array($list)) {
            $rows = $list;
        } elseif (is_object($list) && isset($list->data) && is_array($list->data)) {
            $rows = $list->data;
        }
        foreach ($rows as $row) {
            $r = is_array($row) ? (object) $row : $row;
            $rn = trim((string) ($r->name ?? ''));
            if ($rn !== '' && strcasecmp($rn, $nm) === 0) {
                $countryData['name'] = $rn;
                $countryData['sortname'] = (string) ($r->sortname ?? '');
                $countryData['phonecode'] = $r->phonecode ?? '';
                $countryData['phonemask'] = (string) ($r->phonemask ?? '');
                break;
            }
        }

        return $countryData;
    }

    /**
     * @param object|array<string,mixed>|null $addr
     */
    private function addrProp($addr, string $key)
    {
        if ($addr === null) {
            return null;
        }
        if (is_object($addr)) {
            return $addr->{$key} ?? null;
        }
        if (is_array($addr)) {
            return $addr[$key] ?? null;
        }

        return null;
    }

    /**
     * @param object|array<string,mixed>|null $rel
     */
    private function relationName($rel): string
    {
        if ($rel === null) {
            return '';
        }
        if (is_object($rel)) {
            return trim((string) ($rel->name ?? ''));
        }
        if (is_array($rel)) {
            return trim((string) ($rel['name'] ?? ''));
        }

        return '';
    }

    /**
     * @param object|array<string,mixed> $customer
     * @return object|array<string,mixed>|null
     */
    private function pickCommercialAddress($customer)
    {
        $addresses = is_object($customer) ? ($customer->addresses ?? null) : ($customer['addresses'] ?? null);
        if ($addresses === null) {
            return null;
        }
        if (is_object($addresses) && method_exists($addresses, 'all')) {
            $addresses = $addresses->all();
        }
        if (! is_array($addresses)) {
            return null;
        }

        $fallback = null;
        foreach ($addresses as $addr) {
            $type = (string) $this->addressPivot($addr, 'type');
            if ($type === '' && is_object($addr) && isset($addr->type)) {
                $type = (string) $addr->type;
            }
            if ($type !== 'commercial') {
                continue;
            }
            $isPri = $this->addressPivot($addr, 'is_primary');
            if ($isPri === true || $isPri === 1 || $isPri === '1') {
                return $addr;
            }
            if ($fallback === null) {
                $fallback = $addr;
            }
        }

        return $fallback;
    }

    /**
     * @param object|array<string,mixed> $addr
     * @return mixed
     */
    private function addressPivot($addr, string $key)
    {
        $p = is_object($addr) ? ($addr->pivot ?? null) : ($addr['pivot'] ?? null);
        if (is_object($p)) {
            return $p->{$key} ?? null;
        }
        if (is_array($p)) {
            return $p[$key] ?? null;
        }

        return null;
    }
}
