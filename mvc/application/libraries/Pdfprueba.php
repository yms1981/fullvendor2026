<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require('Fpdf.php');

class Pdfprueba extends Fpdf
{
// Cabecera de página

    function Header()
    {

    // Logo
    $this->Image('https://www.fullvendor.com/company/images/logo',10,8,33);
    // Arial bold 15
    $this->SetFont('Arial','B',15);
    // Movernos a la derecha
    $this->Cell(80);
    // Título
    $this->Cell(30,10,'Title',1,0,'C');
    // Salto de línea
    $this->Ln(20);
    }

// Pie de página
    function Footer()
    {
    // Posición: a 1,5 cm del final
        $this->SetY(-15);
    // Arial italic 8
        $this->SetFont('Arial','I',8);
    // Número de página
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}


?>
