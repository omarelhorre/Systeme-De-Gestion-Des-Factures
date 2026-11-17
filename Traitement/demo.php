<?php
// Mode démo : changer la date de toute l'application pendant une présentation,
// sans toucher à l'horloge du PC.
//
// Comment ça marche :
//  - libfaketime (installé dans le Dockerfile) lit un décalage en secondes dans le fichier
//    DEMO_FICHIER ("+2592000" = 30 jours plus tard) et l'applique à toutes les fonctions de date de PHP ;
//  - BD/connexion.php donne la même date à MySQL (NOW(), CURDATE()) ;
//  - pas de fichier = la vraie date.
// Actif seulement si DEMO=1 (docker-compose.yml, demo=1 dans .env). Sinon la route répond 404.

const DEMO_FICHIER = '/tmp/fatourti-demo-date';

function demoActive(): bool
{
    return getenv('DEMO') === '1';
}

// Décalage actuel en secondes (0 = vraie date)
function demoDecalage(): int
{
    if (!is_file(DEMO_FICHIER)) return 0;
    return (int)trim((string)file_get_contents(DEMO_FICHIER));
}

// Les raccourcis de la barre : [libellé, date visée à partir de la date affichée]
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

// demo/aller/{raccourci}, demo/aller/date (champ "date" du formulaire), demo/aller/reel
function demoAller($cible)
{
    if (!demoActive() || $_SERVER['REQUEST_METHOD'] !== 'POST') introuvable();

    $zone = new DateTimeZone('Africa/Casablanca');
    $maintenant = new DateTimeImmutable('now', $zone);          // la date affichée (déjà décalée)
    $vraiMaintenant = time() - demoDecalage();                  // la vraie date
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

    // retour sur la page où l'on était (même site uniquement)
    $retour = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH);
    $retour = is_string($retour) && preg_match('#^/[A-Za-z0-9\-_/]*$#', $retour) ? ltrim($retour, '/') : '';
    header('Location: '.link.$retour, true, 303);
    exit;
}
