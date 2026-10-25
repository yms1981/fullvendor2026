<?php

defined('BASEPATH') or exit('No direct script access allowed');

class ModuleModel extends CI_Model
{
  
  public $company = array();
  public function __construct(){
    parent::__construct();
    $this->company = $this->Helper->get_company_info();
  }

  public function get_navbars($children = false){

    $id = $this->company->language_id;
    $query = "";
    $query_children = $children ? "father_id is not null" : "father_id is null";

    switch ($id) {
        case 1:
          $query = "SELECT father_id, navbar_id, name_eng as name, icon FROM navbars where $query_children order by `order` ASC";
        break;
        case 2:
          $query = "SELECT father_id, navbar_id, name_spa as name, icon FROM navbars where $query_children order by `order` ASC";
        break;
        default:
          $query = "SELECT father_id, navbar_id, name_eng as name, icon FROM navbars where $query_children order by `order` ASC";
        break;
    }
    return $this->db->query($query)->result();
  }

  public function get_modules(){

    $navbars = $this->get_navbars($children = false);
    $navbars_children = $this->get_navbars($children = true);
    
    foreach ($navbars_children as $key => $children) {
      
      $children_id = $children->father_id;
      
      if(in_array($children_id, array_column($navbars,'navbar_id'))){
        
        $index = array_search($children_id, array_column($navbars,'navbar_id'));      
        $navbars[$index]->children[] = $children;
      }
    }
    return $navbars;    
  }

    

  
}