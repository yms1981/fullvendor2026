<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
//use Dompdf\Dompdf;

class Strategy extends My_Controller
{
    private $processingStrategy;

	public function __construct()
	{
        
        parent::__construct();
        $this->load->model('ExpressOrderProcessing');
        $this->processingStrategy = new ExpressOrderProcessing();
	}
    
    public function processOrder() {
        
        $this->processingStrategy->processOrder();
    }
}