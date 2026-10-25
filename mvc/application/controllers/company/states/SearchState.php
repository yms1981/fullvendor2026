<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class SearchState extends Company_Controller
{
    /**
     * Lista estados/departamentos del país seleccionado (country_id).
     * GET country_id (obligatorio) para filtrar por país.
     */
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        $term = $this->input->get('term');
        $countryId = $this->input->get('country_id');

        $countryId = ($countryId !== null && $countryId !== '') ? (int) $countryId : null;
        if ($countryId === null || $countryId <= 0) {
            echo json_encode([]);
            return;
        }

        $locationParams = [
            'all' => 1,
            'orderProperty' => 'name',
            'orderDirection' => 'asc',
            'props' => ['id', 'name', 'country_id'],
            'country_id' => $countryId
        ];
        if ($term !== null && $term !== '') {
            $locationParams['name'] = $term;
        }

        $response = $this->getObjectFromApi('state', $locationParams);
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

        echo json_encode($list);
    }
}
