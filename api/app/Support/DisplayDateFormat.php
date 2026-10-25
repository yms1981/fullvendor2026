<?php

namespace App\Support;

use App\Models\FormatDate;
use Carbon\Carbon;
use DateTimeInterface;

/**
 * Formato estándar de fechas visibles al usuario: 12/31/2026 (MM/DD/YYYY → m/d/Y).
 */
final class DisplayDateFormat
{
    public const PHP_DATE = 'm/d/Y';

    public const PHP_DATETIME = 'm/d/Y H:i';

    public const PHP_DATETIME_SECONDS = 'm/d/Y H:i:s';

    /** jQuery UI Datepicker: yy = año de 4 dígitos. */
    public const JS_JQUERY_UI = 'mm/dd/yy';

    public const DATE_FIRST_MONTH = '01/m/Y';

    public const MONTH_YEAR = 'm/Y';

    /**
     * @return object{id?: int, format_php: string, format_js: string, date_firstmonth: string, month_year: string, default: string}
     */
    public static function defaultObject(): object
    {
        return (object) [
            'format_php' => self::PHP_DATE,
            'format_js' => self::JS_JQUERY_UI,
            'date_firstmonth' => self::DATE_FIRST_MONTH,
            'month_year' => self::MONTH_YEAR,
            'default' => '1',
        ];
    }

    public static function resolvePhpFormat(?string $fallback = null): string
    {
        $format = FormatDate::query()->where('default', 1)->value('format_php');

        return is_string($format) && $format !== ''
            ? $format
            : ($fallback ?? self::PHP_DATE);
    }

    /**
     * @param mixed $value
     */
    public static function format($value, ?string $format = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $format ??= self::resolvePhpFormat();

        try {
            if ($value instanceof DateTimeInterface) {
                return $value->format($format);
            }

            return Carbon::parse((string) $value)->format($format);
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    /**
     * @param mixed $value
     */
    public static function formatDateTime($value, ?string $format = null): string
    {
        return self::format($value, $format ?? self::PHP_DATETIME);
    }
}
