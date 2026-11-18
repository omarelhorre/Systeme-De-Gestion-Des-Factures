-- À exécuter une fois sur une base Fatourti existante.
-- Notifications persistantes : une ligne par message adressé à un utilisateur.
CREATE TABLE notification
(
    id int primary key AUTO_INCREMENT,
    idUtilisateur int NOT NULL,              -- le destinataire
    type varchar(40) NOT NULL,               -- bienvenue, facture, anomalie, reclamation_nouvelle...
    titre varchar(150) NOT NULL,
    detail varchar(255),
    lien varchar(255),                       -- page à ouvrir, relative au site (ex. facture/detail/12)
    aTraiter tinyint(1) DEFAULT 0 NOT NULL,  -- 1 = demande une action de l'utilisateur
    idObjet int,                             -- la déclaration, facture ou réclamation concernée
    dateCreation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    dateLecture DATETIME,                    -- NULL = pas encore vue
    dateResolution DATETIME,                 -- NULL = pas encore traitée (si aTraiter = 1)
    FOREIGN KEY (idUtilisateur) REFERENCES utilisateur(id),
    INDEX idx_notification_utilisateur (idUtilisateur, id)
);
