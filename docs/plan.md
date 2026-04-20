# AT-AMS — Complete Implementation Plan
## Based on reading all PDFs, code files, and project documentation

---

## 📋 What the PDFs Define (Business Requirements)

The 4 real Algeria Telecom documents define the core business logic:

### 1. GESTION ET SUIVI DES ENGAGEMENTS (ODS Management)
**Key entities from PDF:**
- **ODS (Ordre de Service)** - Service order document, core tracking unit per bureau/division
- Each ODS has: number, date, enterprise, amount, lot numbers, attached documents
- ODS links to: Contract → Bureau/Division → Payment Dossier
- **Bureaux** (regional telecom offices): DRT/SDT, DRT/AGL, DRT/OST, etc.
- Payment tracking per bureau with amounts, dates, status

### 2. NOUVELLE PROCEDURE PASSATION DE MARCHE (Procurement)
**Workflow:**
1. **Appel d'offres** (Tender) - Publication, reception offers
2. **Evaluation** - Commission de penilaian, technical/financial analysis
3. **Attribution** - Contract signature (Contrat cadre + CCAP/Cahier des charges)
4. **Execution** - ODS creation, work completion, PV reception
5. **Paiement** - Payment demande, financial settlement

### 3. PROCEDURE COMPTABILISATION PROJETS PROPRES
- Project accounting for AT's internal projects
- Budget tracking, commitments, actual spending

### 4. Dossier de Paiement AT Corporate (Real Example)
**Required documents for payment:**
- ODS copy (service order)
- PV de reception provisoire (provisional acceptance report)
- Facture (invoice)
- Attachement de travaux (work attachment)
- Bon de commande (purchase order)
- Releve des travaux (work summary)

---

## 🔴 What Currently EXISTS (Already Built)

### Authentication & Users
| File | Status |
|------|--------|
| `pages/auth/login.php` | ✅ Done |
| `pages/auth/register.php` | ✅ Done (4-step wizard) |
| `pages/auth/register_action.php` | ✅ Done |
| `pages/auth/get_cities.php` | ✅ Done |
| `pages/auth/login_action.php` | ✅ Done |
| `includes/auth.php` | ✅ Done |

### Admin Panel
| File | Status |
|------|--------|
| `pages/admin/dashboard.php` | ✅ Done (premium dark UI) |
| `pages/admin/users.php` | ✅ Done |
| `pages/admin/enterprises-pending.php` | ✅ Done |
| `pages/admin/view_contract.php` | ✅ Stub (empty) |

### Documents Module
| File | Status |
|------|--------|
| `pages/documents/upload.php` | ✅ Done (with AI toggle) |
| `pages/documents/list.php` | ✅ Done (basic table) |
| `pages/documents/detail.php` | ✅ Done |
| `pages/documents/versions.php` | ✅ Done |
| `pages/documents/extracted_view.php` | ✅ Basic JSON dump |
| `pages/documents/process_ai.php` | ✅ Done |

### Dashboard & Enterprise
| File | Status |
|------|--------|
| `pages/dashboard/index.php` | ✅ Redirect stub |
| `pages/dashboard/enterprise/index.php` | ✅ Done |

### Core Includes & Config
| File | Status |
|------|--------|
| `includes/ai_helper.php` | ✅ Done (Gemini 1.5 Flash) |
| `includes/auth.php` | ✅ Done |
| `includes/header.php` | ✅ Done (sidebar with menu) |
| `includes/footer.php` | ✅ Done |
| `config/config.php` | ✅ Done |
| `config/database.php` | ✅ Done |
| `controllers/upload_controller.php` | ✅ Done |

### Database
| File | Status |
|------|--------|
| `sql/schema.sql` | ✅ Done (12 tables) |
| `sql/seed.sql` | ✅ Done |
| `sql/update_ai.sql` | ✅ Done (extracted_json, ai_processed) |

---

## 🔴 What is MISSING and MUST BE BUILT

### PRIORITY 1: Core Business Features (ODS & Contracts)

#### 1. Search Page with Contract/ODS Filter
**File:** `pages/documents/search.php`

From sidebar - links exist but file missing. Must have:
- Search by contract number (main feature)
- Filter shows ALL docs linked to contract (ODS, PV, factures, attachments)
- Real-time AJAX search
- File type badges (PDF, ODS, etc.)
- Sidebar filters: department, status, date range

> **User Requirement:** "filter document by contract number that shows ODS files"

---

#### 2. Contracts Module (NEW)
**Files:** `pages/documents/contracts.php`, `pages/documents/contract_detail.php`

From PDFs - contracts are central:
- List all contracts: number, enterprise, total amount, status
- Link to all ODS under each contract
- Link to payment dossiers
- Auto-detect from uploaded PDF via AI extraction

---

#### 3. ODS Module (Ordres de Service)
**File:** `pages/documents/ods.php`, `pages/documents/ods_detail.php`

Core from PDFs:
- ODS Number format: `ODS N°XXX/DRT/SDT/YYYY`
- Links to: contract, enterprise, bureau/division
- Has: lot numbers, amount, issue date, status
- Documents: PV reception, facture, attachement

---

#### 4. Payment Dossiers Module
**File:** `pages/documents/payments.php`

From "Dossier de paiement AT Corporate" PDF:
- Groups required docs: ODS, PV, Facture, Attachement, BC
- Shows: enterprise, contract, ODS number, amount, status
- Checklist view: what's uploaded/missing

---

### PRIORITY 2: Admin & AI Features

#### 5. AI Admin Panel
**File:** `pages/admin/ai_panel.php`

Current: AI extracts to JSON in `extracted_json` column. Missing:
- Dashboard showing all docs with AI status
- Batch re-process (multiple docs at once)
- Search by extracted fields (contract_number, enterprise, amount)
- Quota indicator for Gemini API
- Manual trigger per document

---

#### 6. Settings Page
**File:** `pages/admin/settings.php`

Sidebar links but file missing:
- AI API key management
- Allowed file types, max sizes
- Site name/description
- Email notifications toggle

---

#### 7. Departments Management
**File:** `pages/admin/departments.php`

- CRUD for departments
- Assign users to departments
- From schema: DFC, DAMP, DOT, DJ, ARCH

---

### PRIORITY 3: Supporting Features

#### 8. Archives Central Module
**File:** `pages/documents/archive.php`

- Browse archived by year/department
- Mass archive action
- Statistics

---

#### 9. Reports Module
**File:** `pages/documents/reports.php`

- Stats per department, enterprise, month
- Chart.js integration
- PDF/Excel export

---

#### 10. User Profile Page
**File:** `pages/profile/index.php`

- Edit profile
- Change password
- View own documents

---

#### 11. Notifications Page
**File:** `pages/notifications.php`

- List all notifications
- Mark as read
- Link to related document

---

#### 12. Document Index Redirect
**File:** `pages/documents/index.php`

- Currently missing - sidebar links to it
- Should redirect to list.php

---

## 🗄️ Database Changes Needed

The current 12-table schema needs these additions for ODS/Contract workflow:

```sql
-- TABLE: contracts (new)
CREATE TABLE contracts (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    contract_number  VARCHAR(100) NOT NULL UNIQUE,
    title            VARCHAR(255) NOT NULL,
    enterprise_id    INT DEFAULT NULL,
    department_id    INT DEFAULT NULL,
    total_amount     DECIMAL(15,2) DEFAULT NULL,
    start_date       DATE DEFAULT NULL,
    end_date         DATE DEFAULT NULL,
    status           ENUM('active','completed','suspended','cancelled') DEFAULT 'active',
    created_by       INT DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by)    REFERENCES users(id) ON DELETE SET NULL
);

-- TABLE: ods (Ordres de Service) - NEW
CREATE TABLE ods (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    ods_number      VARCHAR(100) NOT NULL,
    contract_id     INT DEFAULT NULL,
    enterprise_id   INT DEFAULT NULL,
    bureau          VARCHAR(100) DEFAULT NULL,
    lot_number      VARCHAR(50) DEFAULT NULL,
    amount          DECIMAL(15,2) DEFAULT NULL,
    issue_date      DATE DEFAULT NULL,
    description     TEXT DEFAULT NULL,
    status          ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    document_id     INT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id)  REFERENCES contracts(id) ON DELETE SET NULL,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE SET NULL,
    FOREIGN KEY (document_id)  REFERENCES documents(id) ON DELETE SET NULL
);

-- TABLE: payment_dossiers - NEW
CREATE TABLE payment_dossiers (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    dossier_number  VARCHAR(100) NOT NULL,
    contract_id     INT DEFAULT NULL,
    ods_id          INT DEFAULT NULL,
    enterprise_id   INT DEFAULT NULL,
    amount          DECIMAL(15,2) DEFAULT NULL,
    status          ENUM('pending','in_progress','paid','rejected') DEFAULT 'pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE SET NULL,
    FOREIGN KEY (ods_id)      REFERENCES ods(id) ON DELETE SET NULL,
    FOREIGN KEY (enterprise_id) REFERENCES enterprises(id) ON DELETE SET NULL
);

-- TABLE: payment_dossier_docs - NEW (checklist)
CREATE TABLE payment_dossier_docs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    dossier_id      INT NOT NULL,
    doc_type        ENUM('ods_copy','pv_reception','facture','attachement','bon_commande','releve') NOT NULL,
    document_id     INT DEFAULT NULL,
    is_submitted    TINYINT(1) DEFAULT 0,
    submitted_at    TIMESTAMP NULL,
    FOREIGN KEY (dossier_id)   REFERENCES payment_dossiers(id) ON DELETE CASCADE,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL
);

-- ADD to documents table
ALTER TABLE documents 
ADD COLUMN contract_number VARCHAR(100) DEFAULT NULL,
ADD COLUMN contract_id INT DEFAULT NULL,
ADD COLUMN ods_id INT DEFAULT NULL,
ADD COLUMN payment_dossier_id INT DEFAULT NULL,
ADD INDEX idx_documents_contract_num (contract_number),
ADD INDEX idx_documents_contract (contract_id),
ADD INDEX idx_documents_ods (ods_id),
ADD INDEX idx_documents_payment (payment_dossier_id),
ADD FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE SET NULL,
ADD FOREIGN KEY (ods_id) REFERENCES ods(id) ON DELETE SET NULL,
ADD FOREIGN KEY (payment_dossier_id) REFERENCES payment_dossiers(id) ON DELETE SET NULL;

-- ADD to users table
ALTER TABLE users 
ADD COLUMN bureau VARCHAR(100) DEFAULT NULL;
```

---

## 📁 Complete File List — What Needs to Be Created

### NEW PHP Pages (14 files)
| File | Priority | Purpose |
|------|----------|---------|
| `pages/documents/search.php` | 🔴 HIGH | Contract number search + ODS filter |
| `pages/documents/contracts.php` | 🔴 HIGH | Contracts list |
| `pages/documents/contract_detail.php` | 🔴 HIGH | Contract + linked ODS + docs |
| `pages/documents/ods.php` | 🔴 HIGH | ODS list |
| `pages/documents/ods_detail.php` | 🟡 MEDIUM | Single ODS detail |
| `pages/documents/payments.php` | 🔴 HIGH | Payment dossiers with checklist |
| `pages/documents/archive.php` | 🟡 MEDIUM | Archive browser |
| `pages/documents/reports.php` | 🟡 MEDIUM | Reports + charts |
| `pages/documents/index.php` | 🔴 HIGH | Redirect to list.php |
| `pages/admin/ai_panel.php` | 🔴 HIGH | AI admin dashboard |
| `pages/admin/settings.php` | 🟡 MEDIUM | System settings |
| `pages/admin/departments.php` | 🟡 MEDIUM | Department CRUD |
| `pages/profile/index.php` | 🟡 MEDIUM | User profile |
| `pages/notifications.php` | 🟢 LOW | Notifications |

### NEW SQL Files (1 file)
| File | Purpose |
|------|---------|
| `sql/update_contracts_ods.sql` | Add contracts, ods, payment_dossiers tables + document columns |

### REBUILD / UPGRADE (2 files)
| File | Purpose |
|------|---------|
| `pages/documents/extracted_view.php` | Rebuild as rich AI dashboard (currently raw JSON dump) |
| `pages/documents/list.php` | Add contract_number column and filter |

### NEW CONTROLLERS (3 files)
| File | Purpose |
|------|---------|
| `controllers/contract_controller.php` | CRUD for contracts |
| `controllers/ods_controller.php` | CRUD for ODS |
| `controllers/payment_controller.php` | CRUD for payment dossiers |

### NEW AJAX Endpoints (3 files)
| File | Purpose |
|------|---------|
| `pages/documents/ajax_search.php` | Real-time contract/ODS search |
| `pages/documents/ajax_ai_status.php` | Poll AI processing status |
| `pages/admin/ajax_batch_ai.php` | Batch AI processing |

---

## 💡 Smart Features to Implement

### Feature 1: Auto-Contract Linking
When PDF uploaded + AI processed:
1. Extract `contract_number` from JSON
2. Lookup in `contracts` table
3. If found → link document
4. If not found → create stub + alert admin
5. All ODS docs group by contract automatically

### Feature 2: Payment Dossier Checker
From real AT payment PDF - required docs:
- ODS copy ✓
- PV réception provisoire ✓
- Facture ✓
- Attachement ✓
- Bon de commande ✓
Show **progress indicator** (3/5 uploaded) per dossier

### Feature 3: ODS Auto-Detection
After AI extraction, parse ODS numbers:
- Pattern: `ODS N°XXX/DRT/SDT/YYYY`
- Auto-create ODS records from extracted data
- Auto-link documents to ODS

### Feature 4: Contract Number = Universal Key
Make `contract_number` the main search key:
- Type contract number → see ALL docs (ODS, PV, factures)
- Works like "folder" for related docs

### Feature 5: Bureau/Division Tracking
From PDFs - track by bureau:
- DRT/SDT (Direction Régionale Télécom - Services DT)
- DRT/AGL, DRT/OST, etc.
Add to users and ODS tables

---

## 🔒 AI Solution (Free + Working)

> **Current:** Gemini 1.5 Flash (in ai_helper.php) - **FREE** (1500 req/day)
> **Works:** PDFs natively, returns structured JSON
> **No change to AI engine needed - just build admin UI**

**If exceeded:**
- **Ollama + Mistral 7B** (local, fully free on XAMPP)
- **Groq AI** (free tier, fast Llama 3)
- **OpenRouter** (many free models)

---

## 📊 Summary

| Category | Count |
|----------|-------|
| Already built | 26 files |
| New pages to create | 14 files |
| New SQL file | 1 file |
| Pages to rebuild/upgrade | 2 files |
| New controllers | 3 files |
| New AJAX endpoints | 3 files |
| **TOTAL** | **24 new files** |

---

## ✅ Recommended Build Order

### Phase 1: Database Foundation (1 day)
1. Run `sql/update_contracts_ods.sql`

### Phase 2: Core Business Features (3 days)
2. `pages/documents/index.php` - Fix redirect
3. `pages/documents/search.php` - **Highest priority**
4. `pages/documents/contracts.php` + `contract_detail.php`
5. `pages/documents/ods.php` + `ods_detail.php`
6. `pages/documents/payments.php` - With checklist view

### Phase 3: Admin & AI (2 days)
7. `pages/admin/ai_panel.php` - AI dashboard
8. `pages/admin/settings.php`
9. Rebuild `pages/documents/extracted_view.php`

### Phase 4: Supporting Features (2 days)
10. `pages/documents/archive.php`
11. `pages/documents/reports.php`
12. `pages/admin/departments.php`
13. `pages/profile/index.php`
14. `pages/notifications.php`

### Phase 5: Controllers & AJAX (1 day)
15. Contract, ODS, Payment controllers
16. AJAX endpoints for search and AI batch

---

## 🎯 Success Criteria

After completing this plan, the system will:
1. ✅ Allow search by contract number → show all ODS files
2. ✅ Manage full ODS lifecycle (create, track, complete)
3. ✅ Manage contracts with linked documents
4. ✅ Track payment dossiers with document checklist
5. ✅ Provide AI admin panel to monitor/manage extractions
6. ✅ Archive documents by year/department
7. ✅ Generate reports with charts

**The system will match the real AT internal procedures from the PDFs.**