-- ============================================================
--  UnivThèqueFs — Base de données CORRIGÉE
--  Version 2.0 — Import sécurisé (IF NOT EXISTS + INSERT IGNORE)
--  Filière Informatique · FS · Université de Ngaoundéré
-- ============================================================

CREATE DATABASE IF NOT EXISTS univtheque_fs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE univtheque_fs;

-- Désactiver les contraintes FK pendant l'import
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLE: niveaux
-- ============================================================
CREATE TABLE IF NOT EXISTS niveaux (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    intitule VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT IGNORE INTO niveaux (id, code, intitule, description) VALUES
(1, 'L1', 'Licence 1', 'Première année du cycle Licence en Informatique'),
(2, 'L2', 'Licence 2', 'Deuxième année du cycle Licence en Informatique'),
(3, 'L3', 'Licence 3', 'Troisième année du cycle Licence en Informatique');

-- ============================================================
-- TABLE: semestres
-- ============================================================
CREATE TABLE IF NOT EXISTS semestres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero TINYINT NOT NULL,
    niveau_id INT NOT NULL,
    intitule VARCHAR(20) NOT NULL,
    FOREIGN KEY (niveau_id) REFERENCES niveaux(id) ON DELETE CASCADE
);
INSERT IGNORE INTO semestres (id, numero, niveau_id, intitule) VALUES
(1, 1, 1, 'Semestre 1'), (2, 2, 1, 'Semestre 2'),
(3, 3, 2, 'Semestre 3'), (4, 4, 2, 'Semestre 4'),
(5, 5, 3, 'Semestre 5'), (6, 6, 3, 'Semestre 6');

-- ============================================================
-- TABLE: professeurs
-- ============================================================
CREATE TABLE IF NOT EXISTS professeurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100),
    grade ENUM('Pr','Dr','MCF','Ater') NOT NULL DEFAULT 'Dr',
    email VARCHAR(150) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT IGNORE INTO professeurs (id, nom, prenom, grade) VALUES
(1,  'Dayang',      '', 'Pr'),
(2,  'Adamou',      '', 'Dr'),
(3,  'Tchakounte',  '', 'Pr'),
(4,  'Djeutcha',    '', 'Dr'),
(5,  'Guidana',     '', 'Dr'),
(6,  'Mbala',       '', 'Dr'),
(7,  'Batoure',     '', 'Pr'),
(8,  'Kamla',       '', 'Pr'),
(9,  'Hanwa',       '', 'Dr'),
(10, 'Nlong',       '', 'Pr');

-- ============================================================
-- TABLE: unites_enseignement
-- ============================================================
CREATE TABLE IF NOT EXISTS unites_enseignement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    intitule VARCHAR(150) NOT NULL,
    credits TINYINT NOT NULL DEFAULT 3,
    semestre_id INT NOT NULL,
    professeur_id INT,
    description TEXT,
    FOREIGN KEY (semestre_id) REFERENCES semestres(id) ON DELETE CASCADE,
    FOREIGN KEY (professeur_id) REFERENCES professeurs(id) ON DELETE SET NULL
);
-- L1 S1
INSERT IGNORE INTO unites_enseignement (code, intitule, credits, semestre_id) VALUES
('INF111', 'Analyse 1',                       6, 1),
('INF121', 'Architecture des Ordinateurs',    6, 1),
('INF131', 'Introduction à l''Algorithmique', 6, 1),
('INF141', 'Programmation Structurée',         6, 1),
('INF151', 'Formation Bilingue 1',             3, 1),
('INF161', 'Electrostatique',                  3, 1);
-- L1 S2
INSERT IGNORE INTO unites_enseignement (code, intitule, credits, semestre_id) VALUES
('INF112', 'Algèbre 1',                                  6, 2),
('INF122', 'Programmation Fonctionnelle',                 6, 2),
('INF132', 'Introduction aux Réseaux',                   6, 2),
('INF142', 'Introduction aux Systèmes d''Exploitation',  6, 2),
('INF152', 'Electronique Numérique 1',                   3, 2),
('INF162', 'Electrocinétique / Magnétostatique',         3, 2);
-- L2 S3
INSERT IGNORE INTO unites_enseignement (code, intitule, credits, semestre_id) VALUES
('INF213', 'Analyse 2 pour l''Informatique',          6, 3),
('INF223', 'Introduction aux Bases de Données',       6, 3),
('INF233', 'Programmation Orientée Objet',             6, 3),
('INF243', 'Structure des Données',                   6, 3),
('INF253', 'Formation Bilingue 2',                    3, 3),
('INF263', 'Introduction aux Traitements du Signal',  3, 3);
-- L2 S4
INSERT IGNORE INTO unites_enseignement (code, intitule, credits, semestre_id) VALUES
('INF214', 'Algèbre 2 pour l''Informatique',          6, 4),
('INF224', 'Introduction au Génie Logiciel',          6, 4),
('INF234', 'Introduction aux Systèmes d''Information',6, 4),
('INF244', 'Programmation Web',                       6, 4),
('INF254', 'Electronique Numérique 2',                3, 4),
('INF264', 'Arithmétique pour l''Informatique',       3, 4);
-- L3 S5
INSERT IGNORE INTO unites_enseignement (code, intitule, credits, semestre_id) VALUES
('INF315', 'Architecture des Réseaux Sans Fil',        6, 5),
('INF325', 'Conception et Analyse des Algorithmes',    6, 5),
('INF335', 'Réseau et Système d''Exploitation',        6, 5),
('INF345', 'Système d''Exploitation Mobile',           6, 5),
('INF355', 'Ethique, Morale et Savoir-Vivre',          3, 5),
('INF365', 'Cloud Computing',                          3, 5);
-- L3 S6
INSERT IGNORE INTO unites_enseignement (code, intitule, credits, semestre_id) VALUES
('INF316', 'Administration des Systèmes et Réseaux',            6, 6),
('INF326', 'Bases de Données',                                   6, 6),
('INF336', 'Ingénierie des Applications Web',                    6, 6),
('INF346', 'Langages Formels et Introduction à la Compilation',  6, 6),
('INF356', 'Probabilités et Statistiques',                       6, 6),
('INF366', 'Recherche Opérationnelle',                           6, 6);

-- ============================================================
-- TABLE: types_documents
-- ============================================================
CREATE TABLE IF NOT EXISTS types_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    intitule VARCHAR(80) NOT NULL,
    icone VARCHAR(50)
);
INSERT IGNORE INTO types_documents (id, code, intitule, icone) VALUES
(1, 'COURS',   'Cours',                       'fa-book'),
(2, 'TD',      'Travaux Dirigés',              'fa-pencil-alt'),
(3, 'TP',      'Travaux Pratiques',            'fa-flask'),
(4, 'CC',      'Contrôle Continu',             'fa-file-alt'),
(5, 'EXAM_SN', 'Examen Session Normale',       'fa-graduation-cap'),
(6, 'EXAM_SR', 'Examen Session de Rattrapage', 'fa-redo'),
(7, 'TPE',     'Travaux Pratiques Encadrés',   'fa-tasks');

-- ============================================================
-- TABLE: annees_academiques
-- ============================================================
CREATE TABLE IF NOT EXISTS annees_academiques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intitule VARCHAR(20) NOT NULL UNIQUE,
    active TINYINT(1) DEFAULT 0
);
INSERT IGNORE INTO annees_academiques (id, intitule, active) VALUES
(1, '2021-2022', 0),
(2, '2022-2023', 0),
(3, '2023-2024', 0),
(4, '2024-2025', 1),
(5, '2025-2026', 0);

-- ============================================================
-- TABLE: utilisateurs
-- ============================================================
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin','professeur','etudiant') NOT NULL DEFAULT 'etudiant',
    niveau_id INT,
    actif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (niveau_id) REFERENCES niveaux(id) ON DELETE SET NULL
);
-- ⚠️ Mot de passe temporaire — à remplacer via create_admin.php
-- Le hash ci-dessous correspond à : Admin@2024
INSERT IGNORE INTO utilisateurs (id, nom, prenom, email, mot_de_passe, role) VALUES
(1, 'Admin', 'Système', 'admin@univtheque.cm',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'admin'),
(2, 'Etudiant', 'Test', 'etudiant@univtheque.cm',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'etudiant');
-- ⚠️ Le hash ci-dessus est un exemple — lance create_admin.php pour générer les vrais hashs

-- ============================================================
-- TABLE: documents — statut harmonisé avec le PHP
-- ============================================================
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    ue_id INT NOT NULL,
    type_doc_id INT NOT NULL,
    professeur_id INT,
    annee_id INT,
    nom_fichier VARCHAR(500) NOT NULL,
    taille_fichier BIGINT DEFAULT 0,
    nb_telechargements INT DEFAULT 0,
    nb_vues INT DEFAULT 0,
    statut ENUM('en_attente','publie','refuse') NOT NULL DEFAULT 'en_attente',
    uploade_par INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ue_id) REFERENCES unites_enseignement(id) ON DELETE CASCADE,
    FOREIGN KEY (type_doc_id) REFERENCES types_documents(id),
    FOREIGN KEY (professeur_id) REFERENCES professeurs(id) ON DELETE SET NULL,
    FOREIGN KEY (annee_id) REFERENCES annees_academiques(id),
    FOREIGN KEY (uploade_par) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_ue (ue_id),
    INDEX idx_type (type_doc_id),
    INDEX idx_statut (statut),
    FULLTEXT INDEX idx_ft_titre (titre, description)
);

-- ============================================================
-- TABLE: journaux_acces
-- ============================================================
CREATE TABLE IF NOT EXISTS journaux_acces (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT,
    document_id INT,
    action ENUM('vue','telechargement','upload','suppression','connexion','deconnexion'),
    ip_adresse VARCHAR(45),
    date_acces TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE: favoris
-- ============================================================
CREATE TABLE IF NOT EXISTS favoris (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    document_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_favori (utilisateur_id, document_id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
);

-- ============================================================
-- VUE: vue_documents_complets (statut harmonisé → publie)
-- ============================================================
DROP VIEW IF EXISTS vue_documents_complets;
CREATE VIEW vue_documents_complets AS
SELECT
    d.id,
    d.titre,
    d.description,
    d.nom_fichier,
    d.taille_fichier,
    d.nb_telechargements,
    d.nb_vues,
    d.statut,
    d.created_at,
    ue.code       AS code_ue,
    ue.intitule   AS intitule_ue,
    ue.credits    AS ue_credits,
    td.code       AS type_code,
    td.intitule   AS type_label,
    td.icone      AS type_icone,
    CONCAT(p.grade, ' ', p.nom) AS professeur,
    s.numero      AS semestre_numero,
    n.code        AS niveau,
    n.intitule    AS niveau_intitule,
    aa.intitule   AS annee
FROM documents d
JOIN unites_enseignement ue ON d.ue_id = ue.id
JOIN types_documents td     ON d.type_doc_id = td.id
JOIN semestres s             ON ue.semestre_id = s.id
JOIN niveaux n               ON s.niveau_id = n.id
LEFT JOIN professeurs p      ON d.professeur_id = p.id
LEFT JOIN annees_academiques aa ON d.annee_id = aa.id
WHERE d.statut = 'publie';

-- Réactiver les contraintes FK
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FIN — Base de données prête
-- Lancer create_admin.php pour définir les vrais mots de passe
-- ============================================================

-- ── sessions_persistantes (Remember Me) ──────────────────────
CREATE TABLE IF NOT EXISTS sessions_persistantes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    token          VARCHAR(64) NOT NULL UNIQUE,
    expiry         DATETIME NOT NULL,
    ip_adresse     VARCHAR(45),
    user_agent     TEXT,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expiry (expiry)
);
