-- Script SQL pour la base de données touristique_exp_bd
CREATE DATABASE IF NOT EXISTS touristique_exp_bd;
USE touristique_exp_bd;

-- Table des utilisateurs (admin et employés)
CREATE TABLE utilisateurs (
    id_utilisateur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employe_ticket', 'chauffeur') DEFAULT 'employe_ticket',
    telephone VARCHAR(20),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des voyageurs (clients)
CREATE TABLE voyageurs (
    id_voyageur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    telephone VARCHAR(20) NOT NULL,
    adresse TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des chauffeurs
CREATE TABLE chauffeurs (
    id_chauffeur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    numero_permis VARCHAR(50) UNIQUE NOT NULL,
    date_embauche DATE,
    statut ENUM('actif', 'inactif') DEFAULT 'actif'
);

-- Table des bus
CREATE TABLE bus (
    id_bus INT AUTO_INCREMENT PRIMARY KEY,
    numero_bus VARCHAR(50) UNIQUE NOT NULL,
    marque VARCHAR(100),
    capacite_passagers INT,
    capacite_colis DECIMAL(10,2),
    statut ENUM('disponible', 'en_service', 'maintenance') DEFAULT 'disponible'
);

-- Table des itinéraires
CREATE TABLE itineraires (
    id_itineraire INT AUTO_INCREMENT PRIMARY KEY,
    ville_depart VARCHAR(100) NOT NULL,
    ville_arrivee VARCHAR(100) NOT NULL,
    distance_km DECIMAL(10,2),
    duree_estimee TIME,
    prix_base DECIMAL(10,2) NOT NULL
);

-- Table des tickets/réservations
CREATE TABLE tickets (
    id_ticket INT AUTO_INCREMENT PRIMARY KEY,
    numero_ticket VARCHAR(50) UNIQUE NOT NULL,
    id_voyageur INT NOT NULL,
    id_itineraire INT NOT NULL,
    id_chauffeur INT,
    id_bus INT,
    date_voyage DATE NOT NULL,
    heure_depart TIME NOT NULL,
    nombre_places INT DEFAULT 1,
    prix_total DECIMAL(10,2) NOT NULL,
    statut ENUM('reserve', 'confirme', 'annule', 'complete') DEFAULT 'reserve',
    date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_voyageur) REFERENCES voyageurs(id_voyageur) ON DELETE CASCADE,
    FOREIGN KEY (id_itineraire) REFERENCES itineraires(id_itineraire),
    FOREIGN KEY (id_chauffeur) REFERENCES chauffeurs(id_chauffeur),
    FOREIGN KEY (id_bus) REFERENCES bus(id_bus)
);

-- Table des expéditeurs
CREATE TABLE expediteurs (
    id_expediteur INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    email VARCHAR(150),
    adresse TEXT
);

-- Table des destinataires
CREATE TABLE destinataires (
    id_destinataire INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20) NOT NULL,
    email VARCHAR(150),
    adresse TEXT NOT NULL
);

-- Table des colis
CREATE TABLE colis (
    id_colis INT AUTO_INCREMENT PRIMARY KEY,
    numero_suivi VARCHAR(50) UNIQUE NOT NULL,
    id_expediteur INT NOT NULL,
    id_destinataire INT NOT NULL,
    id_itineraire INT NOT NULL,
    id_chauffeur INT,
    id_bus INT,
    description TEXT,
    poids DECIMAL(10,2) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    date_expedition DATE NOT NULL,
    date_livraison_prevue DATE,
    statut ENUM('en_attente', 'en_transit', 'livre', 'annule') DEFAULT 'en_attente',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_expediteur) REFERENCES expediteurs(id_expediteur),
    FOREIGN KEY (id_destinataire) REFERENCES destinataires(id_destinataire),
    FOREIGN KEY (id_itineraire) REFERENCES itineraires(id_itineraire),
    FOREIGN KEY (id_chauffeur) REFERENCES chauffeurs(id_chauffeur),
    FOREIGN KEY (id_bus) REFERENCES bus(id_bus)
);

-- Table des reçus
CREATE TABLE recus (
    id_recu INT AUTO_INCREMENT PRIMARY KEY,
    numero_recu VARCHAR(50) UNIQUE NOT NULL,
    id_colis INT NOT NULL,
    montant_paye DECIMAL(10,2) NOT NULL,
    mode_paiement ENUM('especes', 'carte', 'mobile_money') NOT NULL,
    date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_colis) REFERENCES colis(id_colis) ON DELETE CASCADE
);

-- Table des virements (paiements)
CREATE TABLE virements (
    id_virement INT AUTO_INCREMENT PRIMARY KEY,
    id_ticket INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    mode_paiement ENUM('especes', 'carte', 'mobile_money', 'virement') NOT NULL,
    statut ENUM('en_attente', 'valide', 'refuse') DEFAULT 'en_attente',
    date_virement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_ticket) REFERENCES tickets(id_ticket) ON DELETE CASCADE
);

-- Insertion de données de test
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone) VALUES
('Admin', 'Principal', 'admin@touristique-express.com', MD5('admin123'), 'admin', '+237690000001'),
('Dupont', 'Marie', 'marie.dupont@touristique-express.com', MD5('employe123'), 'employe_ticket', '+237690000002');

INSERT INTO itineraires (ville_depart, ville_arrivee, distance_km, duree_estimee, prix_base) VALUES
('Yaoundé', 'Douala', 250.00, '04:00:00', 5000.00),
('Douala', 'Bafoussam', 280.00, '04:30:00', 5500.00),
('Yaoundé', 'Bafoussam', 320.00, '05:00:00', 6000.00),
('Douala', 'Buea', 75.00, '01:30:00', 2500.00);

INSERT INTO chauffeurs (nom, prenom, telephone, numero_permis, date_embauche, statut) VALUES
('Kamga', 'Paul', '+237677111111', 'CM-2024-001', '2020-01-15', 'actif'),
('Nkomo', 'Jean', '+237677222222', 'CM-2024-002', '2021-03-20', 'actif'),
('Fotso', 'Pierre', '+237677333333', 'CM-2024-003', '2022-06-10', 'actif');

INSERT INTO bus (numero_bus, marque, capacite_passagers, capacite_colis, statut) VALUES
('TE-001', 'Mercedes Benz', 50, 500.00, 'disponible'),
('TE-002', 'Volvo', 45, 450.00, 'disponible'),
('TE-003', 'Scania', 55, 600.00, 'disponible');