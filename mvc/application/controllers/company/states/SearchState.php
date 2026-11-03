<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class SearchState extends Company_Controller
{
    /**
     * Estados del país seleccionado.
     */
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        $term = trim((string) ($this->input->get('term') ?: ''));
        $countryId = (int) ($this->input->get('country_id') ?: 0);

        $this->load->library('geo_reference');
        $rows = $this->geo_reference->searchStates($countryId, $term);

        echo json_encode($rows);
    }
}
