<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** @property Pdf $pdf */
class PrintModel extends CI_Model
{
    public $company = array();

    function __construct()
	{
        parent::__construct();
        $this->company = $this->Helper->get_company_info();
        require 'vendor/autoload.php';
    }

    public function print_csv($header = null, $body = null,$separador = null,$filename = null){


        $filename = $filename?"$filename":"Default.csv";


        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=$filename");


        $file = fopen('php://output', 'w');
        fputcsv($file, $header, $separador);


		foreach($body as $row){
			fputcsv($file,$row,$separador);
		}

        fclose($file);

    }

    public function print_pdf($header = null, $body = null, $filename= null,$paper = "portrait", $font_size = "8px",$data_extra = []){

		$filename = $filename?"$filename":"Default.pdf";
		$format_date = $this->Helper->format_date();
		$date = date($format_date->format_php, strtotime(date("Y-m-d")));

		header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'. $filename . '"');

        // Carga el HTML en Dompdf
		$info = array("thead" => $header,
					  "tbody" => $body,
					  "title" => $filename,
					  "font_size" => $font_size,
					  "data_extra"=>$data_extra,
					  "company" => $this->company,
					  "lang" => $this->HelperLanguage->inventory(),
					  "date" => $date
					);
        $html = $this->load->view('company/order/template_exportpdf', $info, TRUE);
		//$html = $this->load->view('company/order/pdf_impresion', $info, TRUE);

		$this->load->library('pdf');
		$this->pdf->set_option('isRemoteEnabled',TRUE);
        $this->pdf->loadHtml($html);

		$this->pdf->setPaper('A4', $paper);
		$this->pdf->render();

        echo $this->pdf->output();
		exit;
    }

    public function print_excel($header = null, $body = null, $filename= null){

        $spreadsheet = new  Spreadsheet();

		$sheet = $spreadsheet->getActiveSheet();

		$sheet->fromArray([$header], null, 'A1');

		// Escribir los datos en el archivo XLSX
		$row = 2; // Comenzar desde la segunda fila
		foreach ($body as $row_data) {

			$sheet->fromArray([$row_data], null, 'A' . $row);
			$row++;
		}

		// Crear un objeto Writer para guardar el archivo XLSX
		$writer = new Xlsx($spreadsheet);

		// Configurar las cabeceras HTTP para la descarga del archivo
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="' . $filename .'"');
		header('Cache-Control: max-age=0');

		// Salida del archivo
		$writer->save('php://output');
		exit;
    }

}
