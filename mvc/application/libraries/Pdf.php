<?php 
if (!defined('BASEPATH')) exit('No direct script access allowed');  
 
require_once 'dompdf/autoload.inc.php';


use Dompdf\Adapter\CPDF;      
use Dompdf\Dompdf;
use Dompdf\Exception;

class Pdf extends Dompdf
{
 public function __construct()
 {
   parent::__construct();
 } 
}

?>