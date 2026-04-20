ALTER TABLE documents 
ADD COLUMN extracted_json TEXT NULL COMMENT 'AI extracted data in JSON',
ADD COLUMN ai_processed TINYINT(1) DEFAULT 0 COMMENT '1 = AI already processed';