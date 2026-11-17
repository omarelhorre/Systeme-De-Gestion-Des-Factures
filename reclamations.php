<?php
// Ancienne adresse des réclamations du consommateur.
// La page passe maintenant par le routeur (index.php) : reclamation/all.
// Ce fichier redirige seulement, pour que les anciens liens (favoris, emails, notifications) fonctionnent encore.
header('Location: http://localhost:8080/reclamation/all', true, 301);
exit;
