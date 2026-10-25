<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Docs extends MY_Controller
{
    public function index()
    {

        $daToken = $this->getUserToken();
        $options['headers'] = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $daToken
        ];
        //$options['json']=$params;

        $qs = $this->input->server('QUERY_STRING');
        $qs = $qs ? '?' . $qs : '';

        $base = preg_replace('#/api/?$#', '', $this->API_V2_ENTRYPOINT);
        $uri = $base . "/docs" . $qs;

        $response = $this->getHttpClient()->request(
            strtoupper('GET'),
            $uri,
            $options
        );
        print $response->getBody();
    }
}
