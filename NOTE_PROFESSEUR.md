# Fatourti — note pour le test

## Lancer l'application

1. Créer un fichier `.env` à la racine du projet :

   ```
   mysql_root_password=root
   mysql_database=fatourti
   mysql_user=fatourti
   mysql_password=fatourti
   ```

2. Lancer :

   ```
   docker compose up -d
   ```

3. Ouvrir http://localhost:8080

La base est créée au premier lancement à partir de `database.sql`.

## Compte de test

| Rôle | Email | Mot de passe |
|---|---|---|
| Fournisseur | admin@fatourti.local | 12345678 |

Depuis ce compte, *Clients → Ajouter un client* crée un client ou un agent. Leur mot de passe est envoyé par e-mail : ajouter `smtp=…` dans `.env` pour l'envoi.
