# Intégration de la nouvelle version consommation

Cette version remplace l'ancien formulaire de la branche `uthmanefr/consumer-declarations`. Le tableau de bord consommateur mène à `consommation.php`, qui réutilise la session d'authentification existante. Le routeur principal ne contient plus les anciennes routes du module remplacé.

Le module est réparti entre `BD/declarationConsommation.php` (SQL), `Traitement/consommateur/` (règles, contrôleur et PDF), `IHM/consommateur/declarationConsommation.php` (interface), `public/style/consommation.css` et `stockage_consommation/.htaccess` (stockage protégé). Le dossier de stockage doit être inscriptible par Apache ou PHP.

Il utilise les tables existantes `utilisateur`, `consommateur`, `declaration` et `facture`. Il ne crée aucun compte et ne modifie pas le schéma. La première lecture sert de référence sans facture. Dès la deuxième lecture valide, il calcule la consommation, enregistre la facture et fournit son PDF. Les anomalies restent visibles sans facture. Le statut de paiement est relu dans `facture`, pour rester compatible avec le module d'Ilyas.

Les déclarations sont acceptées du 18 au dernier jour du mois, heure `Africa/Casablanca`, à raison d'une déclaration par consommateur et par mois. Les tarifs et seuils dans `Traitement/consommateur/reglesConsommation.php` sont des valeurs de démonstration à valider avant une utilisation réelle.

Configuration : `BD/connexion.php` garde la signature du projet (`connexion($serv, $user, $bd)`). Le module accepte `FATOURTI_DB_HOST`, `FATOURTI_DB_USER` et `FATOURTI_DB_NAME`; par défaut il utilise `db`, `root` et `fatourti`. Le mot de passe est lu selon `BD/connexion.php`. Pour un lancement local hors Docker, configurer l'hôte MySQL et les URL de l'application dans l'environnement local ; ces réglages ne font pas partie de ce commit.

Vérification : `php tests/reglesConsommation.php` et `php tests/pdfFacture.php`. La nouvelle version a aussi été testée via Apache et MySQL sur la base isolée `fatourti_codex_test` : authentification, première lecture, deuxième lecture et PDF, anomalies, limites mensuelles, accès aux fichiers et statut de paiement. Ces données de test ne sont pas incluses dans Git.
