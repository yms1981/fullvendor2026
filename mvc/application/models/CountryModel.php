<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CountryModel extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}

    public function get_countriesAlphabetic(){
        
        $query = "SELECT * FROM countries ORDER BY countries.name ASC";        
        return $this->db->query($query)->result();

    }

}