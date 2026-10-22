<?php
// Petite bibliothèque PDF locale, sans dépendance ajoutée à Composer.
// Entrées photographiques JPEG contrôlées avant l'appel.
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

function creerPdfFacture($chemin, $numero, $client, $precedente, $courante, $calcul, $photoEau, $photoElectricite)
{
    $pdf = new PdfFacture();
    $pdf->texte(40, 48, 23, 'FATOURTI');
    $pdf->texte(40, 77, 13, 'Facture n° '.$numero);
    $pdf->texte(40, 99, 10, 'Tarifs de démonstration - à valider avant utilisation réelle');
    $pdf->texte(40, 130, 11, 'Client : '.$client['prenom'].' '.$client['nom'].' (n° '.$client['id'].')');
    $pdf->texte(40, 148, 10, 'Adresse : '.$client['adresse']);
    $pdf->texte(40, 166, 10, 'Période : '.$precedente['dateSaisie'].' au '.$courante['dateSaisie']);
    $pdf->texte(40, 204, 11, 'ÉLECTRICITÉ');
    $pdf->texte(40, 226, 10, 'Index précédent : '.$precedente['indexElectricite'].' kWh');
    $pdf->texte(40, 244, 10, 'Index courant : '.$courante['indexElectricite'].' kWh');
    $pdf->texte(40, 262, 10, 'Consommation : '.$calcul['electricite'].' kWh × '.TARIF_ELECTRICITE_DEMO.' MAD/kWh');
    $pdf->texte(40, 300, 11, 'EAU');
    $pdf->texte(40, 322, 10, 'Index précédent : '.$precedente['indexEau'].' m³');
    $pdf->texte(40, 340, 10, 'Index courant : '.$courante['indexEau'].' m³');
    $pdf->texte(40, 358, 10, 'Consommation : '.$calcul['eau'].' m³ × '.TARIF_EAU_DEMO.' MAD/m³');
    $pdf->texte(40, 401, 11, 'Total HT : '.number_format($calcul['ht'], 2, ',', ' ').' MAD');
    $pdf->texte(40, 421, 11, 'TVA (18 %) : '.number_format(round($calcul['ht'] * TVA_CONSOMMATION, 2), 2, ',', ' ').' MAD');
    $pdf->texte(40, 444, 13, 'Total TTC : '.number_format($calcul['ttc'], 2, ',', ' ').' MAD');
    $pdf->texte(40, 488, 10, 'Photos des compteurs');
    $pdf->texte(40, 508, 9, 'Eau');
    $pdf->texte(305, 508, 9, 'Électricité');
    $pdf->image('Eau', $photoEau, 40, 520, 240, 190);
    $pdf->image('Electricite', $photoElectricite, 305, 520, 240, 190);
    $pdf->enregistrer($chemin);
}
