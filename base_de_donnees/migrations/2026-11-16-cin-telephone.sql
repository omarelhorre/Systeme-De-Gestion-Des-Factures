-- À exécuter une fois sur une base Fatourti existante.
-- Numéro d'identité (CIN) et téléphone du consommateur, saisis par le fournisseur comme l'adresse.
-- Les deux sont facultatifs pour ne pas bloquer les clients déjà enregistrés.
ALTER TABLE consommateur
    ADD COLUMN cin VARCHAR(20) NULL AFTER adresse,
    ADD COLUMN telephone VARCHAR(20) NULL AFTER cin;
