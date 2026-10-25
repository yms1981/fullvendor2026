<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Códigos postales filtrados por estado (principal) / ciudad / país (tabla postal_codes).
 * Lista ZIP según el ESTADO seleccionado; term filtra por prefijo del código.
 */
class SearchZip extends Company_Controller
{
    /** Abreviaturas US → nombre (admin_code1 en GeoNames). */
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

    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        $cityId = (int) ($this->input->get('city_id') ?: 0);
        $stateId = (int) ($this->input->get('state_id') ?: 0);
        $countryId = (int) ($this->input->get('country_id') ?: 0);
        $term = trim((string) ($this->input->get('term') ?: $this->input->get('q') ?: ''));

        if ($cityId <= 0 && $stateId <= 0 && $countryId <= 0) {
            echo json_encode([]);
            return;
        }

        $this->load->database();
        if (!$this->db->table_exists('postal_codes')) {
            echo json_encode([]);
            return;
        }

        $cityName = '';
        $stateName = '';
        $stateAbbr = '';
        $iso2 = '';

        if ($cityId > 0) {
            $city = $this->db->select('id, name, state_id')->from('cities')->where('id', $cityId)->limit(1)->get()->row();
            if ($city) {
                $cityName = strtoupper(trim((string) $city->name));
                $stateId = $stateId > 0 ? $stateId : (int) $city->state_id;
            }
        }

        if ($stateId > 0) {
            $state = $this->db->select('id, name, country_id')->from('states')->where('id', $stateId)->limit(1)->get()->row();
            if ($state) {
                $stateName = strtoupper(trim((string) $state->name));
                $stateAbbr = self::$usStateAbbr[$stateName] ?? '';
                $countryId = $countryId > 0 ? $countryId : (int) $state->country_id;
            }
        }

        if ($countryId > 0) {
            $country = $this->db->select('id, sortname')->from('countries')->where('id', $countryId)->limit(1)->get()->row();
            if ($country) {
                $iso2 = strtoupper(substr(trim((string) $country->sortname), 0, 2));
            }
        }

        // Prioridad: estado (listado amplio). city_id opcional solo si se pide explícitamente
        // y no hay state_id (compatibilidad). Con state_id siempre filtramos por estado.
        $sql = 'SELECT DISTINCT postal_code, place_name FROM postal_codes WHERE 1=1';
        $binds = [];

        if ($stateId > 0 && $stateName !== '') {
            $sql .= ' AND (state_id = ? OR UPPER(TRIM(admin_name1)) = ?';
            $binds[] = $stateId;
            $binds[] = $stateName;
            if ($stateAbbr !== '') {
                $sql .= ' OR UPPER(TRIM(admin_code1)) = ?';
                $binds[] = $stateAbbr;
            }
            $sql .= ')';
            if ($iso2 !== '') {
                $sql .= ' AND country_iso2 = ?';
                $binds[] = $iso2;
            }
        } elseif ($cityId > 0 && $cityName !== '') {
            $sql .= ' AND (city_id = ? OR (UPPER(TRIM(place_name)) = ?';
            $binds[] = $cityId;
            $binds[] = $cityName;
            if ($stateName !== '') {
                $sql .= ' AND UPPER(TRIM(admin_name1)) = ?';
                $binds[] = $stateName;
            }
            if ($iso2 !== '') {
                $sql .= ' AND country_iso2 = ?';
                $binds[] = $iso2;
            }
            $sql .= ')';
        } elseif ($iso2 !== '' || $countryId > 0) {
            $sql .= ' AND (country_id = ? OR country_iso2 = ?)';
            $binds[] = $countryId;
            $binds[] = $iso2 !== '' ? $iso2 : '__';
        } else {
            echo json_encode([]);
            return;
        }

        if ($term !== '') {
            $sql .= ' AND postal_code LIKE ?';
            $binds[] = $term . '%';
        }

        $sql .= ' ORDER BY postal_code ASC LIMIT 1500';
        $rows = $this->db->query($sql, $binds)->result();

        $out = [];
        $seen = [];
        foreach ($rows as $row) {
            $code = trim((string) ($row->postal_code ?? ''));
            if ($code === '' || isset($seen[$code])) {
                continue;
            }
            $seen[$code] = true;
            $out[] = [
                'id' => $code,
                'name' => $code,
                'postal_code' => $code,
                'place_name' => (string) ($row->place_name ?? ''),
            ];
        }

        echo json_encode(array_values($out));
    }
}
