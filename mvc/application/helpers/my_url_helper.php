<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('admin_url'))
{
	function admin_url($uri = '', $protocol = NULL)
	{
		$base = get_instance()->config->item('admin_url');
		if (! is_string($base) || $base === '') {
			$base = rtrim((string) get_instance()->config->item('base_url'), '/') . '/admin/';
		}

		$url = $base . ltrim((string) $uri, '/');

		return preg_replace('#(?<!:)/{2,}#', '/', $url);
	}
}

if ( ! function_exists('platform_login_url'))
{
	/**
	 * URL de login según el área actual: /admin/login (panel admin) o /login (empresa).
	 */
	function platform_login_url(?string $redirectto = null): string
	{
		$CI = &get_instance();
		$isAdmin = false;
		if (isset($CI->router) && is_object($CI->router)) {
			$dir = rtrim((string) ($CI->router->directory ?? ''), '/');
			$path = trim((string) ($CI->uri->uri_string ?? ''), '/');
			$isAdmin = (strcasecmp($dir, 'admin') === 0)
				|| $path === 'admin'
				|| strpos($path, 'admin/') === 0;
		}

		$url = $isAdmin ? admin_url('login') : base_url('login');
		if ($redirectto !== null && $redirectto !== '') {
			$url .= (strpos($url, '?') === false ? '?' : '&') . 'redirectto=' . rawurlencode($redirectto);
		}

		return $url;
	}
}

if ( ! function_exists('company_url'))
{
	function company_url($uri = '', $protocol = NULL)
	{
		return get_instance()->config->item('company_url') . $uri;
		//return get_instance()->config->company_url($uri, $protocol);
	}
}

if ( ! function_exists('import_template_download_url'))
{
	/**
	 * URL de descarga de plantilla Excel de importación (vía controlador, no estático en uploads/).
	 */
	function import_template_download_url(string $templateFile): string
	{
		if (!class_exists('Spreadsheet_import', false)) {
			require_once APPPATH . 'libraries/Spreadsheet_import.php';
		}

		$basename = Spreadsheet_import::normalizeTemplateBasename($templateFile);
		if ($basename === null) {
			return '';
		}

		return company_url('import_template/download/' . rawurlencode($basename));
	}
}

if ( ! function_exists('seller_url'))
{
	function seller_url($uri = '', $protocol = NULL)
	{
		return get_instance()->config->item('seller_url') . $uri;
		// return get_instance()->config->seller_url($uri, $protocol);
	}
}


if ( ! function_exists('marketplace_url'))
{
	function marketplace_url($uri = '', $protocol = NULL)
	{
		//echo get_instance()->config->seller_url($uri, $protocol);
		//return get_instance()->config->item('marketplace_url') . $uri;
		return get_instance()->config->item('marketplace_url') . $uri;
	}
}

if ( ! function_exists('dated_file_url'))
{
    function dated_file_url($fileUrl) {
        $realPath = realpath(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.ltrim($fileUrl, DIRECTORY_SEPARATOR));

        $fileTimestamp = filemtime($realPath);
        return base_url($fileUrl) . '?v=' . date('YmdHis',$fileTimestamp);
    }
}

if( ! function_exists('get_app_url')) {
    function get_app_url($url='') {
        $application = '';
        /** @var Application_Controller $instance */
        $instance = get_instance();
        if(method_exists($instance, 'getApplication')) {
            $application = $instance->getApplication();
        }
        switch ($application) {
            case 'admin':
                return admin_url($url);
            case 'company':
                return company_url($url);
            case 'marketplace':
                return marketplace_url($url);
            case 'salesman':
                return seller_url($url);
            default:
                return base_url($url);
        }
    }
}

if ( ! function_exists('company_logo_is_api_image'))
{
	/**
	 * Imagen de compañía guardada vía API (campo companies.image = uuid.ext).
	 */
	function company_logo_is_api_image($image)
	{
		$image = trim((string) $image);

		return $image !== '' && (bool) preg_match(
			'/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.[a-zA-Z0-9]+$/i',
			$image
		);
	}
}

if ( ! function_exists('company_logo_url'))
{
	/**
	 * URL pública del logo de compañía (MinIO/API o carpeta legacy uploads/company).
	 */
	function company_logo_url($image)
	{
		$image = trim((string) $image);
		if ($image === '') {
			return '';
		}
		if (company_logo_is_api_image($image)) {
			$dot = strrpos($image, '.');
			if ($dot !== false) {
				$uuid = substr($image, 0, $dot);
				$ext = substr($image, $dot + 1);
				// Mismo origen que el panel (proxy /api/v2 → Laravel).
				// No usar API_SERVER/localhost: el navegador del usuario no los resuelve.
				$path = '/api/v2/images/' . rawurlencode($uuid) . '.' . rawurlencode($ext);
				if (function_exists('base_url')) {
					return rtrim(base_url(), '/') . $path;
				}

				return $path;
			}
		}

		return base_url('uploads/company/' . $image);
	}
}

if ( ! function_exists('company_product_image_uuid'))
{
	/**
	 * Extrae UUID de imagen desde producto API (array u objeto).
	 *
	 * @param array<string, mixed>|object|null $product
	 */
	function company_product_image_uuid($product): string
	{
		if ($product === null) {
			return '';
		}
		$obj = is_array($product) ? (object) $product : $product;

		// Solo tratar ->uuid como imagen si NO es un producto (los productos usan pro_id).
		$looksLikeProduct = isset($obj->pro_id) || isset($obj->sku) || isset($obj->product_id);
		if (! $looksLikeProduct && ! empty($obj->uuid)) {
			$s = trim((string) $obj->uuid);
			if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $s)) {
				return $s;
			}
		}

		$candidates = [
			$obj->product_image ?? null,
			$obj->default_image_id ?? null,
			$obj->first_ordered_image ?? null,
			$obj->image_uuid ?? null,
		];

		if (! empty($obj->default_image)) {
			$di = is_array($obj->default_image) ? (object) $obj->default_image : $obj->default_image;
			if (is_object($di)) {
				$candidates[] = $di->uuid ?? null;
				$candidates[] = $di->image_id ?? null;
			} else {
				$candidates[] = $di;
			}
		}

		$rows = $obj->product_images ?? null;
		if ($rows !== null && $rows !== '') {
			if (is_object($rows) && isset($rows->data)) {
				$rows = $rows->data;
			}
			if (is_object($rows)) {
				$rows = array_values((array) $rows);
			}
			foreach ((array) $rows as $pi) {
				$row = is_array($pi) ? (object) $pi : $pi;
				if (! is_object($row)) {
					continue;
				}
				if (! empty($row->image)) {
					$img = is_array($row->image) ? (object) $row->image : $row->image;
					$candidates[] = is_object($img) ? ($img->uuid ?? null) : $img;
				}
				$candidates[] = $row->image_id ?? null;
			}
		}

		$images = $obj->images ?? null;
		if (is_object($images)) {
			$images = array_values((array) $images);
		}
		if (is_array($images) && $images !== []) {
			$first = $images[0];
			$img = is_array($first) ? (object) $first : $first;
			if (is_object($img)) {
				$candidates[] = $img->uuid ?? null;
				$candidates[] = $img->image_id ?? null;
			}
		}

		foreach ($candidates as $value) {
			if ($value === null || $value === '') {
				continue;
			}
			if (is_object($value)) {
				$value = $value->uuid ?? $value->image_id ?? '';
			}
			$s = trim((string) $value);
			if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $s)) {
				return $s;
			}
		}

		return '';
	}
}

if ( ! function_exists('company_product_image_url'))
{
	/**
	 * URL pública de imagen de producto (estático → API → sin escalado _90/_200).
	 * El parámetro $width se ignora a propósito: el escalado vía API satura el servidor
	 * local (artisan serve) y provoca miniaturas rotas en listados.
	 *
	 * @param array<string, mixed>|object|string|null $productOrUuid UUID o payload de producto API
	 */
	function company_product_image_url($productOrUuid = null, int $width = 90, string $ext = ''): string
	{
		$uuid = '';
		$record = null;
		if (is_string($productOrUuid) || is_numeric($productOrUuid)) {
			$candidate = trim((string) $productOrUuid);
			if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $candidate, $m)) {
				$uuid = $m[1];
			}
		} elseif (is_array($productOrUuid) || is_object($productOrUuid)) {
			$uuid = company_product_image_uuid($productOrUuid);
			if (is_object($productOrUuid)) {
				$record = $productOrUuid->default_image
					?? $productOrUuid->defaultImage
					?? (is_object($productOrUuid->image ?? null) ? $productOrUuid->image : null);
			} elseif (is_array($productOrUuid)) {
				$record = $productOrUuid['default_image']
					?? $productOrUuid['defaultImage']
					?? (is_array($productOrUuid['image'] ?? null) ? $productOrUuid['image'] : null);
			}
		}

		if ($uuid === '') {
			return catalog_public_no_image_url();
		}

		if (function_exists('fv_list_image_urls')) {
			$urls = fv_list_image_urls($uuid, $record);
			$primary = trim((string) ($urls['primary'] ?? ''));
			if ($primary !== '') {
				return $primary;
			}
		}

		if (function_exists('fv_api_image_url')) {
			$api = fv_api_image_url($uuid);
			if ($api !== '') {
				return $api;
			}
		}

		return '/api/v2/images/' . rawurlencode($uuid);
	}
}

if ( ! function_exists('company_product_image_nobg_url'))
{
	/**
	 * URL de imagen de producto con fondo blanco convertido a transparente (PNG).
	 * Usar en catálogo / studio para fotos de estudio.
	 *
	 * @param array<string, mixed>|object|string|null $productOrUuid
	 */
	function company_product_image_nobg_url($productOrUuid = null, int $width = 400): string
	{
		$uuid = '';
		if (is_string($productOrUuid) || is_numeric($productOrUuid)) {
			$candidate = trim((string) $productOrUuid);
			if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $candidate, $m)) {
				$uuid = $m[1];
			}
		} elseif (is_array($productOrUuid) || is_object($productOrUuid)) {
			if (function_exists('company_product_image_uuid')) {
				$uuid = company_product_image_uuid($productOrUuid);
			}
		}

		if ($uuid === '') {
			return function_exists('catalog_public_no_image_url')
				? catalog_public_no_image_url()
				: '/api/v2/images/no-image';
		}

		$width = max(16, min(1200, (int) $width));
		$base = '';
		if (function_exists('base_url')) {
			$base = rtrim((string) base_url(), '/');
		}

		$path = '/api/v2/images/' . rawurlencode($uuid) . '_' . $width . '_nobg.png';

		return $base !== '' ? $base . $path : $path;
	}
}

if ( ! function_exists('catalog_public_no_image_url'))
{
	/**
	 * Placeholder de imagen vía API (MinIO); no usa disco local uploads/.
	 */
	function catalog_public_no_image_url(): string
	{
		return '/api/v2/images/no-image';
	}
}

if ( ! function_exists('catalog_public_image_url'))
{
	/**
	 * URL de imagen de catálogo público (MinIO) con token de carrito.
	 *
	 * @param string|null $imageRef UUID, uuid.webp, URL completa (imagen_js) o null
	 */
	function catalog_public_image_url($imageRef = null, ?string $cartToken = null): string
	{
		$base = rtrim(base_url(), '/');

		if ($imageRef === null || $imageRef === '') {
			return catalog_public_no_image_url();
		}

		$ref = trim((string) $imageRef);
		if ($ref === '') {
			return catalog_public_no_image_url();
		}

        if (preg_match('#^https?://#i', $ref)) {
            $normalized = preg_replace('#^https?://laravel\.test#i', $base, $ref);

            return is_string($normalized) && $normalized !== '' ? $normalized : $ref;
        }

        // UUID.ext (evitar basename() sobre refs corruptas tipo uuid.image/png → "png")
        if (preg_match('#^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})(?:\.([a-z0-9]+))?$#i', $ref, $m)) {
            $ext = strtolower($m[2] ?? 'png');
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            if (! in_array($ext, ['jpg', 'png', 'gif', 'webp', 'bmp', 'avif', 'tif', 'tiff', 'heic', 'heif', 'svg', 'ico'], true)) {
                $ext = 'png';
            }
            $ref = $m[1] . '.' . $ext;
        } elseif (strpos($ref, '/') !== false) {
            $base = basename($ref);
            if (preg_match('#^[0-9a-f-]{36}\.#i', $base)) {
                $ref = $base;
            }
        }

		if (! preg_match('/\.(webp|jpg|jpeg|png|gif|bmp|avif|tif|tiff|heic|heif|svg|ico)$/i', $ref)) {
			$ref .= '.webp';
		}

		$url = $base . '/api/v2/images/catalog/' . rawurlencode($ref);
		if ($cartToken !== null && $cartToken !== '') {
			$url .= '?token=' . rawurlencode($cartToken);
		}

		return $url;
	}
}

if ( ! function_exists('catalog_public_browse_url'))
{
	/**
	 * URL completa del catálogo público con token codificado para query string.
	 */
	function catalog_public_browse_url(string $token, string $path = 'catalogo/producto'): string
	{
		$base = rtrim(base_url(), '/');
		$route = ltrim($path, '/');
		$token = trim($token);
		if ($token === '') {
			return $base . '/' . $route;
		}

		return $base . '/' . $route . '?token=' . rawurlencode($token);
	}
}

if ( ! function_exists('company_product_rating_value'))
{
	/**
	 * Extrae el rating numérico (1–5) de un producto API/objeto.
	 *
	 * @param array<string, mixed>|object|null $product
	 */
	function company_product_rating_value($product): float
	{
		$default = 3.5;
		if ($product === null) {
			return $default;
		}
		$item = is_array($product) ? $product : (array) $product;
		if (isset($item['rating']) && is_numeric($item['rating'])) {
			return max(0.0, min(5.0, round((float) $item['rating'], 1)));
		}
		$rel = $item['product_rating'] ?? $item['productRating'] ?? null;
		if (is_object($rel) && isset($rel->rating) && is_numeric($rel->rating)) {
			return max(0.0, min(5.0, round((float) $rel->rating, 1)));
		}
		if (is_array($rel) && isset($rel['rating']) && is_numeric($rel['rating'])) {
			return max(0.0, min(5.0, round((float) $rel['rating'], 1)));
		}

		return $default;
	}
}

if ( ! function_exists('company_product_rating_stars_html'))
{
	/**
	 * HTML de estrellas para rating de producto (Unicode, sin dependencia de icon fonts).
	 */
	function company_product_rating_stars_html($rating, string $ariaLabel = ''): string
	{
		$r = max(0.0, min(5.0, (float) $rating));
		$full = (int) floor($r);
		$frac = $r - $full;
		$half = ($frac >= 0.25 && $frac < 0.75) ? 1 : 0;
		if ($frac >= 0.75) {
			$full++;
			$half = 0;
		}
		if ($full > 5) {
			$full = 5;
			$half = 0;
		}
		$empty = max(0, 5 - $full - $half);
		$aria = htmlspecialchars($ariaLabel !== '' ? $ariaLabel : number_format($r, 1, '.', ''), ENT_QUOTES, 'UTF-8');
		$html = '<span class="fv-product-rating" role="img" aria-label="' . $aria . '" title="' . $aria . '" data-rating="' . htmlspecialchars((string) $r, ENT_QUOTES, 'UTF-8') . '">';
		for ($i = 0; $i < $full; $i++) {
			$html .= '<span class="fv-product-rating__star is-full" aria-hidden="true">★</span>';
		}
		if ($half) {
			$html .= '<span class="fv-product-rating__star is-half" aria-hidden="true">★</span>';
		}
		for ($i = 0; $i < $empty; $i++) {
			$html .= '<span class="fv-product-rating__star is-empty" aria-hidden="true">☆</span>';
		}
		$html .= '<span class="fv-product-rating__value">' . htmlspecialchars(number_format($r, 1, '.', ''), ENT_QUOTES, 'UTF-8') . '</span>';
		$html .= '</span>';

		return $html;
	}
}


