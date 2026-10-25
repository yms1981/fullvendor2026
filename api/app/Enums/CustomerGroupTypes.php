<?php

namespace App\Enums;

enum CustomerGroupTypes: string
{
    case Increase = 'Increase on Price List %';
    case Decrease = 'Decrease on Price List %';

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Maps UI / import aliases to the DB enum value, or null for "none".
     * Returns false when the value is unrecognized.
     */
    public static function normalize(mixed $value): string|null|false
    {
        if ($value === null) {
            return null;
        }
        if (!is_scalar($value)) {
            return false;
        }

        $trimmed = trim((string) $value);
        $normalized = strtolower(preg_replace('/\s+/', ' ', $trimmed) ?? $trimmed);

        if ($normalized === '' || in_array($normalized, [
            '0', 'nothing', 'null', 'none', 'ninguno', 'ninguna', 'n/a', '-',
        ], true)) {
            return null;
        }

        // Unresolved template translation key from older downloads
        if ($normalized === 'import_example_group_pct_type') {
            return self::Increase->value;
        }

        foreach (self::cases() as $case) {
            if (strcasecmp($trimmed, $case->value) === 0) {
                return $case->value;
            }
        }

        if (
            str_contains($normalized, 'increase')
            || str_contains($normalized, 'increment')
            || str_contains($normalized, 'aumento')
        ) {
            return self::Increase->value;
        }

        if (
            str_contains($normalized, 'decrease')
            || str_contains($normalized, 'decrement')
            || str_contains($normalized, 'disminuc')
            || str_contains($normalized, 'diminui')
        ) {
            return self::Decrease->value;
        }

        return false;
    }
}
