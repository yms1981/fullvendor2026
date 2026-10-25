<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Descompone direcciones “completas” pegadas en un solo campo
 * y deja: address (solo calle), state, city, zip, country.
 * Orden de concatenación UI: Address, State, City, ZipCode, Country.
 */
class CustomerAddressNormalizerService
{
    /** @var array<string, string> US abbrev => full name */
    private const US_STATES = [
        'AL' => 'ALABAMA', 'AK' => 'ALASKA', 'AZ' => 'ARIZONA', 'AR' => 'ARKANSAS',
        'CA' => 'CALIFORNIA', 'CO' => 'COLORADO', 'CT' => 'CONNECTICUT', 'DE' => 'DELAWARE',
        'FL' => 'FLORIDA', 'GA' => 'GEORGIA', 'HI' => 'HAWAII', 'ID' => 'IDAHO',
        'IL' => 'ILLINOIS', 'IN' => 'INDIANA', 'IA' => 'IOWA', 'KS' => 'KANSAS',
        'KY' => 'KENTUCKY', 'LA' => 'LOUISIANA', 'ME' => 'MAINE', 'MD' => 'MARYLAND',
        'MA' => 'MASSACHUSETTS', 'MI' => 'MICHIGAN', 'MN' => 'MINNESOTA', 'MS' => 'MISSISSIPPI',
        'MO' => 'MISSOURI', 'MT' => 'MONTANA', 'NE' => 'NEBRASKA', 'NV' => 'NEVADA',
        'NH' => 'NEW HAMPSHIRE', 'NJ' => 'NEW JERSEY', 'NM' => 'NEW MEXICO', 'NY' => 'NEW YORK',
        'NC' => 'NORTH CAROLINA', 'ND' => 'NORTH DAKOTA', 'OH' => 'OHIO', 'OK' => 'OKLAHOMA',
        'OR' => 'OREGON', 'PA' => 'PENNSYLVANIA', 'RI' => 'RHODE ISLAND', 'SC' => 'SOUTH CAROLINA',
        'SD' => 'SOUTH DAKOTA', 'TN' => 'TENNESSEE', 'TX' => 'TEXAS', 'UT' => 'UTAH',
        'VT' => 'VERMONT', 'VA' => 'VIRGINIA', 'WA' => 'WASHINGTON', 'WV' => 'WEST VIRGINIA',
        'WI' => 'WISCONSIN', 'WY' => 'WYOMING', 'DC' => 'DISTRICT OF COLUMBIA',
    ];

    /**
     * @param  array{
     *   address?: string|null,
     *   country?: string|null,
     *   state?: string|null,
     *   city?: string|null,
     *   zip?: string|null
     * }  $parts
     * @return array{address: ?string, country: ?string, state: ?string, city: ?string, zip: ?string}
     */
    public function normalize(array $parts): array
    {
        $origAddress = $this->upper($parts['address'] ?? null);
        $country = $this->upper($parts['country'] ?? null);
        $state = $this->normalizeStateName($this->upper($parts['state'] ?? null));
        $city = $this->upper($parts['city'] ?? null);
        $zip = $this->normalizeZip($this->upper($parts['zip'] ?? null));

        if ($origAddress === null || $origAddress === '') {
            return compact('country', 'state', 'city', 'zip') + ['address' => null];
        }

        $working = $origAddress;

        // País al final del texto.
        $working = preg_replace(
            '/[,\s]+(UNITED STATES OF AMERICA|UNITED STATES|USA|U\.S\.A\.|U\.S\.|US)\s*$/i',
            '',
            $working
        ) ?? $working;
        $working = $this->clean($working);
        if ($country === null) {
            $country = 'UNITED STATES';
        }

        // 1) ZIP al final (o repetido).
        $parsedZip = null;
        if (preg_match('/(?:^|[\s,])(\d{5})(?:-\d{4})?(?:\s+\1)?\s*$/', $working, $m)) {
            $parsedZip = $m[1];
            $working = $this->clean(preg_replace(
                '/(?:^|[\s,])'.preg_quote($m[1], '/').'(?:-\d{4})?(?:\s+'.preg_quote($m[1], '/').')?\s*$/',
                '',
                $working
            ) ?? $working);
        }
        if ($zip === null && $parsedZip) {
            $zip = $parsedZip;
        }

        // 2) Estado abreviatura al final: "... IL" o "... , IL"
        $parsedStateAbbr = null;
        if (preg_match('/(?:^|[\s,])([A-Z]{2})\s*$/', $working, $m)) {
            $abbr = strtoupper($m[1]);
            if (isset(self::US_STATES[$abbr])) {
                $parsedStateAbbr = $abbr;
                $working = $this->clean(substr($working, 0, -strlen($m[0])));
            }
        }
        if ($state === null && $parsedStateAbbr) {
            $state = self::US_STATES[$parsedStateAbbr];
        }

        // 3) Formato con comas: "CALLE, CIUDAD" (ciudad = último segmento).
        if (str_contains($working, ',')) {
            $segments = array_values(array_filter(array_map(
                fn ($s) => $this->clean($s),
                explode(',', $working)
            )));
            if (count($segments) >= 2) {
                $maybeCity = $segments[count($segments) - 1];
                // Evitar tomar "SUITE 104" como ciudad si aún hay más contexto; ciudad suele ser palabra(s) sin dígitos de suite.
                if ($maybeCity !== '' && ! preg_match('/\bSUITE\b|\bSTE\b|\bAPT\b|\bUNIT\b|#/i', $maybeCity)) {
                    if ($city === null) {
                        $city = $maybeCity;
                    }
                    // Si la ciudad parseada coincide con la conocida, o city estaba vacío, quitar último segmento.
                    if ($city !== null && $this->sameToken($city, $maybeCity)) {
                        array_pop($segments);
                        $working = implode(', ', $segments);
                    }
                }
            }
        }

        // 4) Enriquecer con postal_codes (fuente confiable ciudad/estado por ZIP).
        $postalHit = null;
        if ($zip !== null && Schema::hasTable('postal_codes')) {
            $postalHit = DB::table('postal_codes')
                ->where('country_iso2', 'US')
                ->where('postal_code', $zip)
                ->orderBy('id')
                ->first(['place_name', 'admin_name1', 'admin_code1']);
        }
        if ($postalHit) {
            $pcCity = $this->upper($postalHit->place_name);
            $pcState = $this->upper($postalHit->admin_name1)
                ?: (isset(self::US_STATES[strtoupper((string) ($postalHit->admin_code1 ?? ''))])
                    ? self::US_STATES[strtoupper((string) $postalHit->admin_code1)]
                    : null);

            // Si el address aún contiene la ciudad del ZIP, usarla y limpiar.
            if ($pcCity && (stripos($working, $pcCity) !== false || $city === null || $this->sameToken($city, $pcCity))) {
                $city = $pcCity;
                $working = $this->stripTrailingPhrase($working, $pcCity);
            } elseif ($city === null && $pcCity) {
                $city = $pcCity;
            }

            if ($pcState) {
                // Preferir estado del ZIP (corrige basura tipo state=city).
                if ($state === null || $state === $city || strlen((string) $state) <= 2) {
                    $state = $pcState;
                } elseif ($parsedZip || $parsedStateAbbr) {
                    // El ZIP venía en el address → confiar en postal.
                    $state = $pcState;
                }
            }
        }

        // 5) Sin comas: si city conocida está al final del working, quitarla.
        if ($city) {
            $working = $this->stripTrailingPhrase($working, $city);
        }
        if ($state) {
            $working = $this->stripTrailingPhrase($working, $state);
            $abbr = array_search($state, self::US_STATES, true);
            if (is_string($abbr)) {
                $working = $this->stripTrailingPhrase($working, $abbr);
            }
        }
        if ($zip) {
            $working = $this->stripTrailingPhrase($working, $zip);
        }

        $working = $this->clean($working);
        // No dejar address vacío si el parseo fue agresivo: conservar calle original menos city/state/zip.
        if ($working === '') {
            $fallback = $origAddress;
            foreach (array_filter([$zip, $city, $state, $parsedStateAbbr, 'UNITED STATES', 'USA', 'US']) as $tok) {
                $fallback = $this->stripTrailingPhrase((string) $fallback, (string) $tok);
            }
            $working = $this->clean($fallback);
        }

        return [
            'address' => $working !== '' ? $working : $origAddress,
            'country' => $country,
            'state' => $state,
            'city' => $city,
            'zip' => $zip,
        ];
    }

    /**
     * @param  array<string, mixed>  $columns
     * @return array<string, mixed>
     */
    public function normalizeCustomerColumns(array $columns): array
    {
        foreach (['commercial', 'dispatch'] as $prefix) {
            $addrKey = $prefix.'_address';
            if (! array_key_exists($addrKey, $columns) && ! array_key_exists($prefix.'_zip_code', $columns)) {
                continue;
            }

            $normalized = $this->normalize([
                'address' => $columns[$addrKey] ?? null,
                'country' => $columns[$prefix.'_country'] ?? null,
                'state' => $columns[$prefix.'_state'] ?? null,
                'city' => $columns[$prefix.'_city'] ?? null,
                'zip' => $columns[$prefix.'_zip_code'] ?? null,
            ]);

            $columns[$addrKey] = $normalized['address'];
            $columns[$prefix.'_country'] = $normalized['country'];
            $columns[$prefix.'_state'] = $normalized['state'];
            $columns[$prefix.'_city'] = $normalized['city'];
            $columns[$prefix.'_zip_code'] = $normalized['zip'];
            $columns[$prefix.'_delivery_address'] = $this->concatDisplay(
                $normalized['address'],
                $normalized['state'],
                $normalized['city'],
                $normalized['zip'],
                $normalized['country']
            );
        }

        return $columns;
    }

    public function concatDisplay(
        ?string $address,
        ?string $state,
        ?string $city,
        ?string $zip,
        ?string $country
    ): ?string {
        $parts = [];
        foreach ([$address, $state, $city, $zip, $country] as $seg) {
            $t = trim((string) $seg);
            if ($t !== '') {
                $parts[] = $this->upper($t) ?? $t;
            }
        }

        return $parts === [] ? null : implode(', ', $parts);
    }

    private function normalizeZip(?string $zip): ?string
    {
        if ($zip === null || $zip === '') {
            return null;
        }
        if (preg_match('/\b(\d{5})(?:-\d{4})?\b/', $zip, $m)) {
            return $m[1];
        }

        return $zip;
    }

    private function normalizeStateName(?string $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }
        if (isset(self::US_STATES[$state])) {
            return self::US_STATES[$state];
        }

        return $state;
    }

    private function stripTrailingPhrase(string $haystack, string $needle): string
    {
        $needle = $this->clean($needle);
        $haystack = $this->clean($haystack);
        if ($needle === '' || $haystack === '') {
            return $haystack;
        }
        $q = preg_quote($needle, '/');
        $out = preg_replace('/(?:^|[\s,]+)'.$q.'\s*$/i', '', $haystack);

        return $this->clean($out ?? $haystack);
    }

    private function sameToken(?string $a, ?string $b): bool
    {
        return $this->upper($a) === $this->upper($b);
    }

    private function clean(?string $s): string
    {
        $s = trim((string) $s);
        $s = trim($s, " \t,");
        $s = preg_replace('/\s{2,}/', ' ', $s) ?? $s;

        return trim($s, " \t,");
    }

    private function upper(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        return mb_strtoupper($s, 'UTF-8');
    }
}
