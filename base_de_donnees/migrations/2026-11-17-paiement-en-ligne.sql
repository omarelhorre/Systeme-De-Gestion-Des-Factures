-- À exécuter une fois sur une base Fatourti existante.
-- Paiement en ligne (CMI) : comment la facture a été payée et la référence de la transaction.
-- modePaiement NULL = payée en espèces chez le fournisseur (comme avant).
ALTER TABLE facture
    ADD COLUMN modePaiement VARCHAR(20) NULL,
    ADD COLUMN referencePaiement VARCHAR(64) NULL;
