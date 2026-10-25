<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
//use Dompdf\Dompdf;

class Second extends My_Controller
{
    private $processingStrategy;

	public function __construct()
	{
        
        parent::__construct();
        $this->load->model('StandardOrderProcessing');
        $this->processingStrategy = new StandardOrderProcessing();
	}
    
    public function processOrder() {
        
        $this->processingStrategy->processOrder();
    }
}