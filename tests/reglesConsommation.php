<?php
require_once dirname(__DIR__).'/Traitement/consommateur/reglesConsommation.php';
function verifier($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$zone = new DateTimeZone('Africa/Casablanca');
verifier(!declarationOuverte(new DateTimeImmutable('2026-10-17', $zone)), 'Avant le 18');
verifier(declarationOuverte(new DateTimeImmutable('2026-10-18', $zone)), 'Le 18');
verifier(declarationOuverte(new DateTimeImmutable('2026-10-31', $zone)), 'Dernier jour');
$premier = calculerDeclaration(50, 1200, null);
verifier($premier['statut'] === 'Référence initiale' && $premier['ht'] === null, 'Premier relevé');
$precedent = array('indexEau' => '50.0000', 'indexElectricite' => '1200.0000');
$normal = calculerDeclaration(56, 1350, $precedent);
verifier($normal['statut'] === 'Validée' && $normal['eau'] === 6.0 && $normal['electricite'] === 150.0, 'Consommation');
verifier($normal['ht'] === 216.0 && $normal['ttc'] === 254.88, 'Facture');
verifier(calculerDeclaration(49, 1350, $precedent)['statut'] === 'Anomalie', 'Baisse eau');
verifier(calculerDeclaration(56, 1199, $precedent)['statut'] === 'Anomalie', 'Baisse électricité');
verifier(calculerDeclaration(151, 1350, $precedent)['statut'] === 'Anomalie', 'Seuil eau');
verifier(calculerDeclaration(56, 2201, $precedent)['statut'] === 'Anomalie', 'Seuil électricité');
echo "Règles de consommation : OK\n";
