-- AT-AMS Schema Migration
-- Run this file to update your existing database to use new column names
-- Import this file in phpMyAdmin

USE at_ams;

-- Rename contracts table columns
ALTER TABLE contracts CHANGE COLUMN numero_contrat contract_number VARCHAR(100) NOT NULL;
ALTER TABLE contracts CHANGE COLUMN titre title VARCHAR(255) NOT NULL;
ALTER TABLE contracts CHANGE COLUMN entreprise_id enterprise_id INT DEFAULT NULL;
ALTER TABLE contracts CHANGE COLUMN status status ENUM('active','completed','suspended','cancelled') DEFAULT 'active';

-- Add new columns to contracts if they don't exist
ALTER TABLE contracts ADD COLUMN IF NOT EXISTS contract_type VARCHAR(100) DEFAULT NULL AFTER title;
ALTER TABLE contracts ADD COLUMN IF NOT EXISTS created_by INT DEFAULT NULL AFTER status;

-- Rename ods table columns
ALTER TABLE ods CHANGE COLUMN numero_ods ods_number VARCHAR(100) NOT NULL;
ALTER TABLE ods CHANGE COLUMN contrat_id contract_id INT DEFAULT NULL;
ALTER TABLE ods CHANGE COLUMN montant amount DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE ods CHANGE COLUMN date_creation issue_date DATE DEFAULT NULL;
ALTER TABLE ods CHANGE COLUMN status status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending';

-- Rename payment_dossiers columns
ALTER TABLE payment_dossiers CHANGE COLUMN numero_dossier dossier_number VARCHAR(100) NOT NULL;
ALTER TABLE payment_dossiers CHANGE COLUMN contrat_id contract_id INT DEFAULT NULL;
ALTER TABLE payment_dossiers CHANGE COLUMN ods_id ods_id INT DEFAULT NULL;
ALTER TABLE payment_dossiers CHANGE COLUMN entreprise_id enterprise_id INT DEFAULT NULL;
ALTER TABLE payment_dossiers CHANGE COLUMN status status ENUM('pending','in_progress','paid','rejected') DEFAULT 'pending';

-- Add enterprise RC lookup if needed
ALTER TABLE enterprises ADD COLUMN IF NOT EXISTS rc VARCHAR(50) DEFAULT NULL AFTER nif;
ALTER TABLE enterprises ADD COLUMN IF NOT EXISTS nif VARCHAR(50) DEFAULT NULL AFTER rc;

-- Add new document columns
ALTER TABLE documents ADD COLUMN IF NOT EXISTS contract_number VARCHAR(100) DEFAULT NULL;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS contract_id INT DEFAULT NULL;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS ods_id INT DEFAULT NULL;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS payment_dossier_id INT DEFAULT NULL;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS extracted_json TEXT DEFAULT NULL;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS ai_processed TINYINT(1) DEFAULT 0;