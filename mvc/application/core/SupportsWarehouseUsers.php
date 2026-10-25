<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Listado y CRUD de usuarios de depósito (rol warehouse) vía API — sin SQL directo.
 */
trait SupportsWarehouseUsers
{
    /**
     * @return array<int, object>
     */
    protected function fetchWarehouseUsersFromApi(): array
    {
        $companyId = (int) $this->getCompanyId();
        $resp = $this->getDataFromApi('user', [
            'company_id' => $companyId,
            'type' => 'warehouse',
            'all' => true,
        ], false);

        $rows = [];
        if (is_object($resp) && isset($resp->data) && is_array($resp->data)) {
            $rows = $resp->data;
        } elseif (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
            $rows = $resp['data'];
        } elseif (is_array($resp)) {
            $rows = $resp;
        }

        $out = [];
        foreach ($rows as $row) {
            $out[] = $this->mapUserToLegacyWarehouseRow($row);
        }

        return $out;
    }

    protected function mapUserToLegacyWarehouseRow($user): object
    {
        $u = is_object($user) ? $user : (object) $user;
        $name = trim((string) ($u->name ?? ''));
        $parts = $name === '' ? [] : preg_split('/\s+/u', $name, 2);
        $deletedAt = $u->deleted_at ?? null;

        return (object) [
            'user_id' => (int) ($u->id ?? $u->user_id ?? 0),
            'first_name' => (string) ($u->first_name ?? ($parts[0] ?? '')),
            'last_name' => (string) ($u->last_name ?? ($parts[1] ?? '')),
            'email' => (string) ($u->email ?? ''),
            'phone_number' => (string) ($u->phone ?? $u->phone_number ?? ''),
            'profile_image' => (string) ($u->image ?? $u->profile_image ?? ''),
            'status' => ($deletedAt === null || $deletedAt === '') ? '1' : '0',
            'created' => (string) ($u->created_at ?? $u->created ?? ''),
        ];
    }

    protected function fetchWarehouseUserById(int $userId): ?object
    {
        $resp = $this->getObjectFromApi("user/{$userId}");
        if (! is_object($resp) || $this->hasError($resp)) {
            return null;
        }
        $data = $resp->data ?? $resp;

        return $this->mapUserToLegacyWarehouseRow($data);
    }

    /**
     * @param array<int, object> $users
     * @return array<int, object>
     */
    protected function filterWarehouseUsersList(array $users, $search, $status, $order_sort, $order_type): array
    {
        $search = trim((string) $search);
        if ($order_sort === '' || $order_sort === null) {
            $order_sort = 'id';
            if ($order_type === '' || $order_type === null) {
                $order_type = 'DESC';
            }
        }

        $filtered = array_values(array_filter($users, static function ($row) use ($search, $status) {
            $name = trim((string) (($row->first_name ?? '').' '.($row->last_name ?? '')));
            $email = (string) ($row->email ?? '');
            $userId = (string) ($row->user_id ?? '');
            $phone = (string) ($row->phone_number ?? '');

            if ($status !== null && $status !== '' && $status !== '3') {
                $rowStatus = (string) ($row->status ?? '1');
                if ($rowStatus !== (string) $status) {
                    return false;
                }
            }

            if ($search === '') {
                return true;
            }

            return stripos($name, $search) !== false
                || stripos($email, $search) !== false
                || stripos($userId, $search) !== false
                || stripos($phone, $search) !== false;
        }));

        $dir = strtoupper((string) $order_type) === 'ASC' ? 1 : -1;
        usort($filtered, static function ($a, $b) use ($order_sort, $dir) {
            switch ((string) $order_sort) {
                case 'email':
                    return $dir * strcasecmp((string) ($a->email ?? ''), (string) ($b->email ?? ''));
                case 'phone':
                    return $dir * strcasecmp((string) ($a->phone_number ?? ''), (string) ($b->phone_number ?? ''));
                case 'nombres_completos':
                    $na = trim((string) (($a->first_name ?? '').' '.($a->last_name ?? '')));
                    $nb = trim((string) (($b->first_name ?? '').' '.($b->last_name ?? '')));
                    return $dir * strcasecmp($na, $nb);
                case 'id':
                default:
                    return $dir * (((int) ($a->user_id ?? 0)) <=> ((int) ($b->user_id ?? 0)));
            }
        });

        return $filtered;
    }

    public function buscar_filtro_general($compId, $search, $status, $order_type, $order_sort, $show_per_page, $offset, $tipo_data)
    {
        $all = $this->filterWarehouseUsersList($this->fetchWarehouseUsersFromApi(), $search, $status, $order_sort, $order_type);
        if ($tipo_data === 'cantidad') {
            return count($all);
        }

        $limit = is_numeric($show_per_page) ? (int) $show_per_page : count($all);
        $off = is_numeric($offset) ? (int) $offset : 0;

        return array_slice($all, $off, $limit);
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    protected function buildWarehouseUserApiPayload(array $request, bool $isUpdate = false): array
    {
        $first = trim((string) ($request['first_name'] ?? $request['name'] ?? ''));
        $last = trim((string) ($request['last_name'] ?? ''));
        if ($first === '' && isset($request['name'])) {
            $parts = preg_split('/\s+/u', trim((string) $request['name']), 2);
            $first = $parts[0] ?? '';
            $last = $parts[1] ?? '';
        }

        $payload = [
            'company_id' => (int) $this->getCompanyId(),
            'role' => 'warehouse',
            'name' => trim($first.' '.$last),
            'first_name' => $first,
            'last_name' => $last,
            'email' => trim((string) ($request['email'] ?? '')),
            'phone' => trim((string) ($request['phone_number'] ?? $request['phone'] ?? '')),
        ];

        $relatedId = (int) ($request['related_id'] ?? 0);
        if ($relatedId > 0) {
            $payload['related_id'] = $relatedId;
        }

        $password = trim((string) ($request['password'] ?? ''));
        if (! $isUpdate || $password !== '') {
            $payload['password'] = $password;
        }
        if (! empty($request['images'])) {
            $payload['image'] = $request['images'];
        }
        if (! empty($request['image'])) {
            $payload['image'] = $request['image'];
        }

        return $payload;
    }

    protected function warehouseUserEmailExists(string $email): bool
    {
        $email = trim($email);
        if ($email === '') {
            return false;
        }

        $resp = $this->getDataFromApi('user', [
            'search' => $email,
            'all' => true,
        ], false);

        $rows = [];
        if (is_object($resp) && isset($resp->data) && is_array($resp->data)) {
            $rows = $resp->data;
        } elseif (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
            $rows = $resp['data'];
        }

        foreach ($rows as $row) {
            $r = is_object($row) ? $row : (object) $row;
            if (strcasecmp(trim((string) ($r->email ?? '')), $email) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $request
     * @return array<int, string>
     */
    protected function validarWarehouseUserAlta(array $request): array
    {
        $lang = $this->getModuleLang('cmp-user');
        if ($lang === []) {
            $lang = $this->HelperLanguage->user_validate();
        }

        if (empty($request['email'])) {
            return [$lang['email_is_required'] ?? 'Email required'];
        }
        if (empty($request['password'])) {
            return [$lang['password_is_required'] ?? 'Password required'];
        }
        if ($this->warehouseUserEmailExists((string) $request['email'])) {
            return [$lang['existe_email'] ?? 'Email exists'];
        }
        $relatedId = (int) ($request['related_id'] ?? 0);
        if ($relatedId < 1) {
            return [$lang['user_validation_related_required'] ?? 'Warehouse required'];
        }

        return [];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<int, string>
     */
    protected function validarWarehouseUserEdit(array $request, int $userId): array
    {
        $lang = $this->getModuleLang('cmp-user');
        if ($lang === []) {
            $lang = $this->HelperLanguage->user_validate();
        }

        if (empty($request['email'])) {
            return [$lang['email_is_required'] ?? 'Email required'];
        }

        $user = $this->fetchWarehouseUserById($userId);
        if ($user === null) {
            return [$lang['email_is_required'] ?? 'User not found'];
        }

        if (strcasecmp((string) $user->email, (string) $request['email']) !== 0
            && $this->warehouseUserEmailExists((string) $request['email'])) {
            return [$lang['existe_email'] ?? 'Email exists'];
        }

        return [];
    }

    protected function warehouseUsersPaginationConfig(string $baseUrl, int $totalRows, int $perPage): array
    {
        $langProds = $this->langmodel->getMdl('cmp-products', $this->langId);

        return [
            'base_url' => $baseUrl,
            'num_links' => 2,
            'uri_segment' => 3,
            'total_rows' => $totalRows,
            'per_page' => $perPage,
            'page_query_string' => true,
            'query_string_segment' => 'page',
            'reuse_query_string' => true,
            'full_tag_open' => '<ul class="pagination pagination-sm">',
            'full_tag_close' => '</ul>',
            'num_tag_open' => '<li>',
            'num_tag_close' => '</li>',
            'first_link' => $langProds['pagination_first'] ?? '',
            'first_tag_open' => '<li>',
            'first_tag_close' => '</li>',
            'last_link' => $langProds['pagination_last'] ?? '',
            'last_tag_open' => '<li>',
            'last_tag_close' => '</li>',
            'prev_link' => $langProds['pagination_prev'] ?? '',
            'prev_tag_open' => '<li>',
            'prev_tag_close' => '</li>',
            'next_link' => $langProds['pagination_next'] ?? '',
            'next_tag_open' => '<li>',
            'next_tag_close' => '</li>',
            'cur_tag_open' => '<li class="active"><a href="#">',
            'cur_tag_close' => '</a></li>',
            'attributes' => ['class' => 'page-link'],
        ];
    }
}
