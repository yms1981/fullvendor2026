<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends Company_Controller
{
    /**
     * Búsqueda global AJAX para el navbar del panel company.
     */
    public function index()
    {
        $term = trim((string) $this->input->get('q'));
        if ($term === '' || mb_strlen($term) < 1) {
            return $this->jsonSuccess('', ['results' => []]);
        }

        $compId = (int) $this->getCompany();
        $lang = $this->getLangJsonSection('cmp-global-search');

        $results = array_merge(
            $this->mapProducts($compId, $term, $lang),
            $this->mapOrders($compId, $term, $lang),
            $this->mapCustomers($compId, $term, $lang),
            $this->mapSellers($compId, $term, $lang),
            $this->mapCompanyUsers($compId, $term, $lang)
        );

        return $this->jsonSuccess('', ['results' => array_slice($results, 0, 30)]);
    }

    /**
     * Detalle de un resultado para el modal de vista previa.
     */
    public function detail()
    {
        $type = trim((string) $this->input->get('type'));
        $id = (int) $this->input->get('id');
        $lang = $this->getLangJsonSection('cmp-global-search');

        if ($type === '' || $id < 1) {
            header('Content-Type: application/json');
            http_response_code(422);
            echo json_encode(['message' => (string) ($lang['modal_error'] ?? 'Error')]);
            exit;
        }

        $detail = null;
        switch ($type) {
            case 'product':
                $detail = $this->detailProduct($id, $lang);
                break;
            case 'order':
                $detail = $this->detailOrder($id, $lang);
                break;
            case 'customer':
                $detail = $this->detailCustomer($id, $lang);
                break;
            case 'seller':
                $detail = $this->detailSeller($id, $lang);
                break;
            case 'user':
                $detail = $this->detailUser($id, $lang);
                break;
        }

        if ($detail === null) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['message' => (string) ($lang['modal_error'] ?? 'Error')]);
            exit;
        }

        return $this->jsonSuccess('', $detail);
    }

    /**
     * @param array<string, mixed> $lang
     * @return list<array<string, string>>
     */
    private function mapProducts(int $companyId, string $term, array $lang): array
    {
        $rows = $this->normalizeApiList($this->getObjectFromApi('product', [
            'all' => true,
            'search' => $term,
            'props' => ['pro_id', 'name', 'sku'],
        ]));

        $section = (string) ($lang['section_products'] ?? 'Products');
        $out = [];
        foreach (array_slice($rows, 0, 6) as $row) {
            $o = is_object($row) ? $row : (object) $row;
            $id = (int) ($o->pro_id ?? 0);
            if ($id < 1) {
                continue;
            }
            $out[] = $this->resultItem(
                'product',
                $id,
                (string) ($o->name ?? ''),
                (string) ($o->sku ?? ''),
                $section,
                'mdi-package-variant',
                company_url('products/edit/' . $id)
            );
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $lang
     * @return list<array<string, string>>
     */
    private function mapOrders(int $companyId, string $term, array $lang): array
    {
        $resp = $this->getObjectFromApi('order', [
            'search' => $term,
            'per_page' => 6,
            'page' => 1,
            'props' => ['order_id', 'order_number', 'created', 'updated'],
            'relations' => ['customer', 'statusOrder'],
        ]);
        $rows = $this->normalizeApiList($resp);

        $section = (string) ($lang['section_orders'] ?? 'Orders');
        $out = [];
        foreach ($rows as $row) {
            $o = is_object($row) ? $row : (object) $row;
            $id = (int) ($o->order_id ?? 0);
            if ($id < 1) {
                continue;
            }
            $customer = is_object($o->customer ?? null) ? $o->customer : (object) [];
            $custName = trim((string) ($customer->business_name ?? ''));
            if ($custName === '') {
                $custName = (string) ($customer->name ?? '');
            }
            $status = is_object($o->statusOrder ?? null) ? $o->statusOrder : (object) [];
            $statusLabel = (string) ($status->name_status ?? $status->name_status_english ?? '');
            $out[] = $this->resultItem(
                'order',
                $id,
                (string) ($o->order_number ?? ('#' . $id)),
                trim($custName . ($statusLabel !== '' ? ' · ' . $statusLabel : '')),
                $section,
                'mdi-file-document-outline',
                company_url('orders/view/' . $id)
            );
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $lang
     * @return list<array<string, string>>
     */
    private function mapCustomers(int $companyId, string $term, array $lang): array
    {
        $rows = $this->normalizeApiList($this->getObjectFromApi('customer', [
            'all' => true,
            'search' => $term,
            'props' => ['customer_id', 'name', 'business_name', 'email'],
        ]));

        $section = (string) ($lang['section_customers'] ?? 'Customers');
        $out = [];
        foreach (array_slice($rows, 0, 5) as $row) {
            $o = is_object($row) ? $row : (object) $row;
            $id = (int) ($o->customer_id ?? 0);
            if ($id < 1) {
                continue;
            }
            $title = trim((string) ($o->business_name ?? ''));
            if ($title === '') {
                $title = (string) ($o->name ?? '');
            }
            $out[] = $this->resultItem(
                'customer',
                $id,
                $title,
                (string) ($o->email ?? ''),
                $section,
                'mdi-account-group',
                company_url('customers/edit/' . $id)
            );
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $lang
     * @return list<array<string, string>>
     */
    private function mapSellers(int $companyId, string $term, array $lang): array
    {
        $rows = $this->normalizeApiList($this->getObjectFromApi('user', [
            'type' => 'seller',
            'all' => true,
            'search' => $term,
            'props' => ['id', 'name', 'email', 'first_name', 'last_name'],
        ]));

        $section = (string) ($lang['section_sellers'] ?? 'Sellers');
        $out = [];
        foreach (array_slice($rows, 0, 4) as $row) {
            $mapped = $this->mapUserRow($row);
            $id = (int) ($mapped->user_id ?? 0);
            if ($id < 1) {
                continue;
            }
            $name = trim(($mapped->first_name ?? '') . ' ' . ($mapped->last_name ?? ''));
            $out[] = $this->resultItem(
                'seller',
                $id,
                $name,
                (string) ($mapped->email ?? ''),
                $section,
                'mdi-account-tie',
                company_url('users/edit/' . $id)
            );
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $lang
     * @return list<array<string, string>>
     */
    private function mapCompanyUsers(int $companyId, string $term, array $lang): array
    {
        $rows = $this->normalizeApiList($this->getObjectFromApi('user', [
            'type' => 'manager',
            'all' => true,
            'search' => $term,
            'props' => ['id', 'name', 'email', 'first_name', 'last_name'],
        ]));

        $section = (string) ($lang['section_users'] ?? 'Users');
        $out = [];
        foreach (array_slice($rows, 0, 4) as $row) {
            $mapped = $this->mapUserRow($row);
            $id = (int) ($mapped->user_id ?? 0);
            if ($id < 1) {
                continue;
            }
            $name = trim(($mapped->first_name ?? '') . ' ' . ($mapped->last_name ?? ''));
            $out[] = $this->resultItem(
                'user',
                $id,
                $name,
                (string) ($mapped->email ?? ''),
                $section,
                'mdi-account',
                company_url('users/edit/' . $id)
            );
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $lang
     * @return array<string, mixed>|null
     */
    private function detailProduct(int $id, array $lang): ?array
    {
        $p = $this->getObjectFromApi('product/' . $id, ['relations' => ['currency']]);
        if ($this->hasError($p)) {
            return null;
        }
        $o = is_object($p) ? $p : (object) $p;
        $currency = is_object($o->currency ?? null) ? $o->currency : (object) [];
        $symbol = (string) ($currency->symbol ?? $currency->Symbol ?? '');

        return [
            'type' => 'product',
            'title' => (string) ($o->name ?? ''),
            'url' => company_url('products/edit/' . $id),
            'fields' => $this->filterEmptyFields([
                ['label' => (string) ($lang['field_id'] ?? 'ID'), 'value' => (string) ($o->pro_id ?? $id)],
                ['label' => (string) ($lang['field_sku'] ?? 'SKU'), 'value' => (string) ($o->sku ?? '')],
                ['label' => (string) ($lang['field_name'] ?? 'Name'), 'value' => (string) ($o->name ?? '')],
                ['label' => (string) ($lang['field_price'] ?? 'Price'), 'value' => $this->formatMoney($o->sale_price ?? null, $symbol)],
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $lang
     * @return array<string, mixed>|null
     */
    private function detailOrder(int $id, array $lang): ?array
    {
        $order = $this->getObjectFromApi('order/' . $id, [
            'relations' => ['customer', 'seller.user', 'statusOrder', 'warehouse'],
        ]);
        if ($this->hasError($order)) {
            return null;
        }
        $o = is_object($order) ? $order : (object) $order;
        $customer = is_object($o->customer ?? null) ? $o->customer : (object) [];
        $custName = trim((string) ($customer->business_name ?? ''));
        if ($custName === '') {
            $custName = (string) ($customer->name ?? '');
        }
        $status = is_object($o->statusOrder ?? null) ? $o->statusOrder : (object) [];
        $seller = is_object($o->seller ?? null) ? $o->seller : (object) [];
        $sellerUser = is_object($seller->user ?? null) ? $seller->user : (object) [];
        $sellerName = trim((string) ($sellerUser->name ?? ''));
        if ($sellerName === '') {
            $sellerName = trim(((string) ($sellerUser->first_name ?? '')) . ' ' . ((string) ($sellerUser->last_name ?? '')));
        }

        return [
            'type' => 'order',
            'title' => (string) ($o->order_number ?? ('#' . $id)),
            'url' => company_url('orders/view/' . $id),
            'fields' => $this->filterEmptyFields([
                ['label' => (string) ($lang['field_order_number'] ?? 'Order'), 'value' => (string) ($o->order_number ?? '')],
                ['label' => (string) ($lang['field_status'] ?? 'Status'), 'value' => (string) ($status->name_status ?? $status->name_status_english ?? '')],
                ['label' => (string) ($lang['field_customer'] ?? 'Customer'), 'value' => $custName],
                ['label' => (string) ($lang['field_seller'] ?? 'Seller'), 'value' => $sellerName],
                ['label' => (string) ($lang['field_created'] ?? 'Created'), 'value' => $this->formatDate($o->created ?? null)],
                ['label' => (string) ($lang['field_updated'] ?? 'Updated'), 'value' => $this->formatDate($o->updated ?? null)],
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $lang
     * @return array<string, mixed>|null
     */
    private function detailCustomer(int $id, array $lang): ?array
    {
        $c = $this->getObjectFromApi('customer/' . $id);
        if ($this->hasError($c)) {
            return null;
        }
        $o = is_object($c) ? $c : (object) $c;
        $title = trim((string) ($o->business_name ?? ''));
        if ($title === '') {
            $title = (string) ($o->name ?? '');
        }

        return [
            'type' => 'customer',
            'title' => $title,
            'url' => company_url('customers/edit/' . $id),
            'fields' => $this->filterEmptyFields([
                ['label' => (string) ($lang['field_id'] ?? 'ID'), 'value' => (string) ($o->customer_id ?? $id)],
                ['label' => (string) ($lang['field_business_name'] ?? 'Business'), 'value' => (string) ($o->business_name ?? '')],
                ['label' => (string) ($lang['field_name'] ?? 'Name'), 'value' => (string) ($o->name ?? '')],
                ['label' => (string) ($lang['field_email'] ?? 'Email'), 'value' => (string) ($o->email ?? '')],
                ['label' => (string) ($lang['field_phone'] ?? 'Phone'), 'value' => (string) ($o->phone_number ?? $o->phone ?? '')],
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $lang
     * @return array<string, mixed>|null
     */
    private function detailSeller(int $id, array $lang): ?array
    {
        return $this->detailUserByType($id, $lang, 'seller', company_url('users/edit/' . $id));
    }

    /**
     * @param array<string, mixed> $lang
     * @return array<string, mixed>|null
     */
    private function detailUser(int $id, array $lang): ?array
    {
        return $this->detailUserByType($id, $lang, 'user', company_url('users/edit/' . $id));
    }

    /**
     * @param array<string, mixed> $lang
     * @return array<string, mixed>|null
     */
    private function detailUserByType(int $id, array $lang, string $type, string $url): ?array
    {
        $u = $this->getObjectFromApi('user/' . $id);
        if ($this->hasError($u)) {
            return null;
        }
        $mapped = $this->mapUserRow($u);
        $name = trim(($mapped->first_name ?? '') . ' ' . ($mapped->last_name ?? ''));

        return [
            'type' => $type,
            'title' => $name,
            'url' => $url,
            'fields' => $this->filterEmptyFields([
                ['label' => (string) ($lang['field_id'] ?? 'ID'), 'value' => (string) ($mapped->user_id ?? $id)],
                ['label' => (string) ($lang['field_name'] ?? 'Name'), 'value' => $name],
                ['label' => (string) ($lang['field_email'] ?? 'Email'), 'value' => (string) ($mapped->email ?? '')],
                ['label' => (string) ($lang['field_type'] ?? 'Type'), 'value' => (string) ($lang['section_' . ($type === 'seller' ? 'sellers' : 'users')] ?? $type)],
            ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function resultItem(
        string $type,
        int $id,
        string $title,
        string $subtitle,
        string $section,
        string $icon,
        string $url
    ): array {
        return [
            'type' => $type,
            'id' => (string) $id,
            'title' => $title,
            'subtitle' => $subtitle,
            'section' => $section,
            'icon' => $icon,
            'url' => $url,
        ];
    }

    /**
     * @param mixed $response
     * @return array<int, object|array>
     */
    private function normalizeApiList($response): array
    {
        if ($this->hasError($response)) {
            return [];
        }
        if (is_object($response) && isset($response->data) && is_array($response->data)) {
            return $response->data;
        }
        if (is_array($response)) {
            if (isset($response['data']) && is_array($response['data'])) {
                return $response['data'];
            }

            return $response;
        }

        return [];
    }

    private function mapUserRow($user): object
    {
        $u = is_object($user) ? $user : (object) $user;
        $name = trim((string) ($u->name ?? ''));
        $parts = $name === '' ? [] : preg_split('/\s+/u', $name, 2);
        $first = trim((string) ($u->first_name ?? ($parts[0] ?? '')));
        $last = trim((string) ($u->last_name ?? ($parts[1] ?? '')));
        if ($first === '' && $last === '' && $name !== '') {
            $first = $name;
        }

        return (object) [
            'user_id' => (int) ($u->id ?? $u->user_id ?? 0),
            'first_name' => $first,
            'last_name' => $last,
            'email' => (string) ($u->email ?? ''),
        ];
    }

    /**
     * @param list<array{label: string, value: string}> $fields
     * @return list<array{label: string, value: string}>
     */
    private function filterEmptyFields(array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $value = trim((string) ($field['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            $out[] = [
                'label' => (string) ($field['label'] ?? ''),
                'value' => $value,
            ];
        }

        return $out;
    }

    /**
     * @param mixed $amount
     */
    private function formatMoney($amount, string $symbol = ''): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }
        $fmt = $this->Helper->format_numeric();
        $digits = is_object($fmt) ? (int) ($fmt->digits ?? 2) : 2;
        $radix = is_object($fmt) ? (string) ($fmt->radixPoint ?? '.') : '.';
        $group = is_object($fmt) ? (string) ($fmt->groupSeparator ?? ',') : ',';

        return $symbol . number_format((float) $amount, $digits, $radix, $group);
    }

    /**
     * @param mixed $date
     */
    private function formatDate($date): string
    {
        if ($date === null || $date === '') {
            return '';
        }
        $fmt = $this->Helper->format_date();
        $phpFmt = is_object($fmt) ? (string) ($fmt->format_php ?? fv_display_date_format()) : fv_display_date_format();
        $ts = strtotime((string) $date);
        if ($ts === false) {
            return (string) $date;
        }

        return date($phpFmt, $ts);
    }
}
