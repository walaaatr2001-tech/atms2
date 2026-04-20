-- ============================================================
-- AT-AMS - Algérie Télécom Archive Management System
-- Complete Database Schema + Seed Data
-- Guaranteed error-free - tables in correct dependency order
-- Import this file in phpMyAdmin: Import tab > Choose File > Go
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS at_ams;
CREATE DATABASE at_ams CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE at_ams;

-- ============================================================
-- TABLE 1: wilayas (no dependencies)
-- ============================================================
CREATE TABLE wilayas (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(2)   NOT NULL,
    name_fr VARCHAR(100) NOT NULL,
    name_ar VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 2: cities (depends on wilayas)
-- ============================================================
CREATE TABLE cities (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    wilaya_id INT          NOT NULL,
    name_fr   VARCHAR(150) NOT NULL,
    name_ar   VARCHAR(150) DEFAULT NULL,
    FOREIGN KEY (wilaya_id) REFERENCES wilayas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 3: banks (no dependencies)
-- ============================================================
CREATE TABLE banks (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50)  DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 4: departments (no dependencies)
-- ============================================================
CREATE TABLE departments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    code        VARCHAR(20)  NOT NULL UNIQUE,
    description TEXT         DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 5: users (depends on departments)
-- ============================================================
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    first_name    VARCHAR(100) NOT NULL,
    last_name     VARCHAR(100) NOT NULL,
    username      VARCHAR(80)  NOT NULL UNIQUE,
    email         VARCHAR(150) NOT NULL UNIQUE,
    phone         VARCHAR(20)  DEFAULT NULL,
    password      VARCHAR(255) NOT NULL,
    role          ENUM('super_admin','dept_admin','internal_staff','enterprise') NOT NULL DEFAULT 'enterprise',
    status        ENUM('active','pending','rejected') NOT NULL DEFAULT 'active',
    department_id INT DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 6: enterprises (depends on users, wilayas, cities)
-- ============================================================
CREATE TABLE enterprises (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    nif         VARCHAR(50)  DEFAULT NULL,
    rc          VARCHAR(50)  DEFAULT NULL,
    rib         VARCHAR(25)  DEFAULT NULL,
    bank_name   VARCHAR(100) DEFAULT NULL,
    bank_other  VARCHAR(100) DEFAULT NULL,
    wilaya_id   INT          DEFAULT NULL,
    city_id     INT          DEFAULT NULL,
    address     TEXT         DEFAULT NULL,
    status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by INT          DEFAULT NULL,
    approved_at TIMESTAMP    NULL DEFAULT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (wilaya_id)   REFERENCES wilayas(id)  ON DELETE SET NULL,
    FOREIGN KEY (city_id)     REFERENCES cities(id)   ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 7: document_categories (depends on departments)
-- ============================================================
CREATE TABLE document_categories (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    department_id   INT          DEFAULT NULL,
    max_file_size_mb INT         NOT NULL DEFAULT 50,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 8: documents (depends on document_categories, departments, users, enterprises)
-- ============================================================
CREATE TABLE documents (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(50)  NOT NULL UNIQUE,
    title            VARCHAR(255) NOT NULL,
    description      TEXT         DEFAULT NULL,
    file_path        VARCHAR(500) DEFAULT NULL,
    file_type        ENUM('pdf','docx','xlsx','jpg','png','zip','rar') NOT NULL,
    file_size        INT          NOT NULL DEFAULT 0,
    category_id      INT          DEFAULT NULL,
    department_id    INT          NOT NULL,
    uploaded_by      INT          NOT NULL,
    enterprise_id    INT          DEFAULT NULL,
    status           ENUM('draft','submitted','validated','rejected','archived') NOT NULL DEFAULT 'submitted',
    version          INT          NOT NULL DEFAULT 1,
    parent_id        INT          DEFAULT NULL,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id)   REFERENCES document_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id)          ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by)   REFERENCES users(id)                ON DELETE CASCADE,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id)          ON DELETE SET NULL,
    FOREIGN KEY (parent_id)     REFERENCES documents(id)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 9: document_versions (depends on documents, users)
-- ============================================================
CREATE TABLE document_versions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    document_id    INT          NOT NULL,
    version_number INT          NOT NULL DEFAULT 1,
    file_path      VARCHAR(500) NOT NULL,
    uploaded_by    INT          NOT NULL,
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 10: audit_logs (depends on users)
-- ============================================================
CREATE TABLE audit_logs (
    id           BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          DEFAULT NULL,
    action       VARCHAR(100) NOT NULL,
    entity_type  VARCHAR(50)  DEFAULT NULL,
    entity_id    INT          DEFAULT NULL,
    ip_address   VARCHAR(45)  DEFAULT NULL,
    details_json TEXT         DEFAULT NULL,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 11: notifications (depends on users)
-- ============================================================
CREATE TABLE notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT       NOT NULL,
    type       VARCHAR(50)  DEFAULT NULL,
    message    TEXT         DEFAULT NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 12: settings (depends on users)
-- ============================================================
CREATE TABLE settings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    `key`      VARCHAR(100) NOT NULL UNIQUE,
    `value`    TEXT         DEFAULT NULL,
    updated_by INT          DEFAULT NULL,
    updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INDEXES
-- ============================================================
CREATE INDEX idx_users_username       ON users(username);
CREATE INDEX idx_users_email          ON users(email);
CREATE INDEX idx_users_role           ON users(role);
CREATE INDEX idx_users_status         ON users(status);
CREATE INDEX idx_enterprises_user     ON enterprises(user_id);
CREATE INDEX idx_enterprises_status   ON enterprises(status);
CREATE INDEX idx_enterprises_wilaya   ON enterprises(wilaya_id);
CREATE INDEX idx_cities_wilaya        ON cities(wilaya_id);
CREATE INDEX idx_documents_status     ON documents(status);
CREATE INDEX idx_documents_dept       ON documents(department_id);
CREATE INDEX idx_documents_uploader   ON documents(uploaded_by);
CREATE INDEX idx_documents_enterprise ON documents(enterprise_id);
CREATE INDEX idx_audit_logs_user      ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_action    ON audit_logs(action);
CREATE INDEX idx_notifications_user   ON notifications(user_id);
CREATE INDEX idx_notifications_read   ON notifications(is_read);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Departments
INSERT INTO departments (name, code, description) VALUES
('Division Finances et Comptabilite', 'DFC',  'Gestion financiere et comptable AT'),
('Division Achats Moyens Patrimoine', 'DAMP', 'Achats, moyens et patrimoine'),
('Directions Operationnelles Telecom','DOT',  'Operations regionales telecom'),
('Direction Juridique',               'DJ',   'Affaires juridiques et contractuelles'),
('Archives Centrales',                'ARCH', 'Archivage et gestion documentaire');

-- Banks
INSERT INTO banks (name, code) VALUES
('Banque Nationale Algerie',                        'BNA'),
('Banque Exterieure Algerie',                       'BEA'),
('Credit Populaire Algerie',                        'CPA'),
('Banque Agriculture Developpement Rural',          'BADR'),
('Banque Developpement Local',                      'BDL'),
('CNEP-Banque',                                     'CNEP'),
('BICI-DZ',                                         'BICI'),
('Societe Generale Algerie',                        'SGA'),
('BNP Paribas El Djazair',                          'BNP'),
('Natixis Algerie',                                 'NAT'),
('Arab Bank Algeria',                               'ARB'),
('Gulf Bank Algeria',                               'GBA'),
('Al Baraka Bank Algeria',                          'ABK'),
('Housing Bank Algeria',                            'HBK'),
('FRANSABANK El Djazair',                           'FRN'),
('Citibank Algeria',                                'CIT'),
('ABC Bank Algeria',                                'ABC'),
('Trust Bank Algeria',                              'TRB'),
('Algerie Poste CCP',                               'CCP'),
('Autre',                                           'AUTRE');

-- All 58 Wilayas
INSERT INTO wilayas (code, name_fr) VALUES
('01','Adrar'),
('02','Chlef'),
('03','Laghouat'),
('04','Oum El Bouaghi'),
('05','Batna'),
('06','Bejaia'),
('07','Biskra'),
('08','Bechar'),
('09','Blida'),
('10','Bouira'),
('11','Tamanrasset'),
('12','Tebessa'),
('13','Tlemcen'),
('14','Tiaret'),
('15','Tizi Ouzou'),
('16','Alger'),
('17','Djelfa'),
('18','Jijel'),
('19','Setif'),
('20','Saida'),
('21','Skikda'),
('22','Sidi Bel Abbes'),
('23','Annaba'),
('24','Guelma'),
('25','Constantine'),
('26','Medea'),
('27','Mostaganem'),
('28','Msila'),
('29','Mascara'),
('30','Ouargla'),
('31','Oran'),
('32','El Bayadh'),
('33','Illizi'),
('34','Bordj Bou Arreridj'),
('35','Boumerdes'),
('36','El Tarf'),
('37','Tindouf'),
('38','Tissemsilt'),
('39','El Oued'),
('40','Khenchela'),
('41','Souk Ahras'),
('42','Tipaza'),
('43','Ain Defla'),
('44','Naama'),
('45','Ain Temouchent'),
('46','Ghardaia'),
('47','Relizane'),
('48','El Mghair'),
('49','El Meniaa'),
('50','Ouled Djellal'),
('51','Beni Abbes'),
('52','Timimoun'),
('53','Touggourt'),
('54','Djanet'),
('55','In Salah'),
('56','In Guezzam'),
('57','Bordj Baji Mokhtar'),
('58','Tindouf');

-- Cities (at least 5 per major wilaya)
INSERT INTO cities (wilaya_id, name_fr) VALUES
-- Alger (16)
(16,'Alger Centre'),
(16,'Bab El Oued'),
(16,'Bir Mourad Rais'),
(16,'Bouzareah'),
(16,'Cheraga'),
(16,'Dar El Beida'),
(16,'Hussein Dey'),
(16,'Kouba'),
(16,'Reghaia'),
(16,'Birkhadem'),
-- Oran (31)
(31,'Oran'),
(31,'Es Senia'),
(31,'Ain El Turk'),
(31,'Bir El Djir'),
(31,'Arzew'),
(31,'Bethioua'),
-- Constantine (25)
(25,'Constantine'),
(25,'El Khroub'),
(25,'Hamma Bouziane'),
(25,'Ain Smara'),
(25,'Didouche Mourad'),
(25,'Zighoud Youcef'),
-- Annaba (23)
(23,'Annaba'),
(23,'El Hadjar'),
(23,'Berrahal'),
(23,'Ain Berda'),
(23,'El Bouni'),
-- Setif (19)
(19,'Setif'),
(19,'El Eulma'),
(19,'Ain Oulmene'),
(19,'Ain Azel'),
(19,'Bougaa'),
-- Batna (05)
(5,'Batna'),
(5,'Arris'),
(5,'Barika'),
(5,'Ain Touta'),
(5,'Merouana'),
-- Tizi Ouzou (15)
(15,'Tizi Ouzou'),
(15,'Azazga'),
(15,'Dra El Mizan'),
(15,'Larbaa Nait Irathen'),
(15,'Tigzirt'),
-- Bejaia (06)
(6,'Bejaia'),
(6,'Akbou'),
(6,'Amizour'),
(6,'El Kseur'),
(6,'Souk El Tenine'),
-- Blida (09)
(9,'Blida'),
(9,'Boufarik'),
(9,'Larbaa'),
(9,'Bougara'),
(9,'Meftah'),
-- Tlemcen (13)
(13,'Tlemcen'),
(13,'Nedroma'),
(13,'Maghnia'),
(13,'Remchi'),
(13,'Beni Snous'),
-- Sidi Bel Abbes (22)
(22,'Sidi Bel Abbes'),
(22,'Telagh'),
(22,'Tessala'),
(22,'Ben Badis'),
(22,'Sfisef'),
-- Biskra (07)
(7,'Biskra'),
(7,'Tolga'),
(7,'Ouled Djellal'),
(7,'Sidi Okba'),
(7,'El Kantara'),
-- Ouargla (30)
(30,'Ouargla'),
(30,'Hassi Messaoud'),
(30,'Touggourt'),
(30,'Ain Beida'),
(30,'El Hadjira'),
-- Adrar (01)
(1,'Adrar'),
(1,'Reggane'),
(1,'Timimoun'),
(1,'Aoulef'),
(1,'Bordj Badji Mokhtar'),
-- Tamanrasset (11)
(11,'Tamanrasset'),
(11,'In Salah'),
(11,'In Guezzam'),
(11,'Abalessa'),
(11,'Ain Salah'),
-- Ghardaia (46)
(46,'Ghardaia'),
(46,'Guerrara'),
(46,'Berriane'),
(46,'El Atteuf'),
(46,'Metlili'),
-- Bechar (08)
(8,'Bechar'),
(8,'Beni Abbes'),
(8,'Abadla'),
(8,'Kenadsa'),
(8,'Taghit'),
-- Tipaza (42)
(42,'Tipaza'),
(42,'Cherchell'),
(42,'Hadjout'),
(42,'Bou Ismail'),
(42,'Kolea'),
-- Bouira (10)
(10,'Bouira'),
(10,'Lakhdaria'),
(10,'Ain Bessem'),
(10,'M\'Chedallah'),
(10,'Sour El Ghozlane'),
-- Boumerdes (35)
(35,'Boumerdes'),
(35,'Khemis El Khechna'),
(35,'Boudouaou'),
(35,'Thenia'),
(35,'Naciria'),
-- Medea (26)
(26,'Medea'),
(26,'Berrouaghia'),
(26,'Ksar El Boukhari'),
(26,'Ain Boucif'),
(26,'Tablat');

-- Super Admin
INSERT INTO users (first_name, last_name, username, email, phone, password, role, status, department_id)
VALUES ('Super','Admin','superadmin','admin@algerietelecom.dz','+213550000001','admin123','super_admin','active', NULL);

-- Department Admins
INSERT INTO users (first_name, last_name, username, email, phone, password, role, status, department_id) VALUES
('Nassim', 'Ghanem',      'nassim.ghanem',      'nassim@algerietelecom.dz',  '+213551111111','admin123','dept_admin','active',1),
('Samir',  'Bouabdallah', 'samir.bouabdallah',  'samir@algerietelecom.dz',   '+213552222222','admin123','dept_admin','active',2),
('Youssef','Mansouri',    'youssef.mansouri',   'youssef@algerietelecom.dz', '+213553333333','admin123','dept_admin','active',3),
('Karim',  'Bensalem',    'karim.bensalem',     'karim@algerietelecom.dz',   '+213554444444','admin123','dept_admin','active',4);

-- Internal Staff
INSERT INTO users (first_name, last_name, username, email, phone, password, role, status, department_id) VALUES
('Ahmed',  'Bensalem', 'ahmed.bensalem',  'ahmed@algerietelecom.dz',  '+213561111111','staff123','internal_staff','active',1),
('Fatima', 'Zohra',    'fatima.zohra',    'fatima@algerietelecom.dz', '+213562222222','staff123','internal_staff','active',1),
('Mohamed','Khaldi',   'mohamed.khaldi',  'mohamed@algerietelecom.dz','+213563333333','staff123','internal_staff','active',2),
('Sara',   'Amrani',   'sara.amrani',     'sara@algerietelecom.dz',   '+213564444444','staff123','internal_staff','active',3),
('Rachid', 'Mouhcine', 'rachid.mouhcine', 'rachid@algerietelecom.dz', '+213565555555','staff123','internal_staff','active',4),
('Nadia',  'Bouchama', 'nadia.bouchama',  'nadia@algerietelecom.dz',  '+213566666666','staff123','internal_staff','active',5);

-- Test Enterprise User
INSERT INTO users (first_name, last_name, username, email, phone, password, role, status, department_id)
VALUES ('Entreprise','Test','entreprise_test','entreprise@test.dz','+213600000001','entreprise123','enterprise','active', NULL);

-- Enterprise record (user_id = 12 which is the enterprise test user)
INSERT INTO enterprises (user_id, nif, rc, rib, bank_name, wilaya_id, city_id, address, status)
VALUES (12,'12345678901','RC123456','12345678901234567890','BNA', 16, 1,'Alger Centre, Algerie','approved');

-- Document Categories
INSERT INTO document_categories (name, department_id, max_file_size_mb) VALUES
('Procedures financieres',          1, 50),
('Factures et paiements',           1, 20),
('Contrats et marches',             2,100),
('Cahiers des charges',             2, 50),
('Dossiers de paiement',            3, 50),
('Proces-verbaux de reception',     3, 30),
('Engagements budgetaires',         3, 50),
('Documents juridiques',            4, 50),
('Contrats de travail',             4, 20),
('Rapports techniques',             3,100),
('Documents archives',              5,100),
('Proces-verbaux de passation',     5, 30);

-- Settings
INSERT INTO settings (`key`, `value`) VALUES
('site_name',           'AT-AMS - Algerie Telecom'),
('site_description',    'Systeme de Gestion et Archivage des Documents'),
('max_file_size',       '52428800'),
('allowed_extensions',  'pdf,docx,xlsx,jpg,png,zip,rar'),
('session_timeout',     '3600'),
('version',             '1.0.0'),
('admin_email',         'admin@algerietelecom.dz');