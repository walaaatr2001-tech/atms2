-- ============================================================
-- AT-AMS COMPLETE DATABASE SCHEMA
-- Algeria Telecom Asset Management System
-- One file - Import in phpMyAdmin
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
    bureau        VARCHAR(100) DEFAULT NULL,
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
    contract_id     INT          DEFAULT NULL,
    ods_id          INT          DEFAULT NULL,
    payment_dossier_id INT       DEFAULT NULL,
    contract_number VARCHAR(100) DEFAULT NULL,
    extracted_json  TEXT DEFAULT NULL,
    ai_processed    TINYINT(1)   DEFAULT 0,
    status           ENUM('draft','submitted','validated','rejected','archived') NOT NULL DEFAULT 'submitted',
    version          INT          NOT NULL DEFAULT 1,
    parent_id        INT          DEFAULT NULL,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id)   REFERENCES document_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id)          ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by)   REFERENCES users(id)                ON DELETE CASCADE,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id)          ON DELETE SET NULL,
    FOREIGN KEY (contract_id)   REFERENCES contracts(id)            ON DELETE SET NULL,
    FOREIGN KEY (ods_id)        REFERENCES ods(id)                   ON DELETE SET NULL,
    FOREIGN KEY (parent_id)    REFERENCES documents(id)            ON DELETE SET NULL
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
-- TABLE 10: contracts (depends on enterprises, departments, users)
-- ============================================================
CREATE TABLE contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_number VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    contract_type VARCHAR(100) DEFAULT NULL,
    enterprise_id INT DEFAULT NULL,
    department_id INT DEFAULT NULL,
    total_amount DECIMAL(15,2) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status ENUM('active','completed','suspended','cancelled') DEFAULT 'active',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 11: ods - Ordres de Service (depends on contracts, enterprises, documents)
-- ============================================================
CREATE TABLE ods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ods_number VARCHAR(100) NOT NULL,
    contract_id INT DEFAULT NULL,
    enterprise_id INT DEFAULT NULL,
    bureau VARCHAR(100) DEFAULT NULL,
    lot_number VARCHAR(50) DEFAULT NULL,
    amount DECIMAL(15,2) DEFAULT NULL,
    issue_date DATE DEFAULT NULL,
    description TEXT DEFAULT NULL,
    status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    document_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE SET NULL,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE SET NULL,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 12: payment_dossiers (depends on contracts, ods, enterprises)
-- ============================================================
CREATE TABLE payment_dossiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dossier_number VARCHAR(100) NOT NULL,
    contract_id INT DEFAULT NULL,
    ods_id INT DEFAULT NULL,
    enterprise_id INT DEFAULT NULL,
    amount DECIMAL(15,2) DEFAULT NULL,
    status ENUM('pending','in_progress','paid','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE SET NULL,
    FOREIGN KEY (ods_id) REFERENCES ods(id) ON DELETE SET NULL,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 13: payment_dossier_docs (depends on payment_dossiers, documents)
-- ============================================================
CREATE TABLE payment_dossier_docs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dossier_id INT NOT NULL,
    doc_type ENUM('ods_copy','pv_reception','facture','attachement','bon_commande','releve') NOT NULL,
    document_id INT DEFAULT NULL,
    is_submitted TINYINT(1) DEFAULT 0,
    submitted_at TIMESTAMP NULL,
    FOREIGN KEY (dossier_id) REFERENCES payment_dossiers(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE 14: audit_logs (depends on users)
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
-- TABLE 15: notifications (depends on users)
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
-- TABLE 16: settings (depends on users)
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
CREATE INDEX idx_documents_contract ON documents(contract_id);
CREATE INDEX idx_audit_logs_user      ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_action    ON audit_logs(action);
CREATE INDEX idx_notifications_user   ON notifications(user_id);
CREATE INDEX idx_notifications_read   ON notifications(is_read);

-- Contracts indexes
CREATE INDEX idx_contracts_enterprise ON contracts(enterprise_id);
CREATE INDEX idx_contracts_department ON contracts(department_id);
CREATE INDEX idx_contracts_status ON contracts(status);
CREATE INDEX idx_contracts_number ON contracts(contract_number);

-- ODS indexes
CREATE INDEX idx_ods_contract ON ods(contract_id);
CREATE INDEX idx_ods_enterprise ON ods(enterprise_id);
CREATE INDEX idx_ods_status ON ods(status);
CREATE INDEX idx_ods_number ON ods(ods_number);

-- Payment dossiers indexes
CREATE INDEX idx_payment_contract ON payment_dossiers(contract_id);
CREATE INDEX idx_payment_ods ON payment_dossiers(ods_id);
CREATE INDEX idx_payment_enterprise ON payment_dossiers(enterprise_id);
CREATE INDEX idx_payment_status ON payment_dossiers(status);
CREATE INDEX idx_payment_number ON payment_dossiers(dossier_number);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA - Departments
-- ============================================================
INSERT INTO departments (name, code, description) VALUES
('Division Finances et Comptabilite', 'DFC', 'Gestion financiere et comptable AT'),
('Division Achats Moyens Patrimoine', 'DAMP', 'Achats, moyens et patrimoine'),
('Directions Operationnelles Telecom','DOT', 'Operations regionales telecom'),
('Direction Juridique', 'DJ', 'Affaires juridiques et contractuelles'),
('Archives Centrales', 'ARCH', 'Archivage et gestion documentaire');

-- ============================================================
-- SEED DATA - Banks
-- ============================================================
INSERT INTO banks (name, code) VALUES
('Banque Nationale Algerie', 'BNA'),
('Banque Exterieure Algerie', 'BEA'),
('Credit Populaire Algerie', 'CPA'),
('Banque Agriculture Developpement Rural', 'BADR'),
('Banque Developpement Local', 'BDL'),
('CNEP-Banque', 'CNEP'),
('Societe Generale Algerie', 'SGA'),
('BNP Paribas El Djazair', 'BNP'),
('Arab Bank Algeria', 'ARB'),
('Al Baraka Bank Algeria', 'ABK'),
('Autre', 'AUTRE');

-- ============================================================
-- SEED DATA - Wilayas (58)
-- ============================================================
INSERT INTO wilayas (code, name_fr) VALUES
('01','Adrar'), ('02','Chlef'), ('03','Laghouat'), ('04','Oum El Bouaghi'),
('05','Batna'), ('06','Bejaia'), ('07','Biskra'), ('08','Bechar'),
('09','Blida'), ('10','Bouira'), ('11','Tamanrasset'), ('12','Tebessa'),
('13','Tlemcen'), ('14','Tiaret'), ('15','Tizi Ouzou'), ('16','Alger'),
('17','Djelfa'), ('18','Jijel'), ('19','Setif'), ('20','Saida'),
('21','Skikda'), ('22','Sidi Bel Abbes'), ('23','Annaba'), ('24','Guelma'),
('25','Constantine'), ('26','Medea'), ('27','Mostaganem'), ('28','Msila'),
('29','Mascara'), ('30','Ouargla'), ('31','Oran'), ('32','El Bayadh'),
('33','Illizi'), ('34','Bordj Bou Arreridj'), ('35','Boumerdes'), ('36','El Tarf'),
('37','Tindouf'), ('38','Tissemsilt'), ('39','El Oued'), ('40','Khenchela'),
('41','Souk Ahras'), ('42','Tipaza'), ('43','Ain Defla'), ('44','Naama'),
('45','Ain Temouchent'), ('46','Ghardaia'), ('47','Relizane'), ('48','El Mghair'),
('49','El Meniaa'), ('50','Ouled Djellal'), ('51','Beni Abbes'), ('52','Timimoun'),
('53','Touggourt'), ('54','Djanet'), ('55','In Salah'), ('56','In Guezzam'),
('57','Bordj Baji Mokhtar'), ('58','Tindouf');

-- ============================================================
-- SEED DATA - Cities (sample)
-- ============================================================
INSERT INTO cities (wilaya_id, name_fr) VALUES
(16,'Alger Centre'), (16,'Bab El Oued'), (16,'Bir Mourad Rais'), (16,'Bouzareah'),
(31,'Oran'), (31,'Es Senia'), (31,'Ain El Turk'), (31,'Bir El Djir'),
(25,'Constantine'), (25,'El Khroub'), (25,'Hamma Bouziane'), (25,'Ain Smara'),
(23,'Annaba'), (23,'El Hadjar'), (23,'Berrahal'), (23,'El Bouni'),
(19,'Setif'), (19,'El Eulma'), (19,'Ain Oulmene'), (19,'Ain Azel');

-- ============================================================
-- SEED DATA - Users
-- ============================================================
INSERT INTO users (first_name, last_name, username, email, phone, password, role, status, department_id) VALUES
('Super','Admin','superadmin','admin@algerietelecom.dz','+213550000001','$2y$10$abcdefghijklmnopqrstuv','super_admin','active', NULL),
('Nassim', 'Ghanem', 'nassim.ghanem', 'nassim@algerietelecom.dz', '+213551111111','$2y$10$abcdefghijklmnopqrstuv','dept_admin','active',1),
('Samir', 'Bouabdallah', 'samir.bouabdallah', 'samir@algerietelecom.dz', '+213552222222','$2y$10$abcdefghijklmnopqrstuv','dept_admin','active',2),
('Youssef','Mansouri', 'youssef.mansouri', 'youssef@algerietelecom.dz', '+213553333333','$2y$10$abcdefghijklmnopqrstuv','dept_admin','active',3),
('Karim', 'Bensalem', 'karim.bensalem', 'karim@algerietelecom.dz', '+213554444444','$2y$10$abcdefghijklmnopqrstuv','dept_admin','active',4),
('Ahmed', 'Bensalem', 'ahmed.bensalem', 'ahmed@algerietelecom.dz', '+213561111111','$2y$10$abcdefghijklmnopqrstuv','internal_staff','active',1),
('Fatima', 'Zohra', 'fatima.zohra', 'fatima@algerietelecom.dz', '+213562222222','$2y$10$abcdefghijklmnopqrstuv','internal_staff','active',1),
('Mohamed','Khaldi', 'mohamed.khaldi', 'mohamed@algerietelecom.dz', '+213563333333','$2y$10$abcdefghijklmnopqrstuv','internal_staff','active',2),
('Entreprise','Test','entreprise_test','entreprise@test.dz','+213600000001','$2y$10$abcdefghijklmnopqrstuv','enterprise','active', NULL);

-- ============================================================
-- SEED DATA - Enterprise
-- ============================================================
INSERT INTO enterprises (user_id, nif, rc, rib, bank_name, wilaya_id, city_id, address, status) VALUES
(9, '18143110155117804300', '18B1431101551', '12345678901234567890','BNA', 16, 1,'Alger Centre','approved');

-- ============================================================
-- SEED DATA - Document Categories
-- ============================================================
INSERT INTO document_categories (name, department_id, max_file_size_mb) VALUES
('Procedures financieres', 1, 50),
('Factures et paiements', 1, 20),
('Contrats et marches', 2, 100),
('Cahiers des charges', 2, 50),
('Dossiers de paiement', 3, 50),
('Proces-verbaux de reception', 3, 30),
('Engagements budgetaires', 3, 50),
('Documents juridiques', 4, 50),
('Documents archives', 5, 100);

-- ============================================================
-- SEED DATA - Settings
-- ============================================================
INSERT INTO settings (`key`, `value`) VALUES
('site_name', 'AT-AMS - Algerie Telecom'),
('site_description', 'Systeme de Gestion et Archivage des Documents'),
('max_file_size', '52428800'),
('allowed_extensions', 'pdf,docx,xlsx,jpg,png,zip,rar'),
('session_timeout', '3600'),
('version', '1.0.0'),
('admin_email', 'admin@algerietelecom.dz');

-- ============================================================
-- SEED DATA - Sample Contracts
-- ============================================================
INSERT INTO contracts (contract_number, title, contract_type, enterprise_id, department_id, total_amount, start_date, end_date, status) VALUES
('53/2025', 'Contrat adhesion services telephoniques', 'Contrat d''adhesion a commandes', 1, 2, 5000000.00, '2025-01-01', '2025-12-31', 'active'),
('2024-1476', 'Marche fourniture equipment reseau', 'Marche a commandes', 1, 2, 12500000.00, '2024-06-01', '2025-05-31', 'active'),
('2024-0892', 'Contrat maintenance infrastrucure', 'Marche simple', 1, 1, 3500000.00, '2024-01-01', '2024-12-31', 'completed'),
('2025-0156', 'Marche tranches conditionnelles equipements', 'Marche a tranches conditionnelles', 1, 2, 20000000.00, '2025-03-01', '2026-02-28', 'active'),
('2025-0043', 'Contrat programme transformation numerique', 'Contrat programme', 1, 3, 50000000.00, '2025-01-01', '2027-12-31', 'active');

-- ============================================================
-- SEED DATA - Sample ODS
-- ============================================================
INSERT INTO ods (ods_number, contract_id, enterprise_id, bureau, amount, issue_date, status) VALUES
('ODS-2025-001', 1, 1, 'DAMP', 1500000.00, '2025-02-15', 'completed'),
('ODS-2025-002', 1, 1, 'DFC', 2500000.00, '2025-03-20', 'in_progress'),
('ODS-2024-156', 2, 1, 'DAMP', 5000000.00, '2024-07-10', 'completed'),
('ODS-2025-003', 3, 1, 'DOT', 1200000.00, '2025-01-25', 'pending');

-- ============================================================
-- SEED DATA - Sample Payment Dossiers
-- ============================================================
INSERT INTO payment_dossiers (dossier_number, contract_id, ods_id, enterprise_id, amount, status) VALUES
('PAIE-2025-001', 1, 1, 1, 1500000.00, 'pending'),
('PAIE-2025-002', 2, 3, 1, 5000000.00, 'in_progress'),
('PAIE-2024-089', 3, 3, 1, 3500000.00, 'paid');

-- Done!