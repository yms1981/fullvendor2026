<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Códigos postales filtrados por estado, ciudad y prefijo.
 */
class SearchZip extends Company_Controller
{
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        $cityId = (int) ($this->input->get('city_id') ?: 0);
        $stateId = (int) ($this->input->get('state_id') ?: 0);
        $countryId = (int) ($this->input->get('country_id') ?: 0);
        $term = trim((string) ($this->input->get('term') ?: $this->input->get('q') ?: ''));

        $this->load->library('geo_reference');
        $rows = $this->geo_reference->searchZips($stateId, $cityId, $countryId, $term);

        echo json_encode($rows);
    }
}
