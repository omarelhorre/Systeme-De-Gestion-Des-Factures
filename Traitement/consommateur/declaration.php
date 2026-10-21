<?php
function fatourtiDeclarationDb()
{
    return connexion();
}

function fatourtiDeclarationEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fatourtiDeclarationBasePath()
{
    $directory = dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php');
    return ($directory === '/' || $directory === '.') ? '' : rtrim($directory, '/');
}

function fatourtiMeterImageUpload($field, $consumerId, $meter, &$error)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        $error = 'Chaque photo doit faire moins de 5 Mo.';
        return false;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    );
    if (!isset($extensions[$mime])) {
        $error = 'Format de photo non accepté. Utilisez JPG, PNG ou WEBP.';
        return false;
    }

    $relativeDirectory = 'public/uploads/consommations';
    $absoluteDirectory = ROOT . '/' . $relativeDirectory;
    if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0755, true) && !is_dir($absoluteDirectory)) {
        $error = 'Impossible de créer le dossier des photos.';
        return false;
    }

    $filename = $consumerId . '-' . date('Ym') . '-' . $meter . '-' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    $absolutePath = $absoluteDirectory . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        $error = 'La photo n’a pas pu être enregistrée.';
        return false;
    }

    return $relativeDirectory . '/' . $filename;
}

function fatourtiMonthlyRate($quantity, $bands)
{
    foreach ($bands as $band) {
        if ($quantity <= $band[0]) {
            return $band[1];
        }
    }
    return $bands[count($bands) - 1][1];
}

function fatourtiPdfText($text)
{
    $text = str_replace(array("\r", "\n"), ' ', (string)$text);
    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }
    return str_replace(array('\\', '(', ')'), array('\\\\', '\\(', '\\)'), $text);
}

function fatourtiWriteInvoicePdf($path, $lines, $waterPhoto, $electricityPhoto)
{
    $content = "BT\n/F1 18 Tf\n50 790 Td\n(" . fatourtiPdfText('FATOURTI - FACTURE') . ") Tj\nET\n";
    $y = 750;
    foreach ($lines as $line) {
        $content .= "BT\n/F1 11 Tf\n50 " . $y . " Td\n(" . fatourtiPdfText($line) . ") Tj\nET\n";
        $y -= 25;
    }

    $photos = array();
    foreach (array($waterPhoto, $electricityPhoto) as $photoPath) {
        $imageInfo = @getimagesize($photoPath);
        if (!$imageInfo) {
            return false;
        }
        if ($imageInfo['mime'] === 'image/jpeg') {
            $image = @imagecreatefromjpeg($photoPath);
        } elseif ($imageInfo['mime'] === 'image/png' && function_exists('imagecreatefrompng')) {
            $image = @imagecreatefrompng($photoPath);
        } elseif ($imageInfo['mime'] === 'image/webp' && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($photoPath);
        } else {
            return false;
        }
        if (!$image) {
            return false;
        }
        ob_start();
        imagejpeg($image, null, 82);
        $jpeg = ob_get_clean();
        $photos[] = array('data' => $jpeg, 'width' => imagesx($image), 'height' => imagesy($image));
        imagedestroy($image);
    }

    $xPositions = array(50, 315);
    $imageNames = array('Im1', 'Im2');
    foreach ($photos as $index => $photo) {
        $scale = min(220 / $photo['width'], 135 / $photo['height']);
        $width = round($photo['width'] * $scale, 2);
        $height = round($photo['height'] * $scale, 2);
        $content .= "BT\n/F1 10 Tf\n" . $xPositions[$index] . " 245 Td\n(" . fatourtiPdfText($index === 0 ? 'Photo du compteur d eau' : 'Photo du compteur electrique') . ") Tj\nET\n";
        $content .= "q\n" . $width . " 0 0 " . $height . ' ' . $xPositions[$index] . " 95 cm\n/" . $imageNames[$index] . " Do\nQ\n";
    }

    $objects = array(
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> /XObject << /Im1 6 0 R /Im2 7 0 R >> >> /Contents 5 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream",
        '<< /Type /XObject /Subtype /Image /Width ' . $photos[0]['width'] . ' /Height ' . $photos[0]['height'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($photos[0]['data']) . ">>\nstream\n" . $photos[0]['data'] . "\nendstream",
        '<< /Type /XObject /Subtype /Image /Width ' . $photos[1]['width'] . ' /Height ' . $photos[1]['height'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ' . strlen($photos[1]['data']) . ">>\nstream\n" . $photos[1]['data'] . "\nendstream"
    );

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = array(0);
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }
    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF\n";

    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return false;
    }
    return file_put_contents($path, $pdf, LOCK_EX) !== false;
}

function fatourtiDeclarationPage()
{
    date_default_timezone_set('Africa/Casablanca');
    if (empty($_SESSION['user']['id'])) {
        header('Location: ' . link . 'utilisateur/authentification');
        exit;
    }

    $consumerId = (int)$_SESSION['user']['id'];
    $db = fatourtiDeclarationDb();
    $today = new DateTimeImmutable('today', new DateTimeZone('Africa/Casablanca'));
    $periodStart = $today->modify('first day of this month')->format('Y-m-d');
    $periodEnd = $today->modify('last day of this month')->format('Y-m-d');
    $monthLabel = $today->format('m/Y');
    $day = (int)$today->format('j');
    $error = '';
    $message = '';
    $invoiceId = null;

    $clientStatement = mysqli_prepare($db, 'SELECT c.idFournisseur, c.adresse, u.nom, u.prenom, u.email FROM consommateur c JOIN utilisateur u ON u.id = c.id WHERE c.id = ?');
    mysqli_stmt_bind_param($clientStatement, 'i', $consumerId);
    mysqli_stmt_execute($clientStatement);
    $client = mysqli_fetch_assoc(mysqli_stmt_get_result($clientStatement));
    mysqli_stmt_close($clientStatement);
    if (!$client) {
        http_response_code(403);
        echo 'Compte consommateur introuvable.';
        return;
    }

    $previousStatement = mysqli_prepare($db, 'SELECT indexEau, indexElectricite, cheminCompteurEauImg, cheminCompteurElectriciteImg FROM declaration WHERE idConsommateur = ? AND dateDebut < ? ORDER BY dateDebut DESC, id DESC LIMIT 1');
    mysqli_stmt_bind_param($previousStatement, 'is', $consumerId, $periodStart);
    mysqli_stmt_execute($previousStatement);
    $previous = mysqli_fetch_assoc(mysqli_stmt_get_result($previousStatement));
    mysqli_stmt_close($previousStatement);
    $previousWater = $previous ? (float)$previous['indexEau'] : 0.0;
    $previousElectricity = $previous ? (float)$previous['indexElectricite'] : 0.0;

    $currentStatement = mysqli_prepare($db, 'SELECT * FROM declaration WHERE idConsommateur = ? AND dateDebut = ? LIMIT 1');
    mysqli_stmt_bind_param($currentStatement, 'is', $consumerId, $periodStart);
    mysqli_stmt_execute($currentStatement);
    $current = mysqli_fetch_assoc(mysqli_stmt_get_result($currentStatement));
    mysqli_stmt_close($currentStatement);

    if ($current && $current['statut'] === 'Facture prête') {
        $invoiceStatement = mysqli_prepare($db, 'SELECT id FROM facture WHERE idDeclaration = ? LIMIT 1');
        mysqli_stmt_bind_param($invoiceStatement, 'i', $current['id']);
        mysqli_stmt_execute($invoiceStatement);
        $invoice = mysqli_fetch_assoc(mysqli_stmt_get_result($invoiceStatement));
        mysqli_stmt_close($invoiceStatement);
        $invoiceId = $invoice ? (int)$invoice['id'] : null;
        $message = 'Votre déclaration du mois a été validée.';
    } elseif ($current && $current['statut'] === 'Anomalie de saisie') {
        $message = 'Une déclaration existe déjà, mais un index était inférieur au relevé précédent. Corrigez-le ci-dessous.';
    }

    $canSubmit = $day >= 18 && (!$current || $current['statut'] === 'Anomalie de saisie');
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canSubmit) {
        if (empty($_SESSION['fatourti_declaration_token']) || !isset($_POST['token']) || !hash_equals($_SESSION['fatourti_declaration_token'], $_POST['token'])) {
            $error = 'La session du formulaire a expiré. Rechargez la page puis réessayez.';
        } else {
            $waterIndex = isset($_POST['indexEau']) ? filter_var($_POST['indexEau'], FILTER_VALIDATE_FLOAT) : false;
            $electricityIndex = isset($_POST['indexElectricite']) ? filter_var($_POST['indexElectricite'], FILTER_VALIDATE_FLOAT) : false;
            if ($waterIndex === false || $electricityIndex === false || $waterIndex < 0 || $electricityIndex < 0) {
                $error = 'Saisissez deux index numériques positifs ou nuls.';
            } else {
                $waterImage = fatourtiMeterImageUpload('photoEau', $consumerId, 'eau', $error);
                $electricityImage = $error === '' ? fatourtiMeterImageUpload('photoElectricite', $consumerId, 'electricite', $error) : false;
                if ($waterImage === false || $electricityImage === false) {
                    // The upload helper already supplied the explanation.
                } elseif ($current && !$waterImage && !$electricityImage) {
                    $waterImage = $current['cheminCompteurEauImg'];
                    $electricityImage = $current['cheminCompteurElectriciteImg'];
                } elseif ($current) {
                    $waterImage = $waterImage ?: $current['cheminCompteurEauImg'];
                    $electricityImage = $electricityImage ?: $current['cheminCompteurElectriciteImg'];
                } elseif (!$waterImage || !$electricityImage) {
                    $error = $error ?: 'Ajoutez une photo pour chacun des deux compteurs.';
                }

                if ($error === '') {
                    $waterConsumption = (float)$waterIndex - $previousWater;
                    $electricityConsumption = (float)$electricityIndex - $previousElectricity;
                    $anomaly = $waterConsumption < 0 || $electricityConsumption < 0;
                    $status = $anomaly ? 'Anomalie de saisie' : 'Facture prête';
                    $waterRate = fatourtiMonthlyRate(max(0, $waterConsumption), array(array(6, 2.37), array(20, 7.39), array(35, 10.98), array(PHP_FLOAT_MAX, 11.03)));
                    $electricityRate = fatourtiMonthlyRate(max(0, $electricityConsumption), array(array(100, 0.82), array(150, 0.92), array(PHP_FLOAT_MAX, 1.10)));
                    $totalHT = round(max(0, $waterConsumption) * $waterRate + max(0, $electricityConsumption) * $electricityRate, 2);
                    $totalTTC = round($totalHT * 1.18, 2);

                    mysqli_begin_transaction($db);
                    if ($current) {
                        $saveStatement = mysqli_prepare($db, 'UPDATE declaration SET idFournisseur = ?, cheminCompteurEauImg = ?, cheminCompteurElectriciteImg = ?, indexEau = ?, indexElectricite = ?, dateSaisie = CURRENT_DATE(), statut = ?, dateDebut = ?, dateFin = ?, prixHT = ?, prixTTC = ? WHERE id = ? AND idConsommateur = ?');
                        mysqli_stmt_bind_param($saveStatement, 'issddsssddii', $client['idFournisseur'], $waterImage, $electricityImage, $waterIndex, $electricityIndex, $status, $periodStart, $periodEnd, $totalHT, $totalTTC, $current['id'], $consumerId);
                    } else {
                        $saveStatement = mysqli_prepare($db, 'INSERT INTO declaration (idFournisseur, idConsommateur, cheminCompteurEauImg, cheminCompteurElectriciteImg, indexEau, indexElectricite, statut, dateDebut, dateFin, prixHT, prixTTC) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        mysqli_stmt_bind_param($saveStatement, 'iissddsssdd', $client['idFournisseur'], $consumerId, $waterImage, $electricityImage, $waterIndex, $electricityIndex, $status, $periodStart, $periodEnd, $totalHT, $totalTTC);
                    }

                    $saved = mysqli_stmt_execute($saveStatement);
                    $declarationId = $current ? (int)$current['id'] : (int)mysqli_insert_id($db);
                    mysqli_stmt_close($saveStatement);
                    if (!$saved) {
                        mysqli_rollback($db);
                        $error = 'La déclaration n’a pas pu être enregistrée dans la base de données.';
                    } elseif ($anomaly) {
                        mysqli_commit($db);
                        $current = array('id' => $declarationId, 'indexEau' => $waterIndex, 'indexElectricite' => $electricityIndex, 'cheminCompteurEauImg' => $waterImage, 'cheminCompteurElectriciteImg' => $electricityImage, 'statut' => $status);
                        $canSubmit = true;
                        $message = 'Anomalie : un index est inférieur à celui de la dernière déclaration. Corrigez la saisie puis renvoyez le formulaire pour obtenir la facture.';
                    } else {
                        $relativePdf = 'public/factures/facture-' . $declarationId . '.pdf';
                        $absolutePdf = ROOT . '/' . $relativePdf;
                        $invoiceLines = array(
                            'Numero : F-' . $declarationId . '-' . $today->format('Ym'),
                            'Date : ' . $today->format('d/m/Y') . '    Periode : ' . $monthLabel,
                            'Client : ' . $client['prenom'] . ' ' . $client['nom'],
                            'Adresse : ' . $client['adresse'],
                            'Index eau : ' . number_format((float)$waterIndex, 2, ',', ' ') . ' m3 (precedent : ' . number_format($previousWater, 2, ',', ' ') . ')',
                            'Consommation eau : ' . number_format($waterConsumption, 2, ',', ' ') . ' m3 x ' . number_format($waterRate, 2, ',', ' ') . ' DH',
                            'Index electricite : ' . number_format((float)$electricityIndex, 2, ',', ' ') . ' kWh (precedent : ' . number_format($previousElectricity, 2, ',', ' ') . ')',
                            'Consommation electricite : ' . number_format($electricityConsumption, 2, ',', ' ') . ' kWh x ' . number_format($electricityRate, 2, ',', ' ') . ' DH',
                            'Total HT : ' . number_format($totalHT, 2, ',', ' ') . ' DH',
                            'TVA (18%) : ' . number_format($totalTTC - $totalHT, 2, ',', ' ') . ' DH',
                            'Total TTC : ' . number_format($totalTTC, 2, ',', ' ') . ' DH'
                        );
                        if (!fatourtiWriteInvoicePdf($absolutePdf, $invoiceLines, ROOT . '/' . $waterImage, ROOT . '/' . $electricityImage)) {
                            mysqli_rollback($db);
                            $error = 'La déclaration est prête, mais la facture PDF ne peut pas être créée. Vérifiez les droits d’écriture du projet.';
                        } else {
                            $invoiceStatement = mysqli_prepare($db, 'INSERT INTO facture (idDeclaration, cheminGenere) VALUES (?, ?)');
                            mysqli_stmt_bind_param($invoiceStatement, 'is', $declarationId, $relativePdf);
                            $invoiceSaved = mysqli_stmt_execute($invoiceStatement);
                            $invoiceId = $invoiceSaved ? (int)mysqli_insert_id($db) : null;
                            mysqli_stmt_close($invoiceStatement);
                            if (!$invoiceSaved) {
                                @unlink($absolutePdf);
                                mysqli_rollback($db);
                                $error = 'La facture n’a pas pu être enregistrée.';
                            } else {
                                mysqli_commit($db);
                                $current = array('id' => $declarationId, 'indexEau' => $waterIndex, 'indexElectricite' => $electricityIndex, 'cheminCompteurEauImg' => $waterImage, 'cheminCompteurElectriciteImg' => $electricityImage, 'statut' => $status);
                                $canSubmit = false;
                                $message = 'Déclaration enregistrée. Votre facture PDF est prête à télécharger.';
                            }
                        }
                    }
                }
            }
        }
    }

    if (empty($_SESSION['fatourti_declaration_token'])) {
        $_SESSION['fatourti_declaration_token'] = bin2hex(random_bytes(32));
    }
    if (!$invoiceId && $current && $current['statut'] === 'Facture prête') {
        $invoiceStatement = mysqli_prepare($db, 'SELECT id FROM facture WHERE idDeclaration = ? LIMIT 1');
        mysqli_stmt_bind_param($invoiceStatement, 'i', $current['id']);
        mysqli_stmt_execute($invoiceStatement);
        $invoice = mysqli_fetch_assoc(mysqli_stmt_get_result($invoiceStatement));
        mysqli_stmt_close($invoiceStatement);
        $invoiceId = $invoice ? (int)$invoice['id'] : null;
    }

    $basePath = fatourtiDeclarationBasePath();
    $displayName = trim($client['prenom'] . ' ' . $client['nom']);
    $existingWater = $current ? $current['indexEau'] : '';
    $existingElectricity = $current ? $current['indexElectricite'] : '';
    include ROOT . '/IHM/consommateur/declaration.php';
}

function fatourtiDownloadInvoice($invoiceId)
{
    if (empty($_SESSION['user']['id'])) {
        http_response_code(403);
        exit('Accès refusé.');
    }
    $db = fatourtiDeclarationDb();
    $consumerId = (int)$_SESSION['user']['id'];
    $statement = mysqli_prepare($db, 'SELECT f.cheminGenere FROM facture f JOIN declaration d ON d.id = f.idDeclaration WHERE f.id = ? AND d.idConsommateur = ?');
    mysqli_stmt_bind_param($statement, 'ii', $invoiceId, $consumerId);
    mysqli_stmt_execute($statement);
    $invoice = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));
    mysqli_stmt_close($statement);
    if (!$invoice) {
        http_response_code(404);
        exit('Facture introuvable.');
    }

    $filename = basename($invoice['cheminGenere']);
    $path = ROOT . '/public/factures/' . $filename;
    if (!is_file($path)) {
        http_response_code(404);
        exit('Le fichier PDF est introuvable.');
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}
