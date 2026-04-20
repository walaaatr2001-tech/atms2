-- ============================================================
-- AT-AMS COMPLETE DATABASE DESIGN
-- Algeria Telecom Asset Management System
-- Designed for Security, Search & Filtering
-- ============================================================

-- ============================================================
-- SECTION 1: CORE REFERENCE TABLES (No Dependencies)
-- ============================================================

-- 1.1) Wilayas - Algerian Administrative Regions
CREATE TABLE wilayas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(2) NOT NULL UNIQUE,
    name_fr VARCHAR(100) NOT NULL,
    name_ar VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1.2) Cities - Cities within Wilayas  
CREATE TABLE cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wilaya_id INT NOT NULL,
    name_fr VARCHAR(150) NOT NULL,
    name_ar VARCHAR(150) DEFAULT NULL,
    FOREIGN KEY (wilaya_id) REFERENCES wilayas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1.3) Banks - Algerian Banks
CREATE TABLE banks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1.4) Departments - AT Internal Departments
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    parent_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1.5) Document Categories
CREATE TABLE document_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    department_id INT DEFAULT NULL,
    max_file_size_mb INT NOT NULL DEFAULT 50,
    allowed_extensions VARCHAR(255) DEFAULT 'pdf,docx,xlsx,jpg,png',
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1.6) Contract Types (from AT PDFs)
CREATE TABLE contract_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1.7) Bureaus (DRT, SDT, etc.)
CREATE TABLE bureaus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SECTION 2: USER MANAGEMENT (Security & Access Control)
-- ============================================================

-- 2.1) Users - All System Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin','dept_admin','internal_staff','enterprise') NOT NULL DEFAULT 'enterprise',
    status ENUM('active','pending','suspended','rejected') NOT NULL DEFAULT 'active',
    department_id INT DEFAULT NULL,
    bureau VARCHAR(100) DEFAULT NULL,
    last_login TIMESTAMP NULL,
    login_count INT DEFAULT 0,
    password_changed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.2) Password Reset Tokens (Security)
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2.3) Login Sessions (Security)
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SECTION 3: ENTERPRISES (Companies/Contractors)
-- ============================================================

CREATE TABLE enterprises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    -- Identifiers
    rc VARCHAR(50) DEFAULT NULL,        -- Registre de Commerce
    nif VARCHAR(50) DEFAULT NULL,        -- Numero d'Identification Fiscale  
    art_social VARCHAR(50) DEFAULT NULL, -- Article d'Imposition
    rib VARCHAR(27) DEFAULT NULL,       -- Relevé d'Identité Bancaire
    -- Bank Info
    bank_id INT DEFAULT NULL,
    bank_other VARCHAR(100) DEFAULT NULL,
    -- Location
    wilaya_id INT DEFAULT NULL,
    city_id INT DEFAULT NULL,
    address TEXT,
    -- Contact
    contact_person VARCHAR(100) DEFAULT NULL,
    contact_phone VARCHAR(20) DEFAULT NULL,
    contact_email VARCHAR(150) DEFAULT NULL,
    -- Status
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_by INT DEFAULT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT,
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign Keys
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bank_id) REFERENCES banks(id) ON DELETE SET NULL,
    FOREIGN KEY (wilaya_id) REFERENCES wilayas(id) ON DELETE SET NULL,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    -- Indexes for Search
    INDEX idx_rc (rc),
    INDEX idx_nif (nif),
    INDEX idx_status (status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SECTION 4: CONTRACTS (Core Business Entity)
-- ============================================================

CREATE TABLE contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_number VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    contract_type_id INT DEFAULT NULL,
    enterprise_id INT DEFAULT NULL,
    department_id INT DEFAULT NULL,
    -- Amount & Dates
    total_amount DECIMAL(15,2) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    -- Additional Info
    objet TEXT,
    lieu_execution VARCHAR(255) DEFAULT NULL,
    caution_bancaire DECIMAL(15,2) DEFAULT NULL,
    assurance VARCHAR(255) DEFAULT NULL,
    -- Status
    status ENUM('draft','active','completed','suspended','cancelled') NOT NULL DEFAULT 'draft',
    -- Audit
    created_by INT DEFAULT NULL,
    validated_by INT DEFAULT NULL,
    validated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign Keys
    FOREIGN KEY (contract_type_id) REFERENCES contract_types(id) ON DELETE SET NULL,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL,
    -- Indexes for Search & Filter
    INDEX idx_contract_number (contract_number),
    INDEX idx_enterprise (enterprise_id),
    INDEX idx_department (department_id),
    INDEX idx_type (contract_type_id),
    INDEX idx_status (status),
    INDEX idx_year (start_date),
    INDEX idx_amount (total_amount)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SECTION 5: ORDRES DE SERVICE (ODS)
-- ============================================================

CREATE TABLE ods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ods_number VARCHAR(100) NOT NULL,
    contract_id INT DEFAULT NULL,
    enterprise_id INT DEFAULT NULL,
    bureau_id INT DEFAULT NULL,
    -- Details
    lot_number VARCHAR(50) DEFAULT NULL,
    amount DECIMAL(15,2) DEFAULT NULL,
    issue_date DATE DEFAULT NULL,
    description TEXT,
    -- Status
    status ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    completed_by INT DEFAULT NULL,
    completed_at TIMESTAMP NULL,
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign Keys
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE SET NULL,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE SET NULL,
    FOREIGN KEY (bureau_id) REFERENCES bureaus(id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL,
    -- Indexes for Search
    INDEX idx_ods_number (ods_number),
    INDEX idx_contract (contract_id),
    INDEX idx_enterprise (enterprise_id),
    INDEX idx_bureau (bureau_id),
    INDEX idx_status (status),
    INDEX idx_issue_date (issue_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SECTION 6: PAYMENT DOSSIERS
-- ============================================================

CREATE TABLE payment_dossiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dossier_number VARCHAR(100) NOT NULL,
    contract_id INT DEFAULT NULL,
    ods_id INT DEFAULT NULL,
    enterprise_id INT DEFAULT NULL,
    -- Payment Details
    amount DECIMAL(15,2) DEFAULT NULL,
    amount_letters VARCHAR(255) DEFAULT NULL,
    payment_mode ENUM('virement','cheque','effet') DEFAULT NULL,
    reference_paiement VARCHAR(100) DEFAULT NULL,
    date_paiement DATE DEFAULT NULL,
    -- Status
    status ENUM('pending','in_progress','paid','rejected') NOT NULL DEFAULT 'pending',
    processed_by INT DEFAULT NULL,
    processed_at TIMESTAMP NULL,
    rejection_reason TEXT,
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign Keys
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE SET NULL,
    FOREIGN KEY (ods_id) REFERENCES ods(id) ON DELETE SET NULL,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    -- Indexes
    INDEX idx_dossier_number (dossier_number),
    INDEX idx_contract (contract_id),
    INDEX idx_ods (ods_id),
    INDEX idx_enterprise (enterprise_id),
    INDEX idx_status (status),
    INDEX idx_date_paiement (date_paiement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payment Dossier Documents
CREATE TABLE payment_dossier_docs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dossier_id INT NOT NULL,
    doc_type ENUM('ods_copy','pv_reception','facture','attachement','bon_commande','releve') NOT NULL,
    document_id INT DEFAULT NULL,
    is_submitted TINYINT(1) DEFAULT 0,
    submitted_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (dossier_id) REFERENCES payment_dossiers(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL,
    INDEX idx_dossier (dossier_id),
    INDEX idx_doc_type (doc_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SECTION 7: DOCUMENTS
-- ============================================================

CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) DEFAULT NULL,
    file_type ENUM('pdf','docx','xlsx','jpg','png','zip','rar') NOT NULL,
    file_size INT NOT NULL DEFAULT 0,
    -- Category & Department
    category_id INT DEFAULT NULL,
    department_id INT NOT NULL,
    -- Upload Info
    uploaded_by INT NOT NULL,
    enterprise_id INT DEFAULT NULL,
    -- Links to Business Entities
    contract_id INT DEFAULT NULL,
    ods_id INT DEFAULT NULL,
    payment_dossier_id INT DEFAULT NULL,
    -- AI Extraction
    extracted_json TEXT,
    ai_processed TINYINT(1) DEFAULT 0,
    ai_error TEXT,
    -- Versioning
    version INT NOT NULL DEFAULT 1,
    parent_id INT DEFAULT NULL,
    -- Status
    status ENUM('draft','submitted','validated','rejected','archived') NOT NULL DEFAULT 'submitted',
    validated_by INT DEFAULT NULL,
    validated_at TIMESTAMP NULL,
    rejection_reason TEXT,
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Foreign Keys
    FOREIGN KEY (category_id) REFERENCES document_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE SET NULL,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE SET NULL,
    FOREIGN KEY (ods_id) REFERENCES ods(id) ON DELETE SET NULL,
    FOREIGN KEY (payment_dossier_id) REFERENCES payment_dossiers(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_id) REFERENCES documents(id) ON DELETE SET NULL,
    FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL,
    -- Indexes
    INDEX idx_reference (reference_number),
    INDEX idx_category (category_id),
    INDEX idx_department (department_id),
    INDEX idx_uploader (uploaded_by),
    INDEX idx_contract (contract_id),
    INDEX idx_ods (ods_id),
    INDEX idx_payment_dossier (payment_dossier_id),
    INDEX idx_status (status),
    INDEX idx_ai_processed (ai_processed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Document Versions
CREATE TABLE document_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    version_number INT NOT NULL DEFAULT 1,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL DEFAULT 0,
    changes TEXT,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_document (document_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SECTION 8: AUDIT & LOGGING (Security)
-- ============================================================

CREATE TABLE audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INT DEFAULT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SECTION 9: NOTIFICATIONS
-- ============================================================

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) DEFAULT NULL,
    title VARCHAR(255) DEFAULT NULL,
    message TEXT,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SECTION 10: SETTINGS
-- ============================================================

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255),
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Contract Types
INSERT INTO contract_types (name, code, description) VALUES
('Contrat aderhsion a commandes', 'CAD', 'Contrat aderhsion a commandes'),
('Marche a commandes', 'MAC', 'Marche a commandes'),
('Marche simple', 'MSX', 'Marche simple'),
('Marche a tranches conditionnelles', 'MTC', 'Marche a tranches conditionnelles'),
('Contrat programme', 'CPG', 'Contrat programme'),
('Coordination de commandes', 'CDE', 'Coordination de commandes');

-- Bureaus
INSERT INTO bureaus (code, name, description) VALUES
('DFC', 'Division Finances et Comptabilite', 'Gestion financiere et comptable'),
('DAMP', 'Division Achats Moyens Patrimoine', 'Achats et patrimoine'),
('DOT', 'Directions Operationnelles Telecom', 'Operations regionales'),
('DJ', 'Direction Juridique', 'Affaires juridiques'),
('ARCH', 'Archives Centrales', 'Archivage documentaire'),
('DRT', 'Direction Regionale Telecom', 'Direction regionale'),
('SDT', 'Service Departemental Telecom', 'Service departemental');

-- Departments
INSERT INTO departments (name, code, description) VALUES
('Division Finances et Comptabilite', 'DFC', 'Gestion financiere et comptable AT'),
('Division Achats Moyens Patrimoine', 'DAMP', 'Achats, moyens et patrimoine'),
('Directions Operationnelles Telecom', 'DOT', 'Operations regionales telecom'),
('Direction Juridique', 'DJ', 'Affaires juridiques et contractuelles'),
('Archives Centrales', 'ARCH', 'Archivage et gestion documentaire');

-- Banks
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

-- Wilayas
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
('45','Ain Temouchent'), ('46','Ghardaia'), ('47','Relizane');

-- Cities
INSERT INTO cities (wilaya_id, name_fr) VALUES
(16,'Alger Centre'), (16,'Bab El Oued'), (16,'Bir Mourad Rais'), (16,'Bouzareah'),
(31,'Oran'), (31,'Es Senia'), (31,'Ain El Turk'), (31,'Bir El Djir'),
(25,'Constantine'), (25,'El Khroub'), (25,'Hamma Bouziane'), (25,'Ain Smara'),
(23,'Annaba'), (23,'El Hadjar'), (23,'Berrahal'), (23,'El Bouni');

-- Document Categories
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

-- Users (Default Password: admin123 - hashed)
INSERT INTO users (first_name, last_name, username, email, phone, password_hash, role, status, department_id) VALUES
('Super','Admin','superadmin','admin@algerietelecom.dz','+213550000001','$2y$10$92IXUNpkjO0rOQ5byMm.Ye4oRoEaql6Jd/WsW8mJPqUxbI5fRXJ5Nm','super_admin','active', NULL),
('Nassim', 'Ghanem', 'nassim.ghanem', 'nassim@algerietelecom.dz', '+213551111111','$2y$10$92IXUNpkjO0rOQ5byMm.Ye4oRoEaql6Jd/WsW8mJPqUxbI5fRXJ5Nm','dept_admin','active',1),
('Samir', 'Bouabdallah', 'samir.bouabdallah', 'samir@algerietelecom.dz', '+213552222222','$2y$10$92IXUNpkjO0rOQ5byMm.Ye4oRoEaql6Jd/WsW8mJPqUxbI5fRXJ5Nm','dept_admin','active',2),
('Youssef','Mansouri', 'youssef.mansouri', 'youssef@algerietelecom.dz', '+213553333333','$2y$10$92IXUNpkjO0rOQ5byMm.Ye4oRoEaql6Jd/WsW8mJPqUxbI5fRXJ5Nm','dept_admin','active',3),
('Karim', 'Bensalem', 'karim.bensalem', 'karim@algerietelecom.dz', '+213554444444','$2y$10$92IXUNpkjO0rOQ5byMm.Ye4oRoEaql6Jd/WsW8mJPqUxbI5fRXJ5Nm','dept_admin','active',4),
('Ahmed', 'Bensalem', 'ahmed.bensalem', 'ahmed@algerietelecom.dz', '+213561111111','$2y$10$92IXUNpkjO0rOQ5byMm.Ye4oRoEaql6Jd/WsW8mJPqUxbI5fRXJ5Nm','internal_staff','active',1),
('Fatima', 'Zohra', 'fatima.zohra', 'fatima@algerietelecom.dz', '+213562222222','$2y$10$92IXUNpkjO0rOQ5byMm.Ye4oRoEaql6Jd/WsW8mJPqUxbI5fRXJ5Nm','internal_staff','active',1),
('Entreprise','Test','entreprise_test','entreprise@test.dz','+213600000001','$2y$10$92IXUNpkjO0rOQ5byMm.Ye4oRoEaql6Jd/WsW8mJPqUxbI5fRXJ5Nm','enterprise','active', NULL);

-- Enterprise
INSERT INTO enterprises (user_id, nif, rc, rib, bank_name, wilaya_id, city_id, address, status, approved_by, approved_at) VALUES
(8, '18143110155117804300', '18B1431101551', '12345678901234567890', 1, 16, 1, 'Alger Centre, Algerie', 'approved', 1, NOW());

-- Settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'AT-AMS - Algerie Telecom', 'System name'),
('site_description', 'Systeme de Gestion et Archivage des Documents', 'System description'),
('max_file_size', '52428800', 'Max upload size in bytes'),
('allowed_extensions', 'pdf,docx,xlsx,jpg,png,zip,rar', 'Allowed file extensions'),
('session_timeout', '3600', 'Session timeout in seconds'),
('version', '1.0.0', 'System version'),
('admin_email', 'admin@algerietelecom.dz', 'Admin email');

-- Sample Contracts
INSERT INTO contracts (contract_number, title, contract_type_id, enterprise_id, department_id, total_amount, start_date, end_date, status, validated_by, validated_at) VALUES
('53/2025', 'Contrat adhesion services telephoniques', 1, 1, 2, 5000000.00, '2025-01-01', '2025-12-31', 'active', 2, NOW()),
('2024-1476', 'Marche fourniture equipement reseau', 2, 1, 2, 12500000.00, '2024-06-01', '2025-05-31', 'active', 2, NOW()),
('2024-0892', 'Contrat maintenance infrastructure', 3, 1, 1, 3500000.00, '2024-01-01', '2024-12-31', 'completed', 2, NOW()),
('2025-0156', 'Marche tranches conditionnelles equipements', 4, 1, 2, 20000000.00, '2025-03-01', '2026-02-28', 'active', 2, NOW()),
('2025-0043', 'Contrat programme transformation numerique', 5, 1, 3, 50000000.00, '2025-01-01', '2027-12-31', 'active', 2, NOW());

-- Sample ODS
INSERT INTO ods (ods_number, contract_id, enterprise_id, bureau_id, amount, issue_date, status) VALUES
('ODS-2025-001', 1, 1, 2, 1500000.00, '2025-02-15', 'completed'),
('ODS-2025-002', 1, 1, 1, 2500000.00, '2025-03-20', 'in_progress'),
('ODS-2024-156', 2, 1, 2, 5000000.00, '2024-07-10', 'completed'),
('ODS-2025-003', 3, 1, 3, 1200000.00, '2025-01-25', 'pending');

-- Sample Payment Dossiers
INSERT INTO payment_dossiers (dossier_number, contract_id, ods_id, enterprise_id, amount, status, processed_by, processed_at) VALUES
('PAIE-2025-001', 1, 1, 1, 1500000.00, 'pending', NULL, NULL),
('PAIE-2025-002', 2, 3, 1, 5000000.00, 'in_progress', 2, NOW()),
('PAIE-2024-089', 3, 3, 1, 3500000.00, 'paid', 2, NOW());