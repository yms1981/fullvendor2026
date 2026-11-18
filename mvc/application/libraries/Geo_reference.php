<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Estados, ciudades y códigos postales desde la base de la aplicación.
 * El panel no tiene database.php; estos catálogos se leen aquí para que
 * los selectores no dependan de un token de API.
 */
class Geo_reference
{
    /** @var array<string, string> */
    private static $usStateAbbr = [
        'ALABAMA' => 'AL', 'ALASKA' => 'AK', 'ARIZONA' => 'AZ', 'ARKANSAS' => 'AR',
        'CALIFORNIA' => 'CA', 'COLORADO' => 'CO', 'CONNECTICUT' => 'CT', 'DELAWARE' => 'DE',
        'FLORIDA' => 'FL', 'GEORGIA' => 'GA', 'HAWAII' => 'HI', 'IDAHO' => 'ID',
        'ILLINOIS' => 'IL', 'INDIANA' => 'IN', 'IOWA' => 'IA', 'KANSAS' => 'KS',
        'KENTUCKY' => 'KY', 'LOUISIANA' => 'LA', 'MAINE' => 'ME', 'MARYLAND' => 'MD',
        'MASSACHUSETTS' => 'MA', 'MICHIGAN' => 'MI', 'MINNESOTA' => 'MN', 'MISSISSIPPI' => 'MS',
        'MISSOURI' => 'MO', 'MONTANA' => 'MT', 'NEBRASKA' => 'NE', 'NEVADA' => 'NV',
        'NEW HAMPSHIRE' => 'NH', 'NEW JERSEY' => 'NJ', 'NEW MEXICO' => 'NM', 'NEW YORK' => 'NY',
        'NORTH CAROLINA' => 'NC', 'NORTH DAKOTA' => 'ND', 'OHIO' => 'OH', 'OKLAHOMA' => 'OK',
        'OREGON' => 'OR', 'PENNSYLVANIA' => 'PA', 'RHODE ISLAND' => 'RI', 'SOUTH CAROLINA' => 'SC',
        'SOUTH DAKOTA' => 'SD', 'TENNESSEE' => 'TN', 'TEXAS' => 'TX', 'UTAH' => 'UT',
        'VERMONT' => 'VT', 'VIRGINIA' => 'VA', 'WASHINGTON' => 'WA', 'WEST VIRGINIA' => 'WV',
        'WISCONSIN' => 'WI', 'WYOMING' => 'WY', 'DISTRICT OF COLUMBIA' => 'DC',
    ];

    /** @var PDO|null */
    private $pdo;

    /**
     * @return array<int, array{id:int, name:string, code:string, full_name:string, country_id:int}>
     */
    public function searchStates(int $countryId, string $term = ''): array
    {
        if ($countryId <= 0) {
            return [];
        }
        $pdo = $this->pdo();
        if (!$pdo) {
            return [];
        }

        $sql = 'SELECT id, name, country_id FROM states WHERE deleted_at IS NULL AND country_id = ?';
        $binds = [$countryId];
        $sql .= ' ORDER BY name ASC LIMIT 500';

        $abbrByName = $this->stateAbbrMapForCountry($pdo, $countryId);
        $termUpper = strtoupper(trim($term));

        $rows = $this->rows($pdo, $sql, $binds);
        $out = [];
        foreach ($rows as $row) {
            $fullName = trim((string) $row['name']);
            $fullUpper = strtoupper($fullName);
            $code = $abbrByName[$fullUpper] ?? (self::$usStateAbbr[$fullUpper] ?? '');
            $code = strtoupper(trim((string) $code));
            // Select: "ND - NORTH DAKOTA" (reconocible por sigla o nombre).
            $label = $code !== ''
                ? ($code . ' - ' . $fullUpper)
                : $fullUpper;

            if ($termUpper !== '') {
                $hay = $label . ' ' . $code . ' ' . $fullUpper;
                if (strpos($hay, $termUpper) === false) {
                    continue;
                }
            }

            $out[] = [
                'id' => (int) $row['id'],
                'name' => $label,
                'code' => $code,
                'full_name' => $fullUpper,
                'country_id' => (int) $row['country_id'],
            ];
        }

        return $out;
    }

    /**
     * Mapa nombre de estado → sigla (admin_code1) desde postal_codes + mapa US.
     *
     * @return array<string, string> key = UPPER(admin_name1|state.name)
     */
    private function stateAbbrMapForCountry(PDO $pdo, int $countryId): array
    {
        $map = self::$usStateAbbr;
        if (!$this->tableExists($pdo, 'postal_codes')) {
            return $map;
        }

        $country = $this->one($pdo, 'SELECT sortname FROM countries WHERE id = ?', [$countryId]);
        $iso2 = $country ? strtoupper(substr(trim((string) $country['sortname']), 0, 2)) : '';
        if ($iso2 === '') {
            return $map;
        }

        $rows = $this->rows(
            $pdo,
            'SELECT DISTINCT admin_name1, admin_code1
             FROM postal_codes
             WHERE country_iso2 = ?
               AND admin_name1 IS NOT NULL AND TRIM(admin_name1) <> \'\'
               AND admin_code1 IS NOT NULL AND TRIM(admin_code1) <> \'\'
             LIMIT 1000',
            [$iso2]
        );
        foreach ($rows as $row) {
            $name = strtoupper(trim((string) ($row['admin_name1'] ?? '')));
            $code = strtoupper(trim((string) ($row['admin_code1'] ?? '')));
            if ($name !== '' && $code !== '' && !isset($map[$name])) {
                $map[$name] = $code;
            }
        }

        return $map;
    }

    /**
     * @return array<int, array{id:int, name:string, state_id:int, state_name:string}>
     */
    public function searchCities(int $stateId, int $countryId, string $term = ''): array
    {
        if ($stateId <= 0 && $countryId <= 0) {
            return [];
        }
        $pdo = $this->pdo();
        if (!$pdo) {
            return [];
        }

        $term = trim($term);
        if ($stateId > 0) {
            $sql = 'SELECT c.id, c.name, c.state_id, s.name AS state_name
                    FROM cities c
                    LEFT JOIN states s ON s.id = c.state_id
                    WHERE c.deleted_at IS NULL AND c.state_id = ?';
            $binds = [$stateId];
        } else {
            $sql = 'SELECT c.id, c.name, c.state_id, s.name AS state_name
                    FROM cities c
                    INNER JOIN states s ON s.id = c.state_id AND s.deleted_at IS NULL
                    WHERE c.deleted_at IS NULL AND s.country_id = ?';
            $binds = [$countryId];
        }
        if ($term !== '') {
            $sql .= ' AND UPPER(c.name) LIKE ?';
            $binds[] = strtoupper($term) . '%';
        }
        $sql .= ' ORDER BY c.name ASC LIMIT 800';

        $rows = $this->rows($pdo, $sql, $binds);
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'state_id' => (int) $row['state_id'],
                'state_name' => (string) ($row['state_name'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * ZIP: si hay estado → filtra por estado; si no → todos del país.
     * Cada ítem incluye ciudad/estado para poder asignarlos al elegir el ZIP.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchZips(int $stateId, int $cityId, int $countryId, string $term = ''): array
    {
        $pdo = $this->pdo();
        if (!$pdo || !$this->tableExists($pdo, 'postal_codes')) {
            return [];
        }

        $term = trim($term);
        $stateName = '';
        $stateAbbr = '';
        $iso2 = '';
        $cityName = '';

        if ($cityId > 0) {
            $city = $this->one($pdo, 'SELECT id, name, state_id FROM cities WHERE id = ? AND deleted_at IS NULL', [$cityId]);
            if ($city) {
                $cityName = strtoupper(trim((string) $city['name']));
                if ($stateId <= 0) {
                    $stateId = (int) $city['state_id'];
                }
            }
        }

        if ($stateId > 0) {
            $state = $this->one($pdo, 'SELECT id, name, country_id FROM states WHERE id = ? AND deleted_at IS NULL', [$stateId]);
            if ($state) {
                $stateName = strtoupper(trim((string) $state['name']));
                $stateAbbr = self::$usStateAbbr[$stateName] ?? '';
                if ($countryId <= 0) {
                    $countryId = (int) $state['country_id'];
                }
            }
        }

        if ($countryId > 0) {
            $country = $this->one($pdo, 'SELECT sortname FROM countries WHERE id = ?', [$countryId]);
            if ($country) {
                $iso2 = strtoupper(substr(trim((string) $country['sortname']), 0, 2));
            }
        }

        // Sin país/estado/ciudad/término no hay filtro útil.
        if ($stateId <= 0 && $cityId <= 0 && $countryId <= 0 && $iso2 === '') {
            return [];
        }

        // Con estado: filtrar por estado. Sin estado: todo el país.
        $limit = ($stateId > 0) ? 200 : 150;
        $rows = $this->queryZips($pdo, $iso2, $countryId, $stateId, $stateName, $stateAbbr, 0, '', $term, $limit);

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function queryZips(
        PDO $pdo,
        string $iso2,
        int $countryId,
        int $stateId,
        string $stateName,
        string $stateAbbr,
        int $cityId,
        string $cityName,
        string $term,
        int $limit = 200
    ): array {
        $sql = 'SELECT postal_code, place_name, admin_name1, admin_code1, state_id, city_id
                FROM postal_codes WHERE 1=1';
        $binds = [];

        if ($iso2 !== '') {
            $sql .= ' AND country_iso2 = ?';
            $binds[] = $iso2;
        } elseif ($countryId > 0) {
            $sql .= ' AND country_id = ?';
            $binds[] = $countryId;
        }

        if ($stateId > 0) {
            $sql .= ' AND (state_id = ?';
            $binds[] = $stateId;
            if ($stateName !== '') {
                $sql .= ' OR UPPER(admin_name1) = ?';
                $binds[] = $stateName;
            }
            if ($stateAbbr !== '') {
                $sql .= ' OR UPPER(admin_code1) = ?';
                $binds[] = $stateAbbr;
            }
            $sql .= ')';
        }

        if ($cityId > 0 && $cityName !== '') {
            $sql .= ' AND (city_id = ? OR UPPER(place_name) = ?)';
            $binds[] = $cityId;
            $binds[] = $cityName;
        }

        if ($term !== '') {
            // Reconocer por ZIP (preferente) o por ciudad.
            $sql .= ' AND (postal_code LIKE ? OR UPPER(place_name) LIKE ?)';
            $binds[] = $term . '%';
            $binds[] = strtoupper($term) . '%';
        }

        $limit = max(1, min(500, $limit));
        $sql .= ' ORDER BY postal_code ASC LIMIT ' . $limit;

        $rows = $this->rows($pdo, $sql, $binds);
        $out = [];
        $seen = [];
        foreach ($rows as $row) {
            $code = trim((string) ($row['postal_code'] ?? ''));
            $place = strtoupper(trim((string) ($row['place_name'] ?? '')));
            $adminName = strtoupper(trim((string) ($row['admin_name1'] ?? '')));
            $adminCode = strtoupper(trim((string) ($row['admin_code1'] ?? '')));
            if ($adminCode === '' && $adminName !== '' && isset(self::$usStateAbbr[$adminName])) {
                $adminCode = self::$usStateAbbr[$adminName];
            }
            $key = $code;
            if ($code === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $label = $place !== '' ? ($code . ' - ' . $place) : $code;
            $out[] = [
                'id' => $code,
                'name' => $label,
                'postal_code' => $code,
                'place_name' => $place,
                'city_name' => $place,
                'city_id' => (int) ($row['city_id'] ?? 0),
                'state_id' => (int) ($row['state_id'] ?? 0),
                'state_code' => $adminCode,
                'state_name' => $adminName,
            ];
        }

        return $out;
    }

    private function pdo(): ?PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $env = $this->readApiEnv();
        $host = $env['DB_HOST'] ?? '';
        $name = $env['DB_DATABASE'] ?? '';
        $user = $env['DB_USERNAME'] ?? '';
        if ($host === '' || $name === '' || $user === '') {
            return null;
        }
        $port = $env['DB_PORT'] ?? '3306';
        $pass = $env['DB_PASSWORD'] ?? '';

        try {
            $this->pdo = new PDO(
                'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4',
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (Throwable $e) {
            log_message('error', 'Geo_reference connection failed: ' . $e->getMessage());
            $this->pdo = null;
        }

        return $this->pdo;
    }

    /**
     * @return array<string, string>
     */
    private function readApiEnv(): array
    {
        $path = dirname(APPPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . '.env';
        if (!is_readable($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        if ($raw === false || $raw === '') {
            return [];
        }
        $out = [];
        foreach (preg_split("/\r\n|\n|\r/", $raw) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $value = trim($value, "\"'");
            }
            if ($key !== '') {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        $row = $this->one(
            $pdo,
            'SELECT 1 AS ok FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
            [$table]
        );

        return $row !== null;
    }

    /**
     * @param array<int, mixed> $binds
     * @return array<string, mixed>|null
     */
    private function one(PDO $pdo, string $sql, array $binds): ?array
    {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($binds);
            $row = $stmt->fetch();

            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            log_message('error', 'Geo_reference query failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param array<int, mixed> $binds
     * @return array<int, array<string, mixed>>
     */
    private function rows(PDO $pdo, string $sql, array $binds): array
    {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($binds);
            $rows = $stmt->fetchAll();

            return is_array($rows) ? $rows : [];
        } catch (Throwable $e) {
            log_message('error', 'Geo_reference query failed: ' . $e->getMessage());

            return [];
        }
    }
}
