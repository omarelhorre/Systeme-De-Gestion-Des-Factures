-- À exécuter une fois sur une base Fatourti existante.
-- Consigne du mini-projet : ajouter la table quartier ; un client peut avoir plusieurs contrats
-- dans des quartiers différents ; un quartier est associé à un type d'activité.
--  - chaque adresse de compteur (donc chaque contrat) appartient à un quartier ;
--  - le type d'activité vient du quartier (adresseCompteur.categorie en garde une copie pour les écrans existants).
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS quartier (
    id int PRIMARY KEY AUTO_INCREMENT,
    nom varchar(100) NOT NULL UNIQUE,
    typeActivite varchar(50) NOT NULL       -- Résidentiel économique / standard / haut standing, Commercial, Industriel
);

INSERT IGNORE INTO quartier (nom, typeActivite) VALUES
    ('Médina', 'Commercial'),
    ('Centre-ville', 'Commercial'),
    ('Hay El Farah', 'Résidentiel économique'),
    ('Touabel', 'Résidentiel économique'),
    ('Mhannech', 'Résidentiel standard'),
    ('Sania Ramel', 'Résidentiel standard'),
    ('Martil', 'Résidentiel standard'),
    ('Cabo Negro', 'Résidentiel haut standing'),
    ('Tetouan Park', 'Industriel');

ALTER TABLE adresseCompteur
    ADD COLUMN idQuartier int NULL,
    ADD CONSTRAINT fk_adresse_quartier FOREIGN KEY (idQuartier) REFERENCES quartier(id);

-- Rattacher les adresses existantes à leur quartier, d'après le texte de l'adresse
UPDATE adresseCompteur a JOIN quartier q ON LOWER(a.adresse) LIKE CONCAT('%', LOWER(REPLACE(q.nom, 'Hay ', '')), '%')
SET a.idQuartier = q.id, a.categorie = q.typeActivite
WHERE a.idQuartier IS NULL;
-- Les autres : Centre-ville par défaut (le fournisseur peut corriger)
UPDATE adresseCompteur SET idQuartier = (SELECT id FROM quartier WHERE nom = 'Centre-ville') WHERE idQuartier IS NULL;
