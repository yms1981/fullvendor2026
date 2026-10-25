<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (! function_exists('fv_crud_row_btn_class')) {
    /**
     * Clases estándar para botones de acción en filas CRUD.
     *
     * @param array<string, mixed>|object|null $itemAction
     */
    function fv_crud_row_btn_class($itemAction, string $extra = ''): string
    {
        $action = is_object($itemAction) ? (array) $itemAction : (is_array($itemAction) ? $itemAction : []);
        $rawClass = (string) ($action['buttonClass'] ?? 'primary');
        $colorClass = 'btn-primary';
        if ($rawClass !== '') {
            if (strpos($rawClass, 'btn-') === 0) {
                $colorClass = $rawClass;
            } elseif (preg_match('/\b(primary|warning|info|danger|success|secondary)\b/', $rawClass, $matches)) {
                $colorClass = 'btn-' . $matches[1];
            }
        }

        return trim('btn fv-crud-btn fv-crud-btn--row btn-sm ' . $colorClass . ' ' . $extra);
    }
}

if (! function_exists('fv_page_heading_text')) {
    /**
     * Normaliza el texto visible de un título de página/formulario.
     */
    function fv_page_heading_text($text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        return function_exists('mb_strtoupper') ? mb_strtoupper($text, 'UTF-8') : strtoupper($text);
    }
}

if (! function_exists('fv_page_heading_markup')) {
    /**
     * Título del card de formulario: última palabra en acento.
     */
    function fv_page_heading_markup($text): string
    {
        $text = fv_page_heading_text($text);
        if ($text === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $text) ?: [];
        $last = (string) array_pop($parts);
        $lead = trim(implode(' ', $parts));
        $html = '';
        if ($lead !== '') {
            $html .= htmlspecialchars($lead, ENT_QUOTES, 'UTF-8') . ' ';
        }

        return $html . '<span class="fv-form-hero__accent">' . htmlspecialchars($last, ENT_QUOTES, 'UTF-8') . '</span>';
    }
}

if (! function_exists('fv_company_page_icon')) {
    /**
     * Icono representativo según la ruta MVC company (o valor explícito).
     */
    function fv_company_page_icon(?string $explicit = null): string
    {
        if ($explicit !== null && $explicit !== '') {
            return $explicit;
        }

        $segments = [];
        if (function_exists('get_instance')) {
            $CI = get_instance();
            if (isset($CI->uri) && is_object($CI->uri)) {
                for ($i = 2; $i <= 6; $i++) {
                    $segment = strtolower((string) $CI->uri->segment($i));
                    if ($segment !== '') {
                        $segments[] = $segment;
                    }
                }
            }
        }

        $map = [
            'dashboard' => 'fa-solid fa-chart-simple',
            'products' => 'fa-solid fa-box',
            'product' => 'fa-solid fa-box',
            'inventory' => 'fa-solid fa-clipboard-list',
            'inventario' => 'fa-solid fa-clipboard-list',
            'customers' => 'fa-solid fa-sack-dollar',
            'customer' => 'fa-solid fa-user',
            'orders' => 'fa-solid fa-paper-plane',
            'order' => 'fa-solid fa-file-invoice',
            'warehouses' => 'fa-solid fa-warehouse',
            'warehouse' => 'fa-solid fa-warehouse',
            'catalog' => 'fa-solid fa-file-circle-plus',
            'units' => 'fa-solid fa-ruler-combined',
            'terms' => 'fa-solid fa-file-invoice-dollar',
            'groups' => 'fa-solid fa-users',
            'users' => 'fa-solid fa-user-lock',
            'userprofile' => 'fa-solid fa-id-card',
            'sellerprofile' => 'fa-solid fa-id-card',
            'seller' => 'fa-solid fa-user-tie',
            'currencies' => 'fa-solid fa-coins',
            'currency' => 'fa-solid fa-coins',
            'logs' => 'fa-solid fa-database',
            'import' => 'fa-solid fa-file-import',
            'profile' => 'fa-solid fa-gear',
            'cart' => 'fa-solid fa-cart-shopping',
            'categories' => 'fa-solid fa-tags',
            'categorias' => 'fa-solid fa-tags',
            'country' => 'fa-solid fa-globe',
            'state' => 'fa-solid fa-map',
            'city' => 'fa-solid fa-city',
            'search' => 'fa-solid fa-magnifying-glass',
            'logo' => 'fa-solid fa-image',
            'email' => 'fa-solid fa-envelope',
            'sms' => 'fa-solid fa-comment-sms',
            'lists' => 'fa-solid fa-list',
            'add' => 'fa-solid fa-plus',
            'edit' => 'fa-solid fa-pen-to-square',
            'view' => 'fa-solid fa-eye',
            'export' => 'fa-solid fa-file-export',
            'csvimport' => 'fa-solid fa-file-import',
        ];

        foreach (array_reverse($segments) as $segment) {
            if (isset($map[$segment])) {
                return $map[$segment];
            }
        }

        return 'fa-solid fa-layer-group';
    }
}

if (! function_exists('fv_company_nav_active_key')) {
    /**
     * Clave del ítem activo en el navbar principal del panel company (según la URL).
     */
    function fv_company_nav_active_key(): ?string
    {
        if (! function_exists('get_instance')) {
            return null;
        }

        $CI = get_instance();
        if (! isset($CI->uri) || ! is_object($CI->uri)) {
            return null;
        }

        $s2 = strtolower((string) $CI->uri->segment(2));
        $s3 = strtolower((string) $CI->uri->segment(3));

        if ($s2 === '' || in_array($s2, ['dashboard', 'home'], true)) {
            return 'dashboard';
        }

        if (in_array($s2, ['customers', 'customer'], true)) {
            return 'customer';
        }

        if (in_array($s2, ['products', 'categories', 'categorias'], true)) {
            return 'products';
        }

        if (in_array($s2, ['orders', 'order', 'cart'], true)) {
            return 'order';
        }

        if ($s2 === 'warehouse' && in_array($s3, ['orders', 'warehouse_orderview', 'orderlists'], true)) {
            return 'order';
        }

        if ($s2 === 'inventory') {
            return 'order';
        }

        if ($s2 === 'catalog') {
            return 'catalog';
        }

        $settingsRoutes = [
            'units',
            'terms',
            'sellerprofile',
            'currencies',
            'groups',
            'users',
            'userprofile',
            'warehouses',
            'sendtemplate',
            'countries',
            'country',
            'states',
            'state',
            'cities',
            'city',
        ];
        if (in_array($s2, $settingsRoutes, true)) {
            return 'settings';
        }

        if ($s2 === 'warehouse') {
            return 'settings';
        }

        return null;
    }
}

if (! function_exists('fv_category_image_urls')) {
    /**
     * URLs de miniatura y vista previa para avatar de categoría.
     * Misma estrategia que fv_list_image_urls: estático → API → proxy MVC.
     *
     * @param object|array<string, mixed>|null $category
     * @return array{thumb: string, preview: string, fallbacks: list<string>, has_image: bool}
     */
    function fv_category_image_urls($category): array
    {
        $fallback = function_exists('company_url') ? company_url('image') : '';
        $empty = ['thumb' => $fallback, 'preview' => $fallback, 'fallbacks' => [], 'has_image' => false];

        if ($category === null) {
            return $empty;
        }

        $cat = is_object($category) ? $category : (object) (array) $category;
        $uuid = '';
        $record = null;

        $uuidFrom = static function ($value): string {
            if (! is_string($value)) {
                return '';
            }
            $value = trim($value);
            if ($value === '') {
                return '';
            }
            if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $value, $m)) {
                return $m[1];
            }

            return '';
        };

        if (! empty($cat->image_uuid)) {
            $uuid = $uuidFrom((string) $cat->image_uuid);
        }

        if (! empty($cat->imageRecord) && (is_object($cat->imageRecord) || is_array($cat->imageRecord))) {
            $record = is_object($cat->imageRecord) ? $cat->imageRecord : (object) $cat->imageRecord;
            if ($uuid === '' && ! empty($record->uuid)) {
                $uuid = $uuidFrom((string) $record->uuid);
            }
        }

        if (! empty($cat->image)) {
            if (is_object($cat->image)) {
                $record = $record ?? $cat->image;
                if (! empty($cat->image->uuid)) {
                    $uuid = $uuid !== '' ? $uuid : $uuidFrom((string) $cat->image->uuid);
                }
            } elseif (is_array($cat->image)) {
                $imgObj = (object) $cat->image;
                $record = $record ?? $imgObj;
                if (! empty($imgObj->uuid)) {
                    $uuid = $uuid !== '' ? $uuid : $uuidFrom((string) $imgObj->uuid);
                }
            } elseif (is_string($cat->image)) {
                $fromImage = $uuidFrom($cat->image);
                if ($fromImage !== '') {
                    $uuid = $uuid !== '' ? $uuid : $fromImage;
                }
            }
        }

        if ($uuid === '') {
            return $empty;
        }

        if (function_exists('fv_list_image_urls')) {
            $urls = fv_list_image_urls($uuid, $record);
            $primary = (string) ($urls['primary'] ?? '');
            $fallbacks = is_array($urls['fallbacks'] ?? null) ? $urls['fallbacks'] : [];
            if ($primary === '') {
                return $empty;
            }

            return [
                'thumb' => $primary,
                'preview' => $primary,
                'fallbacks' => array_values($fallbacks),
                'has_image' => true,
            ];
        }

        $api = function_exists('fv_api_image_url') ? fv_api_image_url($uuid) : '';
        $proxy = function_exists('company_url') ? company_url('image/'.$uuid) : '';
        $primary = $api !== '' ? $api : $proxy;

        return [
            'thumb' => $primary !== '' ? $primary : $fallback,
            'preview' => $primary !== '' ? $primary : $fallback,
            'fallbacks' => array_values(array_filter([$proxy !== $primary ? $proxy : ''])),
            'has_image' => $primary !== '',
        ];
    }
}

if (! function_exists('fv_user_display_initials')) {
    function fv_user_display_initials(string $name, string $email = ''): string
    {
        $trimName = trim(preg_replace('/\s+/u', ' ', $name));
        if ($trimName !== '') {
            $parts = preg_split('/\s+/u', $trimName, -1, PREG_SPLIT_NO_EMPTY);
            if (count($parts) >= 2) {
                $a = function_exists('mb_substr') ? mb_substr($parts[0], 0, 1) : substr($parts[0], 0, 1);
                $b = function_exists('mb_substr') ? mb_substr($parts[1], 0, 1) : substr($parts[1], 0, 1);

                return function_exists('mb_strtoupper') ? mb_strtoupper($a.$b) : strtoupper($a.$b);
            }
            $len = function_exists('mb_strlen') ? mb_strlen($parts[0]) : strlen($parts[0]);
            $take = min(2, max(1, $len));
            $slice = function_exists('mb_substr') ? mb_substr($parts[0], 0, $take) : substr($parts[0], 0, $take);

            return function_exists('mb_strtoupper') ? mb_strtoupper($slice) : strtoupper($slice);
        }
        if ($email !== '') {
            $local = (string) (explode('@', $email, 2)[0] ?? '');
            $local = preg_replace('/[^a-zA-Z0-9]/', '', $local);
            if ($local !== '') {
                return strtoupper(strlen($local) >= 2 ? substr($local, 0, 2) : $local);
            }
        }

        return '?';
    }
}

if (! function_exists('fv_user_avatar_bg_color')) {
    function fv_user_avatar_bg_color(string $seed): string
    {
        $h = abs(crc32($seed)) % 360;

        return 'hsl('.$h.', 42%, 46%)';
    }
}

if (! function_exists('fv_uploads_public_url')) {
    /**
     * URL pública de un archivo bajo mvc/uploads (images_local).
     * Codifica cada segmento (espacios en nombres de categoría, etc.).
     */
    function fv_uploads_public_url(string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', ltrim(trim($relativePath), '/'));
        if ($relativePath === '' || ! function_exists('base_url')) {
            return '';
        }
        $segments = [];
        foreach (explode('/', $relativePath) as $part) {
            if ($part === '') {
                continue;
            }
            $segments[] = rawurlencode($part);
        }
        if ($segments === []) {
            return '';
        }

        return rtrim(base_url('uploads/' . implode('/', $segments)), '/');
    }
}

if (! function_exists('fv_api_image_url')) {
    /**
     * URL pública de la API (sin proxy MVC ni sesión): GET /api/v2/images/{uuid}.
     */
    function fv_api_image_url(string $uuidOrId): string
    {
        $id = trim($uuidOrId);
        if ($id === '' || ! function_exists('base_url')) {
            return '';
        }

        return rtrim(base_url('api/v2/images/' . ltrim($id, '/')), '/');
    }
}

if (! function_exists('fv_list_image_urls')) {
    /**
     * URLs para miniaturas de listado (rápido → respaldo).
     * 1) API pública /api/v2/images/{uuid} (fuente fiable en producción)
     * 2) Archivo estático uploads/… solo si existe en disco
     * 3) Proxy MVC company/image/{uuid}
     *
     * @return array{primary: string, fallbacks: list<string>}
     */
    function fv_list_image_urls(string $uuid, $imageRecord = null): array
    {
        $uuid = trim($uuid);
        // Aceptar uuid_90 / uuid_300.ext provenientes de URLs antiguas.
        if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})(?:_\d+)?(?:\.[a-z0-9]+)?$/i', $uuid, $m)) {
            $uuid = $m[1];
        }

        $fallbacks = [];
        $primary = '';

        $path = '';
        if (is_array($imageRecord)) {
            $path = trim((string) ($imageRecord['path'] ?? ''));
        } elseif (is_object($imageRecord)) {
            $path = trim((string) ($imageRecord->path ?? ''));
        }

        // Solo usar uploads/… como primary si el archivo existe en disco.
        // En producción (web.fullvendor.com) path suele apuntar a products/{companyId}/…
        // sin archivo local → 404; la API /api/v2/images/{uuid} es la fuente fiable.
        $static = '';
        if ($path !== '' && preg_match('#^(categories|category|products|product|users|user|catalogs|catalog)/#i', $path)) {
            $diskPath = '';
            if (defined('FCPATH')) {
                $diskPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, ltrim(str_replace('\\', '/', $path), '/'));
            }
            if ($diskPath !== '' && is_file($diskPath)) {
                $static = fv_uploads_public_url($path);
            }
        }

        $api = $uuid !== '' ? fv_api_image_url($uuid) : '';
        $proxy = ($uuid !== '' && function_exists('company_url')) ? company_url('image/' . $uuid) : '';

        // Preferir API (igual que productos en servidor): resuelve path real en BD.
        if ($api !== '') {
            $primary = $api;
        } elseif ($static !== '') {
            $primary = $static;
        } else {
            $primary = $proxy;
        }

        foreach ([$static, $api, $proxy] as $candidate) {
            if ($candidate !== '' && $candidate !== $primary) {
                $fallbacks[] = $candidate;
            }
        }

        return [
            'primary' => $primary,
            'fallbacks' => array_values(array_unique($fallbacks)),
        ];
    }
}

if (! function_exists('fv_user_avatar_image_url')) {
    /**
     * URL del avatar de usuario: uploads/users → API → proxy MVC.
     *
     * @param object|array<string, mixed>|null $user
     */
    function fv_user_avatar_image_url($user): string
    {
        $meta = fv_user_avatar_image_urls($user);

        return (string) ($meta['primary'] ?? '');
    }
}

if (! function_exists('fv_user_avatar_image_urls')) {
    /**
     * URLs del avatar de usuario.
     * users.image suele ser UUID; el archivo real es users/user_{hex}.jpg (tabla images).
     * Por eso NO se inventa uploads/users/{uuid}.ext como primary (404).
     *
     * @param object|array<string, mixed>|null $user
     * @return array{primary: string, fallbacks: list<string>}
     */
    function fv_user_avatar_image_urls($user): array
    {
        $empty = ['primary' => '', 'fallbacks' => []];
        if ($user === null) {
            return $empty;
        }

        $image = trim((string) (is_object($user)
            ? ($user->image ?? $user->profile_image ?? '')
            : ($user['image'] ?? $user['profile_image'] ?? '')));
        if ($image === '') {
            return $empty;
        }

        // URL absoluta ya resuelta.
        if (preg_match('#^(https?:)?//#i', $image) || strpos($image, '/api/v2/images/') === 0) {
            return ['primary' => $image, 'fallbacks' => []];
        }

        $uuid = '';
        $ext = '';
        if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})(?:\.([a-z0-9]+))?$/i', $image, $m)) {
            $uuid = $m[1];
            $ext = strtolower((string) ($m[2] ?? ''));
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
        }

        $candidates = [];

        if ($uuid !== '') {
            // UUID → API (resuelve path real en BD) y proxy MVC. Nunca adivinar {uuid}.ext.
            if (function_exists('fv_api_image_url')) {
                $api = fv_api_image_url($uuid);
                if ($api !== '') {
                    $candidates[] = $api;
                }
            }
            if (function_exists('company_url')) {
                $proxy = company_url('image/' . $uuid);
                if ($proxy !== '') {
                    $candidates[] = $proxy;
                }
            }
            // Solo si el archivo existe literalmente como {uuid}.ext en disco.
            if (defined('FCPATH') && function_exists('fv_uploads_public_url')) {
                $exts = $ext !== '' ? [$ext] : ['jpg', 'png', 'webp', 'jpeg'];
                foreach (array_unique($exts) as $e) {
                    $rel = 'users/' . $uuid . '.' . $e;
                    if (is_file(FCPATH . 'uploads' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel))) {
                        $static = fv_uploads_public_url($rel);
                        if ($static !== '') {
                            $candidates[] = $static;
                        }
                    }
                }
            }
        } else {
            // Legacy: nombre real (p. ej. user_abc.jpg) o path users/...
            $fileName = ltrim(str_replace('\\', '/', $image), '/');
            if (function_exists('base_url')) {
                if (strpos($fileName, 'uploads/users/') === 0) {
                    $candidates[] = base_url($fileName);
                } elseif (preg_match('#^users/#i', $fileName) && function_exists('fv_uploads_public_url')) {
                    $static = fv_uploads_public_url($fileName);
                    if ($static !== '') {
                        $candidates[] = $static;
                    }
                } elseif (strpos($fileName, '/') === false) {
                    $disk = defined('FCPATH')
                        ? FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $fileName
                        : '';
                    if ($disk === '' || is_file($disk)) {
                        $candidates[] = base_url('uploads/users/' . rawurlencode($fileName));
                    }
                }
            }
        }

        $uploadsBaseCompany = defined('FCPATH') ? FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'company' . DIRECTORY_SEPARATOR : '';
        if ($uploadsBaseCompany !== '' && is_file($uploadsBaseCompany . $image) && function_exists('company_logo_url')) {
            $logo = company_logo_url($image);
            if ($logo !== '') {
                array_unshift($candidates, $logo);
            }
        }

        $candidates = array_values(array_unique(array_filter($candidates, static fn ($u) => is_string($u) && $u !== '')));
        if ($candidates === []) {
            return $empty;
        }

        return [
            'primary' => $candidates[0],
            'fallbacks' => array_slice($candidates, 1),
        ];
    }
}

if (! function_exists('fv_user_avatar_meta')) {
    /**
     * @param object|array<string, mixed>|null $user
     * @return array{name: string, email: string, initials: string, bg: string, img_url: string, img_fallbacks: list<string>, has_image: bool}
     */
    function fv_user_avatar_meta($user): array
    {
        $name = '';
        $email = '';
        if (is_object($user)) {
            $name = (string) ($user->name ?? '');
            $email = (string) ($user->email ?? '');
        } elseif (is_array($user)) {
            $name = (string) ($user['name'] ?? '');
            $email = (string) ($user['email'] ?? '');
        }

        $urls = fv_user_avatar_image_urls($user);
        $imgUrl = (string) ($urls['primary'] ?? '');
        $fallbacks = is_array($urls['fallbacks'] ?? null) ? $urls['fallbacks'] : [];
        $seed = $email !== '' ? $email : ($name !== '' ? $name : 'user');

        return [
            'name' => $name,
            'email' => $email,
            'initials' => fv_user_display_initials($name, $email),
            'bg' => fv_user_avatar_bg_color($seed),
            'img_url' => $imgUrl,
            'img_fallbacks' => array_values($fallbacks),
            'has_image' => $imgUrl !== '',
        ];
    }
}

if (! function_exists('fv_product_sku_avatar_text')) {
    function fv_product_sku_avatar_text(string $sku): string
    {
        $sku = strtoupper(trim(preg_replace('/\s+/u', '', $sku)));
        if ($sku === '') {
            return '?';
        }
        $max = 6;
        $len = function_exists('mb_strlen') ? mb_strlen($sku) : strlen($sku);
        if ($len <= $max) {
            return $sku;
        }

        return function_exists('mb_substr') ? mb_substr($sku, 0, $max) : substr($sku, 0, $max);
    }
}

if (! function_exists('fv_product_sku_avatar_font_size')) {
    function fv_product_sku_avatar_font_size(string $label): int
    {
        $len = function_exists('mb_strlen') ? mb_strlen($label) : strlen($label);
        if ($len <= 3) {
            return 14;
        }
        if ($len <= 5) {
            return 11;
        }

        return 9;
    }
}

if (! function_exists('fv_product_sku_avatar_bg_color')) {
    function fv_product_sku_avatar_bg_color(string $sku): string
    {
        return fv_user_avatar_bg_color($sku !== '' ? $sku : 'product');
    }
}

if (! function_exists('fv_product_sku_avatar_meta')) {
    /**
     * @return array{sku: string, label: string, bg: string, font_size: int}
     */
    function fv_product_sku_avatar_meta(string $sku): array
    {
        $sku = trim($sku);
        $label = fv_product_sku_avatar_text($sku);

        return [
            'sku' => $sku,
            'label' => $label,
            'bg' => fv_product_sku_avatar_bg_color($sku),
            'font_size' => fv_product_sku_avatar_font_size($label),
        ];
    }
}

if (! function_exists('fv_unit_display_name')) {
    /**
     * Nombre de unidad de medida para listados (siempre en mayúsculas).
     */
    function fv_unit_display_name(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }
}

if (! function_exists('fv_product_unit_label')) {
    /**
     * @param object|array<string, mixed>|null $listItem
     */
    function fv_product_unit_label($listItem): string
    {
        $name = '';
        $description = '';

        if (is_object($listItem)) {
            if (! empty($listItem->unit)) {
                if (is_object($listItem->unit) || is_array($listItem->unit)) {
                    $unit = (array) $listItem->unit;
                    $name = trim((string) ($unit['unit'] ?? ''));
                    $description = trim((string) ($unit['description'] ?? ''));
                } elseif (is_string($listItem->unit)) {
                    $name = trim($listItem->unit);
                }
            }
            if ($name === '' && ! empty($listItem->unit_name)) {
                $name = trim((string) $listItem->unit_name);
            }
            if ($name === '' && ! empty($listItem->unit_type)) {
                $name = trim((string) $listItem->unit_type);
            }
        } elseif (is_array($listItem)) {
            $unit = $listItem['unit'] ?? null;
            if (is_array($unit) || is_object($unit)) {
                $unit = (array) $unit;
                $name = trim((string) ($unit['unit'] ?? ''));
                $description = trim((string) ($unit['description'] ?? ''));
            } elseif (is_string($unit)) {
                $name = trim($unit);
            }
            if ($name === '' && ! empty($listItem['unit_type'])) {
                $name = trim((string) $listItem['unit_type']);
            }
            if ($name === '' && ! empty($listItem['unit_name'])) {
                $name = trim((string) $listItem['unit_name']);
            }
        }

        $label = $name !== '' ? $name : $description;

        return fv_unit_display_name($label);
    }
}

if (! function_exists('fv_unit_decimal_places')) {
    /**
     * Decimales que acepta una unidad: 0 si es entera.
     *
     * @param object|array<string, mixed>|null $unit
     */
    function fv_unit_decimal_places($unit): int
    {
        if ($unit === null) {
            return 0;
        }
        $unit = is_array($unit) ? (object) $unit : $unit;
        if (! is_object($unit)) {
            return 0;
        }
        $formato = strtolower(trim((string) ($unit->formato ?? 'entero')));
        if ($formato !== 'decimal') {
            return 0;
        }
        $places = (int) ($unit->decimales ?? 0);

        return max(0, min(9, $places));
    }
}

if (! function_exists('fv_product_unit_decimal_places')) {
    /**
     * @param object|array<string, mixed>|null $product
     */
    function fv_product_unit_decimal_places($product): int
    {
        if ($product === null) {
            return 0;
        }
        $product = is_array($product) ? (object) $product : $product;
        $unit = $product->unit ?? null;

        return fv_unit_decimal_places($unit);
    }
}

if (! function_exists('fv_format_unit_quantity')) {
    /**
     * @param object|array<string, mixed>|null $product
     */
    function fv_format_unit_quantity($value, $product, string $radix = '.', string $group = ','): string
    {
        $places = fv_product_unit_decimal_places($product);

        return number_format((float) $value, $places, $radix, $group);
    }
}

if (! function_exists('fv_unit_quantity_step')) {
    function fv_unit_quantity_step(int $places): string
    {
        if ($places <= 0) {
            return '1';
        }

        return '0.' . str_repeat('0', $places - 1) . '1';
    }
}

if (! function_exists('fv_product_resolve_image_uuid')) {
    /**
     * @param object|array<string, mixed>|null $listItem
     */
    function fv_product_resolve_image_uuid($listItem): string
    {
        if ($listItem === null) {
            return '';
        }

        $item = is_object($listItem) ? $listItem : (object) (array) $listItem;

        $resolveUuid = static function ($value): string {
            if ($value === null || $value === '') {
                return '';
            }
            if (is_object($value) && isset($value->uuid) && $value->uuid !== '') {
                return (string) $value->uuid;
            }
            if (is_array($value) && ! empty($value['uuid'])) {
                return (string) $value['uuid'];
            }
            $s = trim((string) $value);
            if ($s === '') {
                return '';
            }
            if (preg_match('#/images/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})#i', $s, $m)) {
                return $m[1];
            }
            if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $s, $m)) {
                return $m[1];
            }

            return '';
        };

        foreach (['default_image', 'default_image_id', 'Img', 'first_ordered_image', 'product_image', 'image_uuid', 'product_image_uuid'] as $key) {
            $uuid = $resolveUuid($item->{$key} ?? null);
            if ($uuid !== '') {
                return $uuid;
            }
        }

        if (! empty($item->product_images)) {
            $rows = $item->product_images;
            if (is_object($rows) && isset($rows->data)) {
                $rows = $rows->data;
            }
            $list = [];
            foreach ((array) $rows as $pi) {
                $list[] = is_object($pi) ? $pi : (object) (array) $pi;
            }
            usort($list, static function ($a, $b) {
                return ((int) ($a->img_order ?? 999999)) <=> ((int) ($b->img_order ?? 999999));
            });
            foreach ($list as $pi) {
                if (! empty($pi->image)) {
                    $uuid = $resolveUuid($pi->image);
                    if ($uuid !== '') {
                        return $uuid;
                    }
                }
                $uuid = $resolveUuid($pi->image_id ?? null);
                if ($uuid !== '') {
                    return $uuid;
                }
            }
        }

        if (! empty($item->images)) {
            $images = is_array($item->images)
                ? $item->images
                : (is_object($item->images) ? (array) $item->images : []);
            foreach ($images as $image) {
                $uuid = $resolveUuid($image);
                if ($uuid !== '') {
                    return $uuid;
                }
            }
        }

        return '';
    }
}

if (! function_exists('fv_product_list_image_meta')) {
    /**
     * @param object|array<string, mixed>|null $listItem
     * @return array{has_image: bool, thumb: string, preview: string, avatar: array<string, mixed>}
     */
    function fv_product_list_image_meta($listItem, string $sku = ''): array
    {
        if ($sku === '' && $listItem !== null) {
            $sku = trim((string) (is_object($listItem) ? ($listItem->sku ?? '') : ($listItem['sku'] ?? '')));
        }

        $uuid = fv_product_resolve_image_uuid($listItem);
        $avatar = fv_product_sku_avatar_meta($sku);
        if ($uuid === '') {
            return [
                'has_image' => false,
                'thumb' => '',
                'preview' => '',
                'fallbacks' => [],
                'avatar' => $avatar,
            ];
        }

        $record = null;
        if (is_object($listItem)) {
            $record = $listItem->default_image ?? $listItem->defaultImage ?? null;
        } elseif (is_array($listItem)) {
            $record = $listItem['default_image'] ?? $listItem['defaultImage'] ?? null;
        }

        if (function_exists('fv_list_image_urls')) {
            $urls = fv_list_image_urls($uuid, $record);
            $primary = (string) ($urls['primary'] ?? '');
            $fallbacks = is_array($urls['fallbacks'] ?? null) ? $urls['fallbacks'] : [];

            return [
                'has_image' => $primary !== '',
                'thumb' => $primary,
                'preview' => $primary,
                'fallbacks' => $fallbacks,
                'avatar' => $avatar,
            ];
        }

        $api = function_exists('fv_api_image_url') ? fv_api_image_url($uuid) : '';
        $proxy = function_exists('company_url') ? company_url('image/'.$uuid) : '';

        return [
            'has_image' => $api !== '' || $proxy !== '',
            'thumb' => $api !== '' ? $api : $proxy,
            'preview' => $api !== '' ? $api : $proxy,
            'fallbacks' => array_values(array_filter([$proxy !== '' && $proxy !== $api ? $proxy : ''])),
            'avatar' => $avatar,
        ];
    }
}

if (! function_exists('fv_resolve_numeric_group_separator')) {
    /**
     * Resuelve el separador de miles del formato numérico de la empresa.
     * Si viene vacío en BD (p. ej. format_id 4), infiere según radixPoint.
     *
     * @param object|array|null $formatNumeric
     */
    function fv_resolve_numeric_group_separator($formatNumeric = null): string
    {
        $radixPoint = '.';
        $groupSeparator = '';

        if ($formatNumeric !== null) {
            if (is_object($formatNumeric)) {
                $radixPoint = (string) ($formatNumeric->radixPoint ?? $formatNumeric->radix_point ?? '.');
                $groupSeparator = (string) ($formatNumeric->groupSeparator ?? $formatNumeric->group_separator ?? '');
            } elseif (is_array($formatNumeric)) {
                $radixPoint = (string) ($formatNumeric['radixPoint'] ?? $formatNumeric['radix_point'] ?? '.');
                $groupSeparator = (string) ($formatNumeric['groupSeparator'] ?? $formatNumeric['group_separator'] ?? '');
            }
        }

        if ($groupSeparator === '') {
            $groupSeparator = ($radixPoint === ',') ? '.' : ',';
        }

        if ($groupSeparator === $radixPoint) {
            $groupSeparator = ($radixPoint === ',') ? '.' : ',';
        }

        return $groupSeparator;
    }
}

if (! function_exists('fv_format_money')) {
    /**
     * Formatea un importe con símbolo y formato numérico de la configuración de la empresa.
     *
     * @param int|float|string|null $value
     * @param object|array|null $formatNumeric
     * @param string|null $currencySymbol
     */
    function fv_format_money($value, $formatNumeric = null, $currencySymbol = null): string
    {
        $digits = 2;
        $radixPoint = '.';
        $codeRegional = '';

        if ($formatNumeric !== null) {
            if (is_object($formatNumeric)) {
                $digits = (int) ($formatNumeric->digits ?? 2);
                $radixPoint = (string) ($formatNumeric->radixPoint ?? $formatNumeric->radix_point ?? '.');
                $codeRegional = (string) ($formatNumeric->code_regional ?? '');
            } elseif (is_array($formatNumeric)) {
                $digits = (int) ($formatNumeric['digits'] ?? 2);
                $radixPoint = (string) ($formatNumeric['radixPoint'] ?? $formatNumeric['radix_point'] ?? '.');
                $codeRegional = (string) ($formatNumeric['code_regional'] ?? '');
            }
        }

        if ($digits < 0) {
            $digits = 2;
        }

        if ($codeRegional === 'en_IN' && function_exists('fv_format_indian_number')) {
            $formatted = fv_format_indian_number((float) $value, $digits, $radixPoint);
        } else {
            $groupSeparator = fv_resolve_numeric_group_separator($formatNumeric);
            $formatted = number_format((float) $value, $digits, $radixPoint, $groupSeparator);
        }
        $symbol = is_string($currencySymbol) ? trim($currencySymbol) : '';

        if ($symbol === '') {
            return $formatted;
        }

        // Estándar mockup: $1,234.56 (sin espacio entre símbolo e importe)
        return $symbol.$formatted;
    }
}

if (! function_exists('fv_format_indian_number')) {
    function fv_format_indian_number(float $value, int $digits = 2, string $radixPoint = '.'): string
    {
        $negative = $value < 0;
        $value = abs($value);
        $fixed = number_format($value, max(0, $digits), '.', '');
        [$intPart, $decPart] = array_pad(explode('.', $fixed, 2), 2, '');
        $last3 = substr($intPart, -3);
        $rest = substr($intPart, 0, -3);
        $groups = [];
        while (is_string($rest) && $rest !== '') {
            $groups[] = substr($rest, -2);
            $rest = substr($rest, 0, -2);
        }
        $groups = array_reverse(array_filter($groups, static fn ($g) => $g !== '' && $g !== false));
        $grouped = ($groups !== [] ? implode(',', $groups).',' : '').$last3;
        $out = $grouped.($digits > 0 ? $radixPoint.$decPart : '');

        return ($negative ? '-' : '').$out;
    }
}

if (! function_exists('fv_format_integer_count')) {
    /**
     * Formatea un entero con separador de miles según el formato numérico de la empresa.
     *
     * @param int|float|string $value
     * @param object|array|null $formatNumeric
     */
    function fv_format_integer_count($value, $formatNumeric = null): string
    {
        $radixPoint = '.';
        if ($formatNumeric !== null) {
            if (is_object($formatNumeric)) {
                $radixPoint = (string) ($formatNumeric->radixPoint ?? $formatNumeric->radix_point ?? '.');
            } elseif (is_array($formatNumeric)) {
                $radixPoint = (string) ($formatNumeric['radixPoint'] ?? $formatNumeric['radix_point'] ?? '.');
            }
        }

        return number_format((int) $value, 0, $radixPoint, fv_resolve_numeric_group_separator($formatNumeric));
    }
}

if (! function_exists('fv_normalize_relation_list')) {
    /**
     * Normaliza una relación API (array, paginator.data u objetos) a lista de objetos.
     *
     * @param mixed $raw
     * @return list<object>
     */
    function fv_normalize_relation_list($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_object($raw) && isset($raw->data)) {
            $raw = $raw->data;
        }
        if (! is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            $out[] = is_object($row) ? $row : (object) (array) $row;
        }

        return $out;
    }
}

if (! function_exists('fv_product_category_group_labels')) {
    /**
     * Etiquetas elegantes de grupos/subgrupos de producto.
     * Subgrupo → "Grupo / Subgrupo"; grupo solo (sin subgrupo seleccionado) → "Grupo".
     *
     * @param mixed $groups
     * @param mixed $subgroups
     * @return list<string>
     */
    function fv_product_category_group_labels($groups, $subgroups): array
    {
        $groupRows = fv_normalize_relation_list($groups);
        $subgroupRows = fv_normalize_relation_list($subgroups);

        $groupNamesById = [];
        foreach ($groupRows as $group) {
            $gid = (int) ($group->group_id ?? 0);
            $name = trim((string) ($group->group_name ?? ''));
            if ($gid > 0 && $name !== '') {
                $groupNamesById[$gid] = $name;
            }
        }

        $labels = [];
        $groupIdsWithSubgroups = [];

        foreach ($subgroupRows as $subgroup) {
            $parentId = (int) ($subgroup->group_id ?? 0);
            $nestedGroup = $subgroup->group ?? $subgroup->category_group ?? null;
            if (is_array($nestedGroup)) {
                $nestedGroup = (object) $nestedGroup;
            }
            if ($parentId <= 0 && is_object($nestedGroup)) {
                $parentId = (int) ($nestedGroup->group_id ?? 0);
            }
            $parentName = '';
            if ($parentId > 0 && isset($groupNamesById[$parentId])) {
                $parentName = $groupNamesById[$parentId];
            } elseif (is_object($nestedGroup)) {
                $parentName = trim((string) ($nestedGroup->group_name ?? ''));
            }
            $subName = trim((string) ($subgroup->subgroup_name ?? ''));
            if ($subName === '') {
                continue;
            }
            if ($parentId > 0) {
                $groupIdsWithSubgroups[$parentId] = true;
            }
            $labels[] = $parentName !== '' ? ($parentName . ' / ' . $subName) : $subName;
        }

        foreach ($groupRows as $group) {
            $gid = (int) ($group->group_id ?? 0);
            if ($gid > 0 && isset($groupIdsWithSubgroups[$gid])) {
                continue;
            }
            $name = trim((string) ($group->group_name ?? ''));
            if ($name !== '') {
                $labels[] = $name;
            }
        }

        return array_values(array_unique($labels));
    }
}

if (! function_exists('fv_product_list_taxonomy_payload')) {
    /**
     * Payload JSON para editar categoría/grupos desde el listado de productos.
     *
     * @param object|array $listItem
     * @param object|array|null $formatNumeric
     * @param string|null $currencySymbol
     * @return array{
     *   pro_id: int,
     *   name: string,
     *   sku: string,
     *   category_ids: list<int>,
     *   category_names: list<string>,
     *   group_ids: list<int>,
     *   subgroup_ids: list<int>,
     *   groups: list<array{id:int,label:string}>,
     *   subgroups: list<array{id:int,label:string,parentGroupId:int}>
     * }
     */
    function fv_product_list_taxonomy_payload($listItem, $formatNumeric = null, $currencySymbol = null): array
    {
        $item = is_object($listItem) ? $listItem : (object) (array) $listItem;
        $categoryIds = [];
        $categoryNames = [];
        foreach (fv_normalize_relation_list($item->categories ?? []) as $cat) {
            $cid = (int) ($cat->cat_id ?? 0);
            $cname = trim((string) ($cat->category_name ?? ''));
            if ($cid > 0) {
                $categoryIds[] = $cid;
            }
            if ($cname !== '') {
                $categoryNames[] = $cname;
            }
        }

        $cgList = $item->categoryGroups ?? $item->category_groups ?? [];
        $csgList = $item->categorySubgroups ?? $item->category_subgroups ?? [];
        $groupIds = [];
        $groups = [];
        $groupNamesById = [];
        foreach (fv_normalize_relation_list($cgList) as $gRow) {
            $gid = (int) ($gRow->group_id ?? 0);
            $gName = trim((string) ($gRow->group_name ?? ''));
            if ($gid <= 0) {
                continue;
            }
            $groupIds[] = $gid;
            if ($gName !== '') {
                $groupNamesById[$gid] = $gName;
            }
            $groups[] = [
                'id' => $gid,
                'label' => $gName !== '' ? $gName : ('#' . $gid),
            ];
        }

        $subgroupIds = [];
        $subgroups = [];
        foreach (fv_normalize_relation_list($csgList) as $sgRow) {
            $sid = (int) ($sgRow->subgroup_id ?? 0);
            if ($sid <= 0) {
                continue;
            }
            $parentId = (int) ($sgRow->group_id ?? 0);
            $subName = trim((string) ($sgRow->subgroup_name ?? ''));
            $parentName = $groupNamesById[$parentId] ?? '';
            $label = ($parentName !== '' && $subName !== '')
                ? ($parentName . ' / ' . $subName)
                : ($subName !== '' ? $subName : ('#' . $sid));
            $subgroupIds[] = $sid;
            $subgroups[] = [
                'id' => $sid,
                'label' => $label,
                'parentGroupId' => $parentId,
            ];
        }

        $sku = trim((string) ($item->sku ?? ''));
        $imageMeta = fv_product_list_image_meta($item, $sku);
        $avatar = is_array($imageMeta['avatar'] ?? null) ? $imageMeta['avatar'] : fv_product_sku_avatar_meta($sku);
        $unitLabel = fv_product_unit_label($item);
        $enabled = (int) ($item->status ?? 1) === 1;
        $descriptions = trim((string) ($item->descriptions ?? $item->description ?? ''));
        if (function_exists('mb_substr') && mb_strlen($descriptions, 'UTF-8') > 400) {
            $descriptions = mb_substr($descriptions, 0, 400, 'UTF-8') . '…';
        } elseif (strlen($descriptions) > 400) {
            $descriptions = substr($descriptions, 0, 400) . '…';
        }

        $salePrice = (float) ($item->sale_price ?? 0);
        $stock = (float) ($item->stock ?? 0);
        $moq = (float) ($item->minimum_quantity ?? 0);
        $currency = is_string($currencySymbol) ? trim($currencySymbol) : '';
        if ($currency === '') {
            $currency = trim((string) ($item->currency_symbol ?? $item->currency_type ?? ''));
        }
        $priceDisplay = function_exists('fv_format_money')
            ? fv_format_money($salePrice, $formatNumeric, $currency !== '' ? $currency : null)
            : (($currency !== '' ? $currency . ' ' : '') . number_format($salePrice, 2, '.', ','));
        $digits = 2;
        $radix = '.';
        if (is_object($formatNumeric)) {
            $digits = (int) ($formatNumeric->digits ?? 2);
            $radix = (string) ($formatNumeric->radixPoint ?? $formatNumeric->radix_point ?? '.');
        } elseif (is_array($formatNumeric)) {
            $digits = (int) ($formatNumeric['digits'] ?? 2);
            $radix = (string) ($formatNumeric['radixPoint'] ?? $formatNumeric['radix_point'] ?? '.');
        }
        if ($digits < 0) {
            $digits = 2;
        }
        $group = function_exists('fv_resolve_numeric_group_separator')
            ? fv_resolve_numeric_group_separator($formatNumeric)
            : ',';
        $stockDisplay = number_format($stock, $digits, $radix, $group);
        if ($unitLabel !== '') {
            $stockDisplay .= ' ' . $unitLabel;
        }
        $moqDisplay = number_format($moq, $digits, $radix, $group);
        if ($unitLabel !== '' && $moq > 0) {
            $moqDisplay .= ' ' . $unitLabel;
        }

        $groupLabels = fv_product_category_group_labels($cgList, $csgList);

        return [
            'pro_id' => (int) ($item->pro_id ?? 0),
            'name' => trim((string) ($item->name ?? '')),
            'sku' => $sku,
            'barcode' => trim((string) ($item->barcode ?? '')),
            'sale_price' => $salePrice,
            'price_display' => $priceDisplay,
            'stock' => $stock,
            'stock_display' => $stockDisplay,
            'minimum_quantity' => $moq,
            'moq_display' => $moqDisplay,
            'unit' => $unitLabel,
            'tags' => trim((string) ($item->tags ?? '')),
            'locations' => trim((string) ($item->locations ?? '')),
            'descriptions' => $descriptions,
            'enabled' => $enabled,
            'has_image' => ! empty($imageMeta['has_image']),
            'image_url' => (string) ($imageMeta['thumb'] ?? ''),
            'image_preview' => (string) ($imageMeta['preview'] ?? ''),
            'image_fallbacks' => array_values(is_array($imageMeta['fallbacks'] ?? null) ? $imageMeta['fallbacks'] : []),
            'avatar_label' => (string) ($avatar['label'] ?? ''),
            'avatar_bg' => (string) ($avatar['bg'] ?? '#94a3b8'),
            'avatar_font_size' => (int) ($avatar['font_size'] ?? 14),
            'category_ids' => array_values(array_unique($categoryIds)),
            'category_names' => array_values(array_unique($categoryNames)),
            'group_ids' => array_values(array_unique($groupIds)),
            'subgroup_ids' => array_values(array_unique($subgroupIds)),
            'group_labels' => $groupLabels,
            'groups' => $groups,
            'subgroups' => $subgroups,
        ];
    }
}
