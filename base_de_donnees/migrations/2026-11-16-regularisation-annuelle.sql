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
