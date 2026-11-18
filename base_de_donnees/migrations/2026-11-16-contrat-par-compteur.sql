-- À exécuter une fois sur une base Fatourti existante.
-- Un contrat est maintenant lié à une adresse de compteur : un client peut avoir plusieurs contrats.
ALTER TABLE contrat
    ADD COLUMN idAdresseCompteur INT NULL AFTER idConsommateur,
    ADD CONSTRAINT fk_contrat_adresse_compteur
        FOREIGN KEY (idAdresseCompteur) REFERENCES adresseCompteur(id);

-- Les contrats déjà enregistrés sont rattachés à la première adresse de leur client.
UPDATE contrat c
SET c.idAdresseCompteur = (SELECT MIN(a.id) FROM adresseCompteur a WHERE a.idConsommateur = c.idConsommateur)
WHERE c.idAdresseCompteur IS NULL;
