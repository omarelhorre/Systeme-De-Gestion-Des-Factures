<?php
require_once dirname(__DIR__).'/Traitement/consommateur/reglesConsommation.php';
require_once dirname(__DIR__).'/Traitement/consommateur/pdfFacture.php';
$sortie = sys_get_temp_dir().'/fatourti-pdf-test-'.bin2hex(random_bytes(6)).'.pdf';
$image = __DIR__.'/photo-test.jpg';
$client = array('id' => 1, 'prenom' => 'Test', 'nom' => 'Client', 'adresse' => 'Adresse test');
$precedente = array('dateSaisie' => '2026-10-20', 'indexEau' => 50, 'indexElectricite' => 1200);
$courante = array('dateSaisie' => '2026-11-20', 'indexEau' => 56, 'indexElectricite' => 1350);
try {
    creerPdfFacture($sortie, 42, $client, $precedente, $courante,
        calculerDeclaration(56, 1350, $precedente), $image, $image);
    if (substr(file_get_contents($sortie), 0, 8) !== '%PDF-1.4') throw new RuntimeException('Fichier PDF invalide.');
    echo "PDF avec photos : OK\n";
} finally {
    if (is_file($sortie)) unlink($sortie);
}
