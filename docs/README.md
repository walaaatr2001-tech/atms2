# AT-AMS - Algeria Telecom Archive Management System

## Project Overview

**AT-AMS** (Archive Management System) is a comprehensive web-based document management system developed for Algeria Telecom. The system enables secure upload, versioning, validation, and archival of official documents across multiple departments and enterprise partners.

---

## Table of Contents

1. [System Overview](#system-overview)
2. [Technology Stack](#technology-stack)
3. [User Roles](#user-roles)
4. [Database Schema](#database-schema)
5. [Features](#features)
6. [Installation](#installation)
7. [Configuration](#configuration)
8. [API Endpoints](#api-endpoints)
9. [Security](#security)
10. [Troubleshooting](#troubleshooting)

---

## System Overview

### Purpose
Centralized document management system for Algeria Telecom to:
- Manage internal and external documents
- Track document versions
- Validate documents through workflow
- Archive documents securely
- Audit all document activities

### Key Features
- Multi-step registration for enterprise partners
- Document upload with automatic versioning
- AI-powered document analysis (Gemini AI integration)
- Role-based access control (RBAC)
- Wilaya/City location management
- Bank and RIB management
- Audit logging
- Notification system

---

## Technology Stack

| Component | Technology |
|-----------|------------|
| Backend | PHP 7.4+ |
| Database | MySQL 8.0 |
| Frontend | HTML5, TailwindCSS |
| Icons | Font Awesome 6.5 |
| JavaScript | Vanilla JS |
| Server | XAMPP (Apache) |

---

## User Roles

| Role | Description | Permissions |
|------|-------------|-------------|
| `super_admin` | System Administrator | Full access to all features, settings, audit logs |
| `dept_admin` | Department Admin | Manage users, documents within department |
| `internal_staff` | Internal Staff | Upload, view, manage documents |
| `enterprise` | Enterprise Partner | Upload documents, view own documents |

### Default Users

| Username | Password | Role |
|----------|----------|------|
| `superadmin` | `admin123` | super_admin |
| `nassim.ghanem` | `admin123` | dept_admin |
| `ahmed.bensalem` | `staff123` | internal_staff |
| `entreprise_test` | `entreprise123` | enterprise |

---

## Database Schema

### Tables Overview

```
┌─────────────────────────────┐
│ wilayas                     │  (58 Algerian wilayas)
├─────────────────────────────┤
│ cities                      │  (cities per wilaya)
├─────────────────────────────┤
│ banks                       │  (Algerian banks)
├─────────────────────────────┤
│ departments                 │  (AT departments)
├─────────────────────────────┤
│ users                       │  (all system users)
├─────────────────────────────┤
│ enterprises                 │  (enterprise partner info)
├─────────────────────────────┤
│ document_categories         │  (document types)
├─────────────────────────────┤
│ documents                   │  (main document records)
├─────────────────────────────┤
│ document_versions           │  (version history)
├─────────────────────────────┤
│ audit_logs                  │  (activity logging)
├─────────────────────────────┤
│ notifications               │  (user notifications)
├─────────────────────────────┤
│ settings                    │  (system configuration)
└─────────────────────────────┘
```

### Key Relationships

- **users** → **departments** (optional FK)
- **users** → **enterprises** (1:1 via user_id)
- **documents** → **users** (uploaded_by)
- **documents** → **departments** (FK)
- **documents** → **enterprises** (optional FK)
- **document_versions** → **documents** (FK)
- **audit_logs** → **users** (FK)

---

## Features

### Authentication

- Secure login with username or email
- Password hashing (bcrypt)
- CSRF protection
- Session management
- Auto-upgrade plain passwords to hashed

### Registration Flow (4 Steps)

1. **Personal Information**
   - First name, Last name
   - Username (unique)
   - Email (unique)
   - Phone (accepts +213 or 0 prefix)
   - Password

2. **Company Information**
   - NIF (Numéro Identifiant Fiscal)
   - RC (Registre de Commerce)
   - RIB (20 digits)
   - Bank selection

3. **Location**
   - Wilaya (58 provinces)
   - City (dynamic based on wilaya)
   - Agency address

4. **Review & Submit**
   - Summary of all entered data
   - Pending approval status

### Dashboard

- Document statistics
- Recent documents
- Quick actions

### Document Management

- Upload documents (PDF, DOCX, XLSX, JPG, PNG, ZIP, RAR)
- Automatic version numbering
- Category assignment
- Status workflow: draft → submitted → validated/rejected → archived
- Version history

### AI Document Analysis

- Upload form includes AI processing toggle
- Uses Gemini AI for content extraction
- Stores extracted data as JSON in documents table
- Toggle available on upload page via checkbox

### Admin Features

- User management
- Enterprise approval/rejection
- Audit log viewing
- System settings

### Audit Logging

- All user actions logged via `logAction()` function
- Tracks entity type, action performed, and entity ID
- Stored in `audit_logs` table with timestamp

---

## Installation

### Prerequisites

- XAMPP or similar Apache/MySQL stack
- PHP 7.4 or higher
- MySQL 8.0

### Steps

1. **Start Apache and MySQL** in XAMPP Control Panel

2. **Import Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create database `at_ams`
   - Import `sql/schema.sql`

3. **Configure Database**
   - Edit `config/database.php` if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'at_ams');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Your MySQL password
   ```

4. **Access Application**
   - Login: `http://localhost/AT-AMS/pages/auth/login.php`
   - Register: `http://localhost/AT-AMS/pages/auth/register.php`

---

## Configuration

### File Structure

```
AT-AMS/
├── config/
│   ├── config.php      (main config, functions)
│   └── database.php    (DB connection)
├── controllers/
│   └── upload_controller.php (document upload handler)
├── includes/
│   ├── ai_helper.php   (Gemini AI integration)
│   ├── auth.php       (auth checks)
│   ├── footer.php     (main layout footer)
│   └── header.php     (main layout header)
├── pages/
│   ├── auth/           (login, register)
│   ├── admin/         (admin pages)
│   ├── dashboard/     (main dashboard)
│   │   └── enterprise/ (enterprise partner dashboard)
│   ├── documents/     (document management)
│   │   ├── detail.php
│   │   ├── list.php
│   │   ├── upload.php
│   │   └── versions.php
│   └── profile/       (user profile)
├── public/
│   ├── css/           (styles)
│   └── js/            (JavaScript)
├── sql/
│   ├── schema.sql      (database schema + seed)
│   └── seed.sql       (additional seed data)
└── index.php          (redirect to login)
```

### Key Configuration Options

In `config/config.php`:
```php
define('BASE_PATH', __DIR__ . '/..');
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('APP_NAME', 'AT-AMS - Algeria Telecom');
```

In `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'at_ams');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### Core Functions

The `config/config.php` provides the following utility functions:

| Function | Description |
|----------|-------------|
| `generateCSRF()` | Generates CSRF token for forms |
| `validateCSRF($token)` | Validates CSRF token |
| `login($email, $password)` | Authenticates user and creates session |
| `isLoggedIn()` | Checks if user is authenticated |
| `getUser()` | Returns current user data |
| `redirect($url)` | Redirects to specified URL |
| `generateRefNumber()` | Generates document reference (AT-YYYY-NNNN) |
| `logAction($action, $type, $type_id)` | Logs action to audit_logs table |

---

## API Endpoints

### Authentication

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/pages/auth/login.php` | POST | User login |
| `/pages/auth/register.php` | GET/POST | User registration |
| `/pages/auth/register_action.php` | POST | Process registration |

### Data

| Endpoint | Method | Parameters | Description |
|----------|--------|------------|-------------|
| `/pages/auth/get_cities.php` | GET | `wilaya_id` | Get cities by wilaya |

---

## Security Features

- **CSRF Protection**: All forms use CSRF tokens
- **Password Hashing**: bcrypt via `password_hash()`
- **SQL Injection Prevention**: Prepared statements + escaping
- **XSS Prevention**: `htmlspecialchars()` on output
- **Session Security**: `session_regenerate_id()` on login
- **Input Validation**: Server-side validation on all inputs

---

## Troubleshooting

### Common Issues

#### 1. "Cannot read properties of undefined"
- **Cause**: Missing JavaScript variable or element
- **Fix**: Check browser console for specific error

#### 2. Database connection error
- **Cause**: Wrong credentials in `database.php`
- **Fix**: Verify MySQL credentials

#### 3. Phone validation fails
- **Cause**: Using wrong format
- **Fix**: Use `0550XXXXXX` or `+213XXXXXXXXX`

#### 4. Redirect not working
- **Cause**: Headers already sent
- **Fix**: Ensure no output before `header()`

#### 5. Login not working
- **Cause**: Status check or password issue
- **Fix**: Check user status in database (must be 'active')

### Testing Checklist

- [ ] Registration flow completes
- [ ] Login redirects to dashboard
- [ ] Phone accepts both formats
- [ ] Success message appears and auto-hides
- [ ] All user roles can login
- [ ] Document upload works
- [ ] Admin can approve enterprises

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.2.0 | April 2026 | Fixed logAction function, added audit logging, AI document analysis |
| 1.1.0 | April 2026 | Added document version history, upload controller, enterprise dashboard |
| 1.0.0 | 2026 | Initial release |

---

## Support

For issues or questions:
- Email: admin@algerietelecom.dz
- Internal: IT Department

---

*Documentation generated for AT-AMS v1.2.0*
*Algeria Telecom - Archive Management System*
