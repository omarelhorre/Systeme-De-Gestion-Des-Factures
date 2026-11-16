CREATE TABLE utilisateur
(
    id int primary key AUTO_INCREMENT,
    nom varchar(50) NOT NULL,
    prenom varchar(50) NOT NULL,
    email varchar(100) UNIQUE NOT NULL,
    pass varchar(255) NOT NULL
);

CREATE TABLE agent
(
    id int primary key,
    FOREIGN KEY (id) REFERENCES utilisateur(id)
);

CREATE TABLE fournisseur
(
    id int primary key,
    FOREIGN KEY (id) REFERENCES utilisateur(id)
);
CREATE TABLE consommateur 
(
    id int primary key,
    idFournisseur int NOT NULL,
    adresse varchar(255) NOT NULL,
    cin varchar(20),
    telephone varchar(20),
    FOREIGN KEY (idFournisseur) REFERENCES fournisseur(id),
    FOREIGN KEY (id) REFERENCES utilisateur(id)
);

CREATE TABLE adresseCompteur
(
    id int primary key AUTO_INCREMENT,
    idConsommateur int NOT NULL,
    adresse varchar(255) NOT NULL,
    categorie varchar(50) NOT NULL,
    FOREIGN KEY (idConsommateur) REFERENCES consommateur(id)
);

CREATE TABLE contrat
(
    id int primary key AUTO_INCREMENT,
    dateDebut DATE DEFAULT (CURRENT_DATE()) NOT NULL,
    cheminGeneration varchar(255) NOT NULL,
    idConsommateur int NOT NULL,
    statut varchar(20) DEFAULT 'actif' NOT NULL,
    FOREIGN KEY (idConsommateur) REFERENCES consommateur(id)
);

CREATE TABLE fichierAnuelle
(
    fichierChemin varchar(255) primary key NOT NULL
);

CREATE TABLE ConsommationAnuelle
(
    id int primary key AUTO_INCREMENT,
    idConsommateur int NOT NULL,
    fichierChemin varchar(255) NOT NULL,
    agentDepo int NOT NULL,
    annee int NOT NULL,
    ConsommationEau decimal(15,4) NOT NULL,
    ConsommationElectricite decimal(15,4) NOT NULL,
    dateSaisie DATE NOT NULL,
    FOREIGN KEY (idConsommateur) REFERENCES consommateur(id),
    FOREIGN KEY (fichierChemin) REFERENCES fichierAnuelle(fichierChemin),
    FOREIGN KEY (agentDepo) REFERENCES agent(id),
    UNIQUE(annee,idConsommateur)
);

CREATE TABLE regularisationAnnuelle
(
    id int primary key AUTO_INCREMENT,
    idConsommationAnuelle int UNIQUE NOT NULL,
    differenceEau decimal(15,4) NOT NULL,
    differenceElectricite decimal(15,4) NOT NULL,
    montantEstime decimal(15,2) NOT NULL,
    dateLimite DATE NOT NULL,
    dateDernierEnvoi DATE,
    nombreEnvois int DEFAULT 0 NOT NULL,
    dateReglement DATE,
    FOREIGN KEY (idConsommationAnuelle) REFERENCES ConsommationAnuelle(id)
);

CREATE TABLE declaration(
    id int primary key AUTO_INCREMENT,
    idFournisseur int NOT NULL,
    idConsommateur int NOT NULL,
    idAdresseCompteur int NOT NULL,
    cheminCompteurEauImg varchar(255) ,
    cheminCompteurElectriciteImg varchar(255),
    indexEau decimal(15,4) NOT NULL,
    indexElectricite decimal(15,4) NOT NULL,
    dateSaisie DATE DEFAULT (CURRENT_DATE()) NOT NULL,
    statut varchar(100) DEFAULT 'Référence initiale' NOT NULL,
    dateDebut DATE DEFAULT (CURRENT_DATE()) NOT NULL,
    dateFin DATE,
    prixHT decimal(15,4),
    prixTTC decimal(15,2),
    INDEX idx_declaration_consommateur (idConsommateur),
    FOREIGN KEY (idFournisseur) REFERENCES fournisseur(id),
    FOREIGN KEY (idConsommateur) REFERENCES consommateur(id),
    FOREIGN KEY (idAdresseCompteur) REFERENCES adresseCompteur(id),
    UNIQUE(idAdresseCompteur,dateDebut,dateFin)
);

CREATE TABLE facture(
    id int primary key AUTO_INCREMENT,
    idDeclaration int UNIQUE NOT NULL,
    etat varchar(20) DEFAULT 'Impayée' NOT NULL, 
    datePaiement DATE,
    cheminGenere varchar(255) NOT NULL,
    dateGeneration DATE DEFAULT (CURRENT_DATE()) NOT NULL,
    FOREIGN KEY (idDeclaration) REFERENCES declaration(id),
    UNIQUE(cheminGenere)
);

CREATE TABLE reclamation(

    idReclamation int primary key AUTO_INCREMENT,
    id int NOT NULL,
    reponse varchar(1000),
    description varchar(1000) NOT NULL,
    dateCreation DATE DEFAULT (CURRENT_DATE()) NOT NULL,
    statut varchar(30) DEFAULT 'En Attente' NOT NULL,
    FOREIGN KEY (id) REFERENCES consommateur(id)
);

CREATE TABLE reclamationFuite(

    idReclamation int primary key,
    FOREIGN KEY (idReclamation) REFERENCES reclamation(idReclamation)
);


CREATE TABLE autre(

    idReclamation int primary key,
    FOREIGN KEY (idReclamation) REFERENCES reclamation(idReclamation)
);


CREATE TABLE fuiteInterne(

    idReclamation int primary key,
    FOREIGN KEY (idReclamation) REFERENCES reclamationFuite(idReclamation)
);

CREATE TABLE fuiteExterne(

    idReclamation int primary key,
    FOREIGN KEY (idReclamation) REFERENCES reclamationFuite(idReclamation)
);
CREATE TABLE reclamationFacture(

    idReclamation int primary key,
    id int NOT NULL,
    FOREIGN KEY (idReclamation) REFERENCES reclamation(idReclamation),
    FOREIGN KEY (id) REFERENCES facture(id)
);

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
