<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class SearchCity extends Company_Controller
{
    /**
     * Ciudades del estado seleccionado, o del país si todavía no hay estado.
     */
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        $term = trim((string) ($this->input->get('term') ?: ''));
        $stateId = (int) ($this->input->get('state_id') ?: 0);
        $countryId = (int) ($this->input->get('country_id') ?: 0);

        $this->load->library('geo_reference');
        $rows = $this->geo_reference->searchCities($stateId, $countryId, $term);

        echo json_encode($rows);
    }
}
