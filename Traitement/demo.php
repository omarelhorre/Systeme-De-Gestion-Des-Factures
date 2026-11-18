<?php

const DEMO_FICHIER = '/tmp/fatourti-demo-date';

function demoActive(): bool
{
    return getenv('DEMO') === '1';
}

function demoDecalage(): int
{
    if (!is_file(DEMO_FICHIER)) return 0;
    return (int)trim((string)file_get_contents(DEMO_FICHIER));
}

function demoRaccourcis(): array
{
    return array(
        'dix-huit'       => array('18 du mois',      function ($d) { return $d->setDate((int)$d->format('Y'), (int)$d->format('n'), 18)->setTime(9, 0); }),
        'fin-mois'       => array('Fin du mois',     function ($d) { return $d->modify('last day of this month')->setTime(18, 0); }),
        'mois-suivant'   => array('Mois suivant',    function ($d) { return $d->modify('first day of next month')->setTime(9, 0); }),
        'plus-30'        => array('+30 jours',       function ($d) { return $d->modify('+30 days'); }),
        'fin-annee'      => array('Fin d\'année',    function ($d) { return $d->setDate((int)$d->format('Y'), 12, 31)->setTime(18, 0); }),
        'annee-suivante' => array('Année suivante',  function ($d) { return $d->setDate((int)$d->format('Y') + 1, 1, 1)->setTime(9, 0); }),
    );
}

function demoAller($cible)
{
    if (!demoActive() || $_SERVER['REQUEST_METHOD'] !== 'POST') introuvable();

    $zone = new DateTimeZone('Africa/Casablanca');
    $maintenant = new DateTimeImmutable('now', $zone);
    $vraiMaintenant = time() - demoDecalage();
    $raccourcis = demoRaccourcis();

    if ($cible === 'reel') {
        @unlink(DEMO_FICHIER);
    } else {
        if (isset($raccourcis[$cible])) {
            $vise = $raccourcis[$cible][1]($maintenant);
        } else if ($cible === 'date' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date'] ?? '')) {
            $vise = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $_POST['date'].' 09:00', $zone);
        } else {
            introuvable();
        }
        $decalage = $vise->getTimestamp() - $vraiMaintenant;
        file_put_contents(DEMO_FICHIER, ($decalage >= 0 ? '+' : '').$decalage);
    }

    $retour = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH);
    $retour = is_string($retour) && preg_match('#^/[A-Za-z0-9\-_/]*$#', $retour) ? ltrim($retour, '/') : '';
    header('Location: '.link.$retour, true, 303);
    exit;
}
