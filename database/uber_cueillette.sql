-- phpMyAdmin SQL Dump
-- Base de données : `uber_cueillette`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table `uber_cueillette_agriculteur`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `uber_cueillette_agriculteur`;
CREATE TABLE IF NOT EXISTS `uber_cueillette_agriculteur` (
  `id_agriculteur` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(30) NOT NULL,
  `prenom` varchar(30) NOT NULL,
  `CIN` varchar(8) NOT NULL,
  `email` varchar(30) NOT NULL,
  `adresse` varchar(50) NOT NULL,
  `pseudo` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id_agriculteur`),
  UNIQUE KEY `CIN` (`CIN`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `pseudo` (`pseudo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

INSERT INTO `uber_cueillette_agriculteur` (`id_agriculteur`, `nom`, `prenom`, `CIN`, `email`, `adresse`, `pseudo`, `password`) VALUES
(1, 'Ben Ali', 'Mohamed', '12345678', 'mohamed.benali@email.com', 'Route de la Marsa, Tunis', 'mohamedagri', 'agri123$'),
(2, 'Trabelsi', 'Sami', '87654321', 'sami.trabelsi@email.com', 'Avenue Habib Bourguiba, Sousse', 'samiagri', 'agri456#'),
(3, 'Gharbi', 'Fatma', '45678912', 'fatma.gharbi@email.com', 'Rue de Indépendance, Nabeul', 'fatmaagri', 'agri789$');

-- --------------------------------------------------------
-- Table `uber_cueillette_ouvrier`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `uber_cueillette_ouvrier`;
CREATE TABLE IF NOT EXISTS `uber_cueillette_ouvrier` (
  `id_ouvrier` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(30) NOT NULL,
  `prenom` varchar(30) NOT NULL,
  `CIN` varchar(8) NOT NULL,
  `email` varchar(30) NOT NULL,
  `photo` longblob,
  `description` varchar(100) DEFAULT NULL,
  `pseudo` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id_ouvrier`),
  UNIQUE KEY `CIN` (`CIN`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `pseudo` (`pseudo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

INSERT INTO `uber_cueillette_ouvrier` (`id_ouvrier`, `nom`, `prenom`, `CIN`, `email`, `photo`, `description`, `pseudo`, `password`) VALUES
(1, 'Trabelsi', 'Ahmed', '11111111', 'ahmed.trabelsi@email.com', NULL, '3 ans expérience olives, disponible immédiatement', 'ahmedouv', 'ouvrier123$'),
(2, 'Khelifi', 'Nour', '22222222', 'nour.khelifi@email.com', NULL, '2 ans expérience agrumes, sérieux et ponctuel', 'nourow', 'ouvrier456#'),
(3, 'Jelassi', 'Omar', '33333333', 'omar.jelassi@email.com', NULL, 'Débutant motivé, apprend vite', 'omarouv', 'ouvrier789$'),
(4, 'Ben Salem', 'Houda', '44444444', 'houda.salem@email.com', NULL, '5 ans expérience, disponible tous les week-ends', 'houdaouv', 'ouvrier123$'),
(5, 'Mansouri', 'Karim', '55555555', 'karim.mansouri@email.com', NULL, 'Jeune retraité, disponible toute la saison', 'karimouv', 'ouvrier456#');

-- --------------------------------------------------------
-- Table `uber_cueillette_type_fruit`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `uber_cueillette_type_fruit`;
CREATE TABLE IF NOT EXISTS `uber_cueillette_type_fruit` (
  `id_type_fruit` int NOT NULL AUTO_INCREMENT,
  `libelle` varchar(20) NOT NULL,
  PRIMARY KEY (`id_type_fruit`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4;

INSERT INTO `uber_cueillette_type_fruit` (`id_type_fruit`, `libelle`) VALUES
(1, 'Olives'),
(2, 'Agrumes'),
(3, 'Tomates'),
(4, 'Raisins'),
(5, 'Fraises'),
(6, 'Pêches');

-- --------------------------------------------------------
-- Table `uber_cueillette_gouvernorat`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `uber_cueillette_gouvernorat`;
CREATE TABLE IF NOT EXISTS `uber_cueillette_gouvernorat` (
  `id_gouvernorat` int NOT NULL AUTO_INCREMENT,
  `libelle` varchar(30) NOT NULL,
  PRIMARY KEY (`id_gouvernorat`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4;

INSERT INTO `uber_cueillette_gouvernorat` (`id_gouvernorat`, `libelle`) VALUES
(1, 'Tunis'), (2, 'Ariana'), (3, 'Ben Arous'), (4, 'Manouba'), (5, 'Nabeul'),
(6, 'Zaghouan'), (7, 'Bizerte'), (8, 'Béja'), (9, 'Jendouba'), (10, 'Kef'),
(11, 'Siliana'), (12, 'Sousse'), (13, 'Monastir'), (14, 'Mahdia'), (15, 'Sfax'),
(16, 'Kairouan'), (17, 'Kasserine'), (18, 'Sidi Bouzid'), (19, 'Gabès'),
(20, 'Médenine'), (21, 'Tataouine'), (22, 'Gafsa'), (23, 'Tozeur'), (24, 'Kébili');

-- --------------------------------------------------------
-- Table `uber_cueillette_offre` (avec colonne description ajoutée)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `uber_cueillette_offre`;
CREATE TABLE IF NOT EXISTS `uber_cueillette_offre` (
  `id_offre` int NOT NULL AUTO_INCREMENT,
  `id_type_fruit` int NOT NULL,
  `id_gouvernorat` int NOT NULL,
  `adresse` varchar(50) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nombre_ouvriers` int NOT NULL,
  `prix_journee` float NOT NULL,
  `description` TEXT,
  `date_limite` date NOT NULL,
  `id_agriculteur` int NOT NULL,
  PRIMARY KEY (`id_offre`),
  KEY `fk_offre_type_fruit` (`id_type_fruit`),
  KEY `fk_offre_gouvernorat` (`id_gouvernorat`),
  KEY `fk_offre_agriculteur` (`id_agriculteur`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;

INSERT INTO `uber_cueillette_offre` (`id_offre`, `id_type_fruit`, `id_gouvernorat`, `adresse`, `date_debut`, `date_fin`, `nombre_ouvriers`, `prix_journee`, `description`, `date_limite`, `id_agriculteur`) VALUES
(1, 1, 1, 'Domaine Borj El Amri, Tunis', '2026-05-01', '2026-05-15', 10, 35, 'Récolte olives, repas fourni', '2026-04-25', 1),
(2, 2, 4, 'Ferme Sousse, Route de Monastir', '2026-06-01', '2026-06-20', 8, 40, 'Récolte agrumes, transport assuré', '2026-05-25', 2),
(3, 3, 5, 'Champs Nabeul, Zone agricole', '2026-07-01', '2026-07-30', 15, 30, 'Récolte tomates, expérience souhaitée', '2026-06-20', 3),
(4, 1, 3, 'Domaine Ben Arous, Cité Ettahrir', '2026-05-10', '2026-05-25', 5, 38, NULL, '2026-05-05', 1),
(5, 4, 12, 'Vignoble Sousse, Kalâa Kebira', '2026-08-01', '2026-08-20', 6, 45, NULL, '2026-07-25', 2),
(6, 5, 5, 'Ferme Fraises, Korba', '2026-04-01', '2026-04-20', 12, 32, NULL, '2026-03-28', 3),
(7, 6, 15, 'Pêcherie Sfax, Route de Gabès', '2026-07-15', '2026-08-05', 7, 42, NULL, '2026-07-10', 1);

-- --------------------------------------------------------
-- Table `uber_cueillette_candidature`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `uber_cueillette_candidature`;
CREATE TABLE IF NOT EXISTS `uber_cueillette_candidature` (
  `id_candidature` int NOT NULL AUTO_INCREMENT,
  `id_offre` int NOT NULL,
  `id_ouvrier` int NOT NULL,
  `decision` enum('en_attente','acceptee','refusee') DEFAULT 'en_attente',
  `date_candidature` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `note` int DEFAULT NULL,
  `commentaire` varchar(50) DEFAULT NULL,
  `remuneration` float DEFAULT NULL,
  PRIMARY KEY (`id_candidature`),
  KEY `fk_candidature_offre` (`id_offre`),
  KEY `fk_candidature_ouvrier` (`id_ouvrier`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4;

INSERT INTO `uber_cueillette_candidature` (`id_candidature`, `id_offre`, `id_ouvrier`, `decision`, `date_candidature`, `note`, `commentaire`, `remuneration`) VALUES
(1, 1, 1, 'acceptee', '2026-03-15 10:30:00', 8, 'Très bon travail, sérieux', 525),
(2, 1, 2, 'en_attente', '2026-03-16 14:20:00', NULL, NULL, NULL),
(3, 1, 3, 'refusee', '2026-03-14 09:15:00', NULL, NULL, NULL),
(4, 4, 1, 'en_attente', '2026-03-20 11:00:00', NULL, NULL, NULL),
(5, 4, 4, 'acceptee', '2026-03-18 16:30:00', NULL, NULL, NULL),
(6, 4, 5, 'en_attente', '2026-03-19 08:45:00', NULL, NULL, NULL),
(7, 7, 2, 'en_attente', '2026-03-25 13:15:00', NULL, NULL, NULL),
(8, 7, 3, 'en_attente', '2026-03-24 10:00:00', NULL, NULL, NULL),
(9, 2, 2, 'acceptee', '2026-03-10 09:00:00', 9, 'Excellent, ponctuel', 800),
(10, 2, 4, 'acceptee', '2026-03-11 11:30:00', 7, 'Bon travail', 800),
(11, 2, 5, 'en_attente', '2026-03-12 14:00:00', NULL, NULL, NULL),
(12, 5, 1, 'en_attente', '2026-03-22 15:45:00', NULL, NULL, NULL),
(13, 3, 3, 'acceptee', '2026-03-05 08:30:00', 9, 'Très bien, motivé', 900),
(14, 3, 5, 'acceptee', '2026-03-06 10:15:00', 8, 'Sérieux', 900);

-- --------------------------------------------------------
-- Clés étrangères
-- --------------------------------------------------------

ALTER TABLE `uber_cueillette_candidature`
  ADD CONSTRAINT `fk_candidature_offre` FOREIGN KEY (`id_offre`) REFERENCES `uber_cueillette_offre` (`id_offre`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_candidature_ouvrier` FOREIGN KEY (`id_ouvrier`) REFERENCES `uber_cueillette_ouvrier` (`id_ouvrier`) ON DELETE CASCADE;

ALTER TABLE `uber_cueillette_offre`
  ADD CONSTRAINT `fk_offre_agriculteur` FOREIGN KEY (`id_agriculteur`) REFERENCES `uber_cueillette_agriculteur` (`id_agriculteur`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_offre_gouvernorat` FOREIGN KEY (`id_gouvernorat`) REFERENCES `uber_cueillette_gouvernorat` (`id_gouvernorat`),
  ADD CONSTRAINT `fk_offre_type_fruit` FOREIGN KEY (`id_type_fruit`) REFERENCES `uber_cueillette_type_fruit` (`id_type_fruit`);

COMMIT;