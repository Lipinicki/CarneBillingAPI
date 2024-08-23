CREATE DATABASE db_cobranca;
USE db_cobranca;

CREATE TABLE Carne (
    Id INT NOT NULL AUTO_INCREMENT PRIMARY KEY
);

CREATE TABLE Parcela (
    Numero INT NOT NULL,
    Valor FLOAT NOT NULL,
    Data_Vencimento DATE NOT NULL,
    Entrada BOOLEAN NOT NULL,
    Carne_Id INT NOT NULL,
    FOREIGN KEY (Carne_Id) REFERENCES Carne(Id)
);