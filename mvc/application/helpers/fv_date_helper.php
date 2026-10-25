<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (! function_exists('fv_display_date_format')) {
    /**
     * Fallback PHP si no hay formato de empresa: 12/31/2026 (MM/DD/YYYY).
     */
    function fv_display_date_format(): string
    {
        return 'm/d/Y';
    }
}

if (! function_exists('fv_display_datetime_format')) {
    function fv_display_datetime_format(): string
    {
        return fv_resolve_display_date_format() . ' H:i';
    }
}

if (! function_exists('fv_date_format_jquery_ui')) {
    /**
     * Convierte format_php (empresa) a tokens de jQuery UI Datepicker.
     * Ej: m/d/Y → mm/dd/yy | d/m/Y → dd/mm/yy | Y-m-d → yy-mm-dd
     */
    function fv_date_format_jquery_ui(?string $phpFormat = null): string
    {
        $php = trim((string) ($phpFormat ?? fv_resolve_display_date_format()));
        if ($php === '') {
            return 'mm/dd/yy';
        }

        // Formatos con texto libre: patrón numérico equivalente.
        if (preg_match('/\\\\d\\\\e|年|de M/u', $php)
            || preg_match('/[^\w\-\/\.\s\\\\]/u', str_replace(['\\d', '\\e'], '', $php))) {
            if (preg_match('/^Y/', $php) || (strpos($php, 'Y') !== false && strpos($php, 'n') !== false)) {
                return 'yy-mm-dd';
            }
            if (preg_match('/^m/', $php)) {
                return 'mm/dd/yy';
            }
            if (preg_match('/^d/', $php)) {
                return 'dd/mm/yy';
            }
        }

        $out = '';
        $len = strlen($php);
        for ($i = 0; $i < $len; $i++) {
            if ($php[$i] === '\\' && $i + 1 < $len) {
                $out .= $php[$i + 1];
                $i++;
                continue;
            }
            switch ($php[$i]) {
                case 'Y':
                    $out .= 'yy';
                    break;
                case 'y':
                    $out .= 'y';
                    break;
                case 'm':
                    $out .= 'mm';
                    break;
                case 'n':
                    $out .= 'm';
                    break;
                case 'd':
                    $out .= 'dd';
                    break;
                case 'j':
                    $out .= 'd';
                    break;
                case 'M':
                    $out .= 'M';
                    break;
                default:
                    $out .= $php[$i];
            }
        }

        return $out !== '' ? $out : 'mm/dd/yy';
    }
}

if (! function_exists('fv_display_date_format_js')) {
    /**
     * Formato jQuery UI Datepicker alineado con format_php de la empresa.
     */
    function fv_display_date_format_js(): string
    {
        $fmt = fv_resolve_format_date_object();
        $php = is_object($fmt) ? trim((string) ($fmt->format_php ?? '')) : '';
        if ($php !== '') {
            return fv_date_format_jquery_ui($php);
        }
        if (is_object($fmt) && ! empty($fmt->format_js)) {
            return (string) $fmt->format_js;
        }

        return 'mm/dd/yy';
    }
}

if (! function_exists('fv_normalize_format_date_object')) {
    /**
     * Asegura format_js coherente con format_php (fuente de verdad del perfil).
     */
    function fv_normalize_format_date_object($formatDate): object
    {
        if (! is_object($formatDate)) {
            return fv_default_format_date_object();
        }
        $php = trim((string) ($formatDate->format_php ?? ''));
        if ($php === '') {
            $php = fv_display_date_format();
            $formatDate->format_php = $php;
        }
        $formatDate->format_js = fv_date_format_jquery_ui($php);

        return $formatDate;
    }
}

if (! function_exists('fv_default_format_date_object')) {
    /**
     * @return object{format_php: string, format_js: string, date_firstmonth: string, month_year: string, default: string}
     */
    function fv_default_format_date_object(): object
    {
        return (object) [
            'format_php' => 'm/d/Y',
            'format_js' => 'mm/dd/yy',
            'date_firstmonth' => 'm/01/Y',
            'month_year' => 'm/Y',
            'default' => '1',
        ];
    }
}

if (! function_exists('fv_resolve_format_date_object')) {
    /**
     * Objeto de formato de fecha de la empresa (format_php / format_js).
     */
    function fv_resolve_format_date_object(): object
    {
        if (function_exists('get_instance')) {
            $CI = get_instance();
            if (is_object($CI) && isset($CI->fv_format_date) && is_object($CI->fv_format_date)
                && ! empty($CI->fv_format_date->format_php)) {
                return $CI->fv_format_date;
            }
            if (is_object($CI) && isset($CI->Helper) && method_exists($CI->Helper, 'format_date')) {
                $fmt = $CI->Helper->format_date();
                if (is_object($fmt) && ! empty($fmt->format_php)) {
                    return $fmt;
                }
            }
        }

        return fv_default_format_date_object();
    }
}

if (! function_exists('fv_resolve_display_date_format')) {
    function fv_resolve_display_date_format(): string
    {
        $fmt = fv_resolve_format_date_object();
        $php = is_object($fmt) ? (string) ($fmt->format_php ?? '') : '';

        return $php !== '' ? $php : fv_display_date_format();
    }
}

if (! function_exists('fv_format_display_date')) {
    /**
     * @param mixed $date
     */
    function fv_format_display_date($date, ?string $format = null): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        $format = $format ?? fv_resolve_display_date_format();
        $ts = is_int($date) || (is_string($date) && ctype_digit($date))
            ? (int) $date
            : strtotime((string) $date);

        return $ts !== false ? date($format, $ts) : (string) $date;
    }
}

if (! function_exists('fv_format_display_datetime')) {
    /**
     * @param mixed $date
     */
    function fv_format_display_datetime($date, ?string $format = null): string
    {
        return fv_format_display_date($date, $format ?? fv_display_datetime_format());
    }
}

if (! function_exists('fv_date_format_bootstrap')) {
    /**
     * Convierte format_php (empresa) a tokens de bootstrap-datepicker.
     * Ej: m/d/Y → mm/dd/yyyy | d/m/Y → dd/mm/yyyy | Y-m-d → yyyy-mm-dd
     */
    function fv_date_format_bootstrap(?string $phpFormat = null): string
    {
        $php = $phpFormat ?? fv_resolve_display_date_format();
        $php = trim((string) $php);
        if ($php === '') {
            return 'mm/dd/yyyy';
        }

        // Formatos con texto libre (p. ej. "d de M de Y"): usar patrón numérico equivalente.
        if (preg_match('/\\\\d\\\\e|年|de M/u', $php) || preg_match('/[^\w\-\/\.\s\\\\]/u', str_replace(['\\d', '\\e'], '', $php))) {
            if (strpos($php, 'Y') !== false && strpos($php, 'm') === false && strpos($php, 'n') !== false) {
                return 'yyyy-mm-dd';
            }
            if (preg_match('/^m/', $php)) {
                return 'mm/dd/yyyy';
            }
            if (preg_match('/^d/', $php)) {
                return 'dd/mm/yyyy';
            }
            if (preg_match('/^Y/', $php)) {
                return 'yyyy-mm-dd';
            }
        }

        $out = '';
        $len = strlen($php);
        for ($i = 0; $i < $len; $i++) {
            if ($php[$i] === '\\' && $i + 1 < $len) {
                $out .= $php[$i + 1];
                $i++;
                continue;
            }
            $ch = $php[$i];
            switch ($ch) {
                case 'Y':
                    $out .= 'yyyy';
                    break;
                case 'y':
                    $out .= 'yy';
                    break;
                case 'm':
                    $out .= 'mm';
                    break;
                case 'n':
                    $out .= 'm';
                    break;
                case 'd':
                    $out .= 'dd';
                    break;
                case 'j':
                    $out .= 'd';
                    break;
                case 'M':
                    $out .= 'M';
                    break;
                default:
                    $out .= $ch;
            }
        }

        return $out !== '' ? $out : 'mm/dd/yyyy';
    }
}

if (! function_exists('fv_render_company_date_input')) {
    /**
     * Input de fecha visible en formato empresa + hidden ISO (Y-m-d) para el backend.
     *
     * @param array<string, scalar|null> $attrs Atributos extra del input visible (class, id, ...)
     */
    function fv_render_company_date_input(string $name, ?string $isoValue = null, array $attrs = []): string
    {
        $iso = trim((string) ($isoValue ?? ''));
        if ($iso !== '' && strtotime($iso) === false) {
            $iso = '';
        }
        $display = $iso !== '' ? fv_format_display_date($iso) : '';
        $idBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name) ?: 'fv_date';
        $displayId = (string) ($attrs['id'] ?? ($idBase . '_display'));
        $hiddenId = $idBase . '_iso';
        $extraClass = trim((string) ($attrs['class'] ?? ''));
        $class = trim('form-control fv-company-datepicker ' . $extraClass);
        unset($attrs['id'], $attrs['class'], $attrs['type'], $attrs['value'], $attrs['name']);

        $attrHtml = '';
        foreach ($attrs as $k => $v) {
            if ($v === null || $v === false) {
                continue;
            }
            $attrHtml .= ' ' . htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8') . '="'
                . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<input type="text" id="' . htmlspecialchars($displayId, ENT_QUOTES, 'UTF-8') . '"'
            . ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"'
            . ' value="' . htmlspecialchars($display, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-fv-iso-target="#' . htmlspecialchars($hiddenId, ENT_QUOTES, 'UTF-8') . '"'
            . ' autocomplete="off" readonly' . $attrHtml . '>'
            . '<input type="hidden" id="' . htmlspecialchars($hiddenId, ENT_QUOTES, 'UTF-8') . '"'
            . ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"'
            . ' value="' . htmlspecialchars($iso, ENT_QUOTES, 'UTF-8') . '">';
    }
}
