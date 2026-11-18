<?php
class PdfFacture
{
    private $lignes = array();
    private $images = array();

    public function texte($x, $y, $taille, $texte)
    {
        $latin = iconv('UTF-8', 'Windows-1252//TRANSLIT', $texte);
        if ($latin === false) $latin = $texte;
        $latin = str_replace(array('\\', '(', ')', "\r", "\n"), array('\\\\', '\\(', '\\)', ' ', ' '), $latin);
        $this->lignes[] = sprintf('BT /F1 %.2F Tf %.2F %.2F Td (%s) Tj ET', $taille, $x, 842 - $y, $latin);
    }

    public function image($nom, $chemin, $x, $y, $largeur, $hauteur)
    {
        $info = getimagesize($chemin);
        if (!$info || $info[2] !== IMAGETYPE_JPEG || $info['channels'] !== 3) {
            throw new RuntimeException('Photo JPEG couleur requise pour la facture.');
        }
        $this->images[$nom] = array('bytes' => file_get_contents($chemin), 'width' => $info[0], 'height' => $info[1]);
        $this->lignes[] = sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q', $largeur, $hauteur, $x, 842 - $y - $hauteur, $nom);
    }

    public function enregistrer($chemin)
    {
        $objets = array();
        $ajouter = function ($contenu) use (&$objets) { $objets[] = $contenu; return count($objets); };
        $catalogue = $ajouter('<< /Type /Catalog /Pages 2 0 R >>');
        $pages = $ajouter('');
        $page = $ajouter('');
        $texte = implode("\n", $this->lignes)."\n";
        $contenu = $ajouter('<< /Length '.strlen($texte).' >>' . "\nstream\n" . $texte . "endstream");
        $police = $ajouter('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
        $references = array();
        foreach ($this->images as $nom => $image) {
            $bytes = $image['bytes'];
            $numero = $ajouter('<< /Type /XObject /Subtype /Image /Width '.$image['width'].' /Height '.$image['height'].' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($bytes).' >>' . "\nstream\n" . $bytes . "\nendstream");
            $references[] = '/'.$nom.' '.$numero.' 0 R';
        }
        $objets[$pages - 1] = '<< /Type /Pages /Kids ['.$page.' 0 R] /Count 1 >>';
        $objets[$page - 1] = '<< /Type /Page /Parent '.$pages.' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 '.$police.' 0 R >> /XObject << '.implode(' ', $references).' >> >> /Contents '.$contenu.' 0 R >>';
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $positions = array(0);
        foreach ($objets as $index => $objet) {
            $positions[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$objet."\nendobj\n";
        }
        $debutXref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objets) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($positions, 1) as $position) $pdf .= sprintf('%010d 00000 n ', $position)."\n";
        $pdf .= 'trailer << /Size '.(count($objets) + 1).' /Root '.$catalogue." 0 R >>\nstartxref\n".$debutXref."\n%%EOF\n";
        if (file_put_contents($chemin, $pdf, LOCK_EX) !== strlen($pdf)) throw new RuntimeException('Écriture de la facture impossible.');
    }
}

function creerPdfFacture($chemin, $numero, $client, $precedente, $courante, $calcul, $photoEau, $photoElectricite, $dateFacture = null)
{
    $montant = function ($valeur) { return number_format((float)$valeur, 2, ',', ' ').' MAD'; };
    $quantite = function ($valeur) { return rtrim(rtrim(number_format((float)$valeur, 2, ',', ' '), '0'), ','); };
    $jour = function ($date) { return date('d/m/Y', strtotime($date)); };

    $pdf = new PdfFacture();
    $pdf->texte(40, 48, 23, 'FATOURTI');
    $pdf->texte(40, 77, 13, 'Facture n° '.$numero);
    $pdf->texte(40, 95, 10, 'Date de la facture : '.$jour($dateFacture ?: date('Y-m-d')));
    $pdf->texte(40, 125, 11, 'Client : '.$client['prenom'].' '.$client['nom'].' (n° '.$client['id'].')');
    $pdf->texte(40, 143, 10, 'Adresse : '.$client['adresse']);
    $pdf->texte(40, 161, 10, 'Période : du '.$jour($precedente['dateSaisie']).' au '.$jour($courante['dateSaisie']));

    $services = array(
        array(40, 'ÉLECTRICITÉ', 'kWh', 'indexElectricite', $calcul['electricite'], $calcul['detailElectricite'] ?? array(), $calcul['htElectricite'] ?? null),
        array(305, 'EAU', 'm³', 'indexEau', $calcul['eau'], $calcul['detailEau'] ?? array(), $calcul['htEau'] ?? null),
    );
    foreach ($services as $service) {
        list($x, $titre, $unite, $index, $consommation, $detail, $sousTotal) = $service;
        $pdf->texte($x, 200, 11, $titre);
        $pdf->texte($x, 220, 10, 'Index précédent : '.$quantite($precedente[$index]).' '.$unite);
        $pdf->texte($x, 236, 10, 'Index courant : '.$quantite($courante[$index]).' '.$unite);
        $pdf->texte($x, 252, 10, 'Consommation : '.$quantite($consommation).' '.$unite);
        $y = 272;
        foreach ($detail as $ligne) {
            $tranche = $ligne['a'] === null ? 'au-delà de '.$ligne['de'] : $ligne['de'].' à '.$ligne['a'];
            $pdf->texte($x, $y, 9, $tranche.' '.$unite.' : '.$quantite($ligne['quantite']).' × '.number_format($ligne['prix'], 2, ',', ' ').' = '.$montant($ligne['montant']));
            $y += 15;
        }
        if ($sousTotal !== null) $pdf->texte($x, 340, 10, 'Sous-total HT : '.$montant($sousTotal));
    }

    $pdf->texte(40, 385, 11, 'Total HT : '.$montant($calcul['ht']));
    $pdf->texte(40, 405, 11, 'TVA (18 %) : '.$montant(round($calcul['ht'] * TVA_CONSOMMATION, 2)));
    $pdf->texte(40, 428, 13, 'Total TTC : '.$montant($calcul['ttc']));
    $pdf->texte(40, 470, 10, 'Photos des compteurs');
    $pdf->texte(40, 490, 9, 'Eau');
    $pdf->texte(305, 490, 9, 'Électricité');
    $pdf->image('Eau', $photoEau, 40, 502, 240, 190);
    $pdf->image('Electricite', $photoElectricite, 305, 502, 240, 190);
    $pdf->enregistrer($chemin);
}
