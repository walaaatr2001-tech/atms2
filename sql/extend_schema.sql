-- AT-AMS Schema Extensions
-- Add new tables for contracts, ODS, and payment dossiers

-- Create contracts table
CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_number VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
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
);

-- Create ods table
CREATE TABLE IF NOT EXISTS ods (
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
);

-- Create payment_dossiers table
CREATE TABLE IF NOT EXISTS payment_dossiers (
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
);

-- Create payment_dossier_docs table
CREATE TABLE IF NOT EXISTS payment_dossier_docs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dossier_id INT NOT NULL,
    doc_type ENUM('ods_copy','pv_reception','facture','attachement','bon_commande','releve') NOT NULL,
    document_id INT DEFAULT NULL,
    is_submitted TINYINT(1) DEFAULT 0,
    submitted_at TIMESTAMP NULL,
    FOREIGN KEY (dossier_id) REFERENCES payment_dossiers(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL
);

-- Add new columns to documents table
ALTER TABLE documents
    ADD COLUMN contract_number VARCHAR(100) DEFAULT NULL,
    ADD COLUMN contract_id INT DEFAULT NULL,
    ADD COLUMN ods_id INT DEFAULT NULL,
    ADD COLUMN payment_dossier_id INT DEFAULT NULL;

-- Add bureau column to users table
ALTER TABLE users ADD COLUMN bureau VARCHAR(100) DEFAULT NULL;

-- Add indexes for new tables
CREATE INDEX idx_contracts_enterprise ON contracts(enterprise_id);
CREATE INDEX idx_contracts_department ON contracts(department_id);
CREATE INDEX idx_contracts_status ON contracts(status);
CREATE INDEX idx_ods_contract ON ods(contract_id);
CREATE INDEX idx_ods_enterprise ON ods(enterprise_id);
CREATE INDEX idx_ods_status ON ods(status);
CREATE INDEX idx_payment_contract ON payment_dossiers(contract_id);
CREATE INDEX idx_payment_ods ON payment_dossiers(ods_id);
CREATE INDEX idx_payment_enterprise ON payment_dossiers(enterprise_id);
CREATE INDEX idx_payment_status ON payment_dossiers(status);
CREATE INDEX idx_documents_contract ON documents(contract_id);
CREATE INDEX idx_documents_ods ON documents(ods_id);
CREATE INDEX idx_documents_payment_dossier ON documents(payment_dossier_id);