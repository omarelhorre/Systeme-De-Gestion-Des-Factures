<?php

    function genererPdfContrat(array $client)
    {

    
    include_once ROOT.'vendor/autoload.php';
    ob_start();
    include ROOT.'IHM/dompdfContent/contratpdf.php';
    $html = ob_get_clean();
    $pdf = new \Dompdf\Dompdf();
    $pdf->loadHtml($html,'UTF-8');
    $pdf->setPaper('A4');
    $pdf->render();
    return $pdf->output();

    }
?>