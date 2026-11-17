-- À exécuter une fois sur une base Fatourti existante.
-- Seuils d'anomalie définis par chaque fournisseur (cahier des charges : "dépasse un seuil défini par le fournisseur").
-- Une consommation mensuelle au-delà de ces seuils crée une anomalie au lieu d'une facture.
ALTER TABLE fournisseur
    ADD COLUMN seuilEau DECIMAL(10,2) NOT NULL DEFAULT 100.00,
    ADD COLUMN seuilElectricite DECIMAL(10,2) NOT NULL DEFAULT 1000.00;
