DROP TABLE IF EXISTS notification;
DROP TABLE IF EXISTS reclamationFacture;
DROP TABLE IF EXISTS fuiteExterne;
DROP TABLE IF EXISTS fuiteInterne;
DROP TABLE IF EXISTS autre;
DROP TABLE IF EXISTS reclamationFuite;
DROP TABLE IF EXISTS reclamation;
DROP TABLE IF EXISTS facture;
DROP TABLE IF EXISTS declaration;
DROP TABLE IF EXISTS regularisationAnnuelle;
DROP TABLE IF EXISTS ConsommationAnuelle;
DROP TABLE IF EXISTS fichierAnuelle;
DROP TABLE IF EXISTS contrat;
DROP TABLE IF EXISTS adresseCompteur;
DROP TABLE IF EXISTS quartier;
DROP TABLE IF EXISTS consommateur;
DROP TABLE IF EXISTS fournisseur;
DROP TABLE IF EXISTS agent;
DROP TABLE IF EXISTS utilisateur;

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
    seuilEau decimal(10,2) DEFAULT 100.00 NOT NULL,
    seuilElectricite decimal(10,2) DEFAULT 1000.00 NOT NULL,
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

CREATE TABLE quartier
(
    id int primary key AUTO_INCREMENT,
    nom varchar(100) NOT NULL UNIQUE,
    typeActivite varchar(50) NOT NULL
);

INSERT INTO quartier (nom, typeActivite) VALUES
   ('Centre-ville', 'Commercial'),
    ('Hay El Farah', 'Résidentiel économique'),
    ('Mhannech', 'Résidentiel standard'), ('Sania Ramel', 'Résidentiel standard'), ('Martil', 'Résidentiel standard'),
    ('Cabo Negro', 'Résidentiel haut standing'), ('Tetouan Park', 'Industriel');

CREATE TABLE adresseCompteur
(
    id int primary key AUTO_INCREMENT,
    idConsommateur int NOT NULL,
    adresse varchar(255) NOT NULL,
    categorie varchar(50) NOT NULL,
    idQuartier int,
    FOREIGN KEY (idConsommateur) REFERENCES consommateur(id),
    FOREIGN KEY (idQuartier) REFERENCES quartier(id)
);

CREATE TABLE contrat
(
    id int primary key AUTO_INCREMENT,
    dateDebut DATE DEFAULT (CURRENT_DATE()) NOT NULL,
    cheminGeneration varchar(255) NOT NULL,
    idConsommateur int NOT NULL,
    idAdresseCompteur int,
    statut varchar(20) DEFAULT 'actif' NOT NULL,
    FOREIGN KEY (idConsommateur) REFERENCES consommateur(id),
    FOREIGN KEY (idAdresseCompteur) REFERENCES adresseCompteur(id)
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
    modePaiement varchar(20),
    referencePaiement varchar(64),
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
    idUtilisateur int NOT NULL,
    type varchar(40) NOT NULL,
    titre varchar(150) NOT NULL,
    detail varchar(255),
    lien varchar(255),
    aTraiter tinyint(1) DEFAULT 0 NOT NULL,
    idObjet int,
    dateCreation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    dateLecture DATETIME,
    dateResolution DATETIME,
    FOREIGN KEY (idUtilisateur) REFERENCES utilisateur(id),
    INDEX idx_notification_utilisateur (idUtilisateur, id)
);

INSERT INTO utilisateur (id, nom, prenom, email, pass) VALUES (1, 'Admin', 'Fatourti', 'admin@fatourti.local', '$2y$10$St9FP7PrGHZLMuZ5KwWt8.yGswdFsZZHNzAx7EU0c6ZErqLolvfY2');
INSERT INTO fournisseur (id, seuilEau, seuilElectricite) VALUES (1, 100.00, 1000.00);
