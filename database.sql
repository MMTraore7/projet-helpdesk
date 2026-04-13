CREATE DATABASE IF NOT EXISTS helpdesk;
USE helpdesk;

CREATE TABLE IF NOT EXISTS status (
    id_status INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS Role (
    id_role INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS Categories (
    id_categories INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS Utilisateurs (
    login VARCHAR(20) PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL, -- Augmenté pour stocker les hash de mots de passe
    actif BOOLEAN DEFAULT TRUE,
    id_role INT,
    failed_login_attempts INT DEFAULT 0,
    lockout_time DATETIME NULL,
    FOREIGN KEY (id_role) REFERENCES Role(id_role)
);

CREATE TABLE IF NOT EXISTS Tickets (
    id_tickets INT AUTO_INCREMENT PRIMARY KEY,
    description TEXT NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_reso DATETIME NULL,
    date_suivi DATETIME NULL,
    notes TEXT,
    id_categories INT,
    login VARCHAR(20),
    FOREIGN KEY (id_categories) REFERENCES Categories(id_categories),
    FOREIGN KEY (login) REFERENCES Utilisateurs(login) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS Logs (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    date_heure DATETIME DEFAULT CURRENT_TIMESTAMP,
    login VARCHAR(20) NULL,
    action VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    type VARCHAR(50) DEFAULT 'INFO',
    FOREIGN KEY (login) REFERENCES Utilisateurs(login) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS est_associe (
    id_tickets INT,
    id_status INT,
    PRIMARY KEY (id_tickets, id_status),
    FOREIGN KEY (id_tickets) REFERENCES Tickets(id_tickets) ON DELETE CASCADE,
    FOREIGN KEY (id_status) REFERENCES status(id_status) ON DELETE CASCADE
);

-- Insertion de données de base
INSERT IGNORE INTO Role (id_role, nom) VALUES (1, 'Client'), (2, 'Technicien'), (3, 'Admin');
INSERT IGNORE INTO status (id_status, libelle) VALUES (1, 'Nouveau'), (2, 'En cours'), (3, 'Résolu'), (4, 'Fermé');
INSERT IGNORE INTO Categories (id_categories, libelle) VALUES (1, 'Matériel'), (2, 'Logiciel'), (3, 'Réseau'), (4, 'Autre');

-- Création d'utilisateurs par défaut (mot de passe = 'password' hashé avec bcrypt)
-- HASH de 'password' : $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT IGNORE INTO Utilisateurs (login, nom, password, actif, id_role) VALUES 
('admin1', 'Super Admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, 3),
('tech1', 'Technicien Bob', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, 2),
('client1', 'Client Alice', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, 1);
