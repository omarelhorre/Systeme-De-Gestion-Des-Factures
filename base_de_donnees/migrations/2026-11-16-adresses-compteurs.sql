-- Run once against an existing Fatourti database.
-- Existing declarations are linked to one address per client.
CREATE TABLE adresseCompteur (
    id INT PRIMARY KEY AUTO_INCREMENT,
    idConsommateur INT NOT NULL,
    adresse VARCHAR(255) NOT NULL,
    categorie VARCHAR(50) NOT NULL,
    FOREIGN KEY (idConsommateur) REFERENCES consommateur(id)
);

INSERT INTO adresseCompteur (idConsommateur, adresse, categorie)
SELECT id, adresse, 'Résidentiel standard' FROM consommateur;

ALTER TABLE declaration ADD COLUMN idAdresseCompteur INT NULL AFTER idConsommateur;

UPDATE declaration d
JOIN adresseCompteur a ON a.idConsommateur = d.idConsommateur
SET d.idAdresseCompteur = a.id;

ALTER TABLE declaration
    MODIFY idAdresseCompteur INT NOT NULL,
    ADD CONSTRAINT fk_declaration_adresse_compteur
        FOREIGN KEY (idAdresseCompteur) REFERENCES adresseCompteur(id),
    ADD INDEX idx_declaration_consommateur (idConsommateur),
    DROP INDEX idConsommateur,
    ADD UNIQUE KEY unique_adresse_periode (idAdresseCompteur, dateDebut, dateFin);
