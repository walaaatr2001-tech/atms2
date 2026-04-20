-- AT-AMS Seed Data

USE at_ams;

-- Insert departments
INSERT INTO departments (name, code, description) VALUES 
('Division Finances et Comptabilité', 'DFC', 'Gestion financière et comptable AT'),
('Division Achats Moyens Patrimoine', 'DAMP', 'Achats, moyens et patrimoine'),
('Directions Opérationnelles Télécom', 'DOT', 'Opérations régionales télécom'),
('Direction Juridique', 'DJ', 'Affaires juridiques et contractuelles'),
('Archives Centrales', 'ARCH', 'Archivage et gestion documentaire');

-- Insert 58 wilayas
INSERT INTO wilayas (code, name_fr) VALUES
('01','Adrar'),('02','Chlef'),('03','Laghouat'),('04','Oum El Bouaghi'),
('05','Batna'),('06','Béjaïa'),('07','Biskra'),('08','Béchar'),
('09','Blida'),('10','Bouira'),('11','Tamanrasset'),('12','Tébessa'),
('13','Tlemcen'),('14','Tiaret'),('15','Tizi Ouzou'),('16','Alger'),
('17','Djelfa'),('18','Jijel'),('19','Sétif'),('20','Saïda'),
('21','Skikda'),('22','Sidi Bel Abbès'),('23','Annaba'),('24','Guelma'),
('25','Constantine'),('26','Médéa'),('27','Mostaganem'),('28','Msila'),
('29','Mascara'),('30','Ouargla'),('31','Oran'),('32','El Bayadh'),
('33','Illizi'),('34','Bordj Bou Arréridj'),('35','Boumerdès'),('36','El Tarf'),
('37','Tindouf'),('38','Tissemsilt'),('39','El Oued'),('40','Khenchela'),
('41','Souk Ahras'),('42','Tipaza'),('43','Aïn Defla'),('44','Naâma'),
('45','Aïn Témouchent'),('46','Ghardaïa'),('47','Relizane'),('48','El Mghair'),
('49','El Meniaa'),('50','Ouled Djellal'),('51','Béni Abbès'),('52','Timimoun'),
('53','Touggourt'),('54','Djanet'),('55','In Salah'),('56','In Guezzam'),
('57','Bordj Baji Mokhtar'),('58','Tindouf');

-- Insert sample cities
INSERT INTO cities (wilaya_id, name_fr) VALUES
(16,'Alger Centre'),(16,'Bab El Oued'),(16,'Bir Mourad Raïs'),(16,'Bouzareah'),
(16,'Chéraga'),(16,'Dar El Beïda'),(16,'Hussein Dey'),(16,'Kouba'),
(1,'Adrar'),(1,'Timimoun'),(1,'In Salah'),(31,'Oran'),(31,'Es Senia'),
(31,'Aïn El Turk'),(31,'Bir El Djir'),(23,'Annaba'),(23,'El Hadjar'),
(23,'Bône'),(25,'Constantine'),(25,'Didouche Mourad'),(5,'Batna'),
(5,'Biskra'),(19,'Sétif'),(19,'El Eulma'),(6,'Béjaïa'),
(15,'Tizi Ouzou'),(15,'Bejaia'),(22,'Sidi Bel Abbès'),(22,'Mascara'),
(30,'Ouargla'),(30,'Hassi Messaoud'),(29,'Mascara'),(18,'Jijel'),
(13,'Tlemcen'),(9,'Blida'),(10,'Bouira'),(27,'Mostaganem'),
(28,'Msila'),(17,'Djelfa'),(21,'Skikda'),(11,'Tamanrasset');

-- Insert banks
INSERT INTO banks (name, code) VALUES
('Banque Nationale d''Algérie','BNA'),('Banque Extérieure d''Algérie','BEA'),
('Crédit Populaire d''Algérie','CPA'),('Banque de l''Agriculture et du Développement Rural','BADR'),
('Banque de Développement Local','BDL'),('CNEP-Banque','CNEP'),
('BICI-DZ','BICI'),('Société Générale Algérie','SGA'),
('BNP Paribas El Djazaïr','BNP'),('Natixis Algérie','NAT'),
('Arab Bank Algeria','ARB'),('Gulf Bank Algeria','GBA'),
('Al Baraka Bank Algeria','ABK'),('Housing Bank Algeria','HBK'),
('FRANSABANK El Djazaïr','FRN'),('Citibank Algeria','CIT'),
('ABC Bank Algeria','ABC'),('Trust Bank Algeria','TRB'),
('Algérie Poste (CCP)','CCP'),('Autre','AUTRE');

-- Insert document categories
INSERT INTO document_categories (name, department_id, max_file_size_mb) VALUES
('Procédures financières - Comptabilisation',1,50),
('Factures et états de paiement',1,20),
('Contrats marchés et avenants',2,100),
('Cahiers des charges',2,50),
('Dossiers de paiement DOT',3,50),
('Procès-verbaux de réception',3,30),
('Engagements et engagements budgétaires',3,50),
('Documents juridiques',4,50),
('Contrats de travail',4,20),
('Procès-verbaux de passation',5,30),
('Rapports d''intervention technique',3,100),
('Documents archivés',5,100);

-- Insert Super Admin (username: superadmin, password: admin123 - plain text)
INSERT INTO users (first_name, last_name, username, email, phone, password, role, status) 
VALUES ('Super','Admin','superadmin','admin@algerietelecom.dz','+213550000001','admin123','super_admin','active');

-- Insert department admins (password: admin123)
INSERT INTO users (first_name, last_name, username, email, phone, password, role, status, department_id) VALUES
('Nassim','Ghanem','nassim.ghanem','nassim@algerietelecom.dz','+213551234567','admin123','dept_admin','active',1),
('Samir','Bouabdallah','samir.bouabdallah','samir@algerietelecom.dz','+213552345678','admin123','dept_admin','active',2),
('Youssef','Mansouri','youssef.mansouri','youssef@algerietelecom.dz','+213553456789','admin123','dept_admin','active',3),
('Karim','Bensalem','karim.bensalem','karim@algerietelecom.dz','+213554567890','admin123','dept_admin','active',4);

-- Insert internal staff (password: staff123)
INSERT INTO users (first_name, last_name, username, email, phone, password, role, status, department_id) VALUES
('Ahmed','Bensalem','ahmed.bensalem','ahmed@algerietelecom.dz','+213561234567','staff123','internal_staff','active',1),
('Fatima','Zohra','fatima.zohra','fatima@algerietelecom.dz','+213562345678','staff123','internal_staff','active',1),
('Mohamed','Khaldi','mohamed.khaldi','mohamed@algerietelecom.dz','+213563456789','staff123','internal_staff','active',2),
('Sara','Amrani','sara.amrani','sara@algerietelecom.dz','+213564567890','staff123','internal_staff','active',3),
('Rachid','Mouhcine','rachid.mouhcine','rachid@algerietelecom.dz','+213565678901','staff123','internal_staff','active',4),
('Nadia','Bouchama','nadia.bouchama','nadia@algerietelecom.dz','+213566789012','staff123','internal_staff','active',5);

-- Insert settings
INSERT INTO settings (`key`, `value`) VALUES
('site_name','AT-AMS - Algérie Télécom'),
('site_description','Système de Gestion et d''Archivage des Documents'),
('max_file_size','52428800'),
('allowed_extensions','pdf,docx,xlsx,jpg,png,zip,rar'),
('session_timeout','3600'),
('version','1.0.0');

-- Insert sample enterprise user (for testing)
INSERT INTO users (first_name, last_name, username, email, phone, password, role, status) 
VALUES ('Entreprise','Test','entreprise_test','entreprise@test.dz','+213600000001','entreprise123','enterprise','active');

INSERT INTO enterprises (user_id, nif, rc, rib, bank_name, wilaya_id, city_id, address, status) 
VALUES (13,'12345678901','RC123456','1234567890123456789012','BNA',16,1,'Alger, Algérie','approved');