<?php
    // Page "Mon compte" : chaque utilisateur peut changer son email ou son mot de passe.
    // Le nom, le prénom, le numéro d'identité et le téléphone sont affichés mais non modifiables ici.
    include_once(ROOT."/BD/utilisateur.php");
    include_once(ROOT."/BD/fournisseur.php");
    include_once(ROOT."/BD/consommateur.php");
    include_once(ROOT."/BD/agent.php");

    // $action : 'profil' (affichage), 'modifierEmail' ou 'modifierMotDePasse' (formulaires en POST)
    function profil($action)
    {
        if (!isset($_SESSION['user'])) {
            header("Location:".link."utilisateur/authentification");
            exit;
        }
        $id = (int)$_SESSION['user']['id'];
        $utilisateur = selectUtilisateur($id);
        if (empty($_SESSION['csrf_profil'])) {
            $_SESSION['csrf_profil'] = bin2hex(random_bytes(32));
        }
        $erreur = '';

        if ($_SERVER["REQUEST_METHOD"] == "POST" && $action !== 'profil')
            {
                $motDePasseActuel = (string)($_POST['motDePasseActuel'] ?? '');
                if (!hash_equals($_SESSION['csrf_profil'], (string)($_POST['csrf'] ?? ''))) {
                    $erreur = 'Session expirée : rechargez la page.';
                }
                // Toute modification demande le mot de passe actuel
                else if (!password_verify($motDePasseActuel, $utilisateur['pass'])) {
                    $erreur = 'Mot de passe actuel incorrect.';
                }
                else if ($action == 'modifierEmail')
                    {
                        $email = trim((string)($_POST['email'] ?? ''));
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
                            $erreur = 'Adresse email invalide.';
                        } else if (emailDejaUtilise($email, $id)) {
                            $erreur = 'Cette adresse email est déjà utilisée.';
                        } else {
                            modifierEmailUtilisateur($id, $email);
                            $_SESSION['user']['email'] = $email;
                            $_SESSION['message_profil'] = 'Votre email a été modifié.';
                        }
                    }
                else if ($action == 'modifierMotDePasse')
                    {
                        $nouveau = (string)($_POST['nouveauMotDePasse'] ?? '');
                        $confirmation = (string)($_POST['confirmation'] ?? '');
                        if (strlen($nouveau) < 8 || strlen($nouveau) > 72) {
                            $erreur = 'Le nouveau mot de passe doit contenir entre 8 et 72 caractères.';
                        } else if ($nouveau !== $confirmation) {
                            $erreur = 'Les deux nouveaux mots de passe ne sont pas identiques.';
                        } else {
                            $hache = password_hash($nouveau, PASSWORD_DEFAULT);
                            modifierMotDePasseUtilisateur($id, $hache);
                            $_SESSION['user']['pass'] = $hache;
                            $_SESSION['message_profil'] = 'Votre mot de passe a été modifié.';
                        }
                    }
                // Réussite : nouveau jeton, puis retour sur la page (évite de renvoyer le formulaire)
                if ($erreur === '') {
                    $_SESSION['csrf_profil'] = bin2hex(random_bytes(32));
                    header("Location:".link."utilisateur/profil");
                    exit;
                }
            }

        $message = $_SESSION['message_profil'] ?? '';
        unset($_SESSION['message_profil']);

        // Rôle de l'utilisateur : il décide du menu affiché et des champs en plus (consommateur)
        $consommateur = checkConsommateur($id);
        if ($consommateur) $role = 'consommateur';
        else if (checkAgent($id)) $role = 'agent';
        else $role = 'fournisseur';

        include_once(ROOT."/IHM/profil.php");
    }
?>
