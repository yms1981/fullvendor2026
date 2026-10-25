<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class SearchCity extends Company_Controller
{
    /**
     * Ciudades filtradas por state_id o, si no hay estado, por country_id del país seleccionado.
     */
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        $term = $this->input->get('term');
        $stateId = $this->input->get('state_id');
        $countryId = $this->input->get('country_id');

        $stateId = ($stateId !== null && $stateId !== '') ? (int) $stateId : null;
        $countryId = ($countryId !== null && $countryId !== '') ? (int) $countryId : null;

        if (($stateId === null || $stateId <= 0) && ($countryId === null || $countryId <= 0)) {
            echo json_encode([]);
            return;
        }

        $locationParams = [
            'all' => 1,
            'orderProperty' => 'name',
            'orderDirection' => 'asc',
            'props' => ['id', 'name', 'state_id'],
            'relations' => ['state:id,name,country_id'],
        ];

        if ($stateId !== null && $stateId > 0) {
            $locationParams['state_id'] = $stateId;
        } else {
            $locationParams['country_id'] = $countryId;
        }

        if ($term !== null && $term !== '') {
            $locationParams['name'] = $term;
        }

        $response = $this->getObjectFromApi('city', $locationParams);
        $list = [];

        if (!$this->hasError($response)) {
            if (is_object($response) && isset($response->data)) {
                $list = is_array($response->data) ? $response->data : [];
            } elseif (is_array($response) && !isset($response['error']) && isset($response['data'])) {
                $list = is_array($response['data']) ? $response['data'] : [];
            } elseif (is_array($response) && !isset($response['error'])) {
                $list = $response;
            }
        }

        $out = [];
        foreach ($list as $row) {
            $row = is_object($row) ? $row : (object) $row;
            $state = isset($row->state) ? (is_object($row->state) ? $row->state : (object) $row->state) : null;
            $out[] = [
                'id' => (int) ($row->id ?? 0),
                'name' => (string) ($row->name ?? ''),
                'state_id' => (int) ($row->state_id ?? ($state->id ?? 0)),
                'state_name' => (string) ($state->name ?? ''),
            ];
        }

        echo json_encode($out);
    }
}
