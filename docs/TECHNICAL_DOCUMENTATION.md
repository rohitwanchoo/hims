# HIMS - Hospital Information Management System
## Technical Documentation for System Administrators

**Version:** 1.0
**Platform:** Laravel 12.0 + Vue 3
**Last Updated:** January 2026

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [System Requirements](#2-system-requirements)
3. [Installation Guide](#3-installation-guide)
4. [Architecture Overview](#4-architecture-overview)
5. [Module Documentation](#5-module-documentation)
6. [Database Schema](#6-database-schema)
7. [API Reference](#7-api-reference)
8. [Configuration Guide](#8-configuration-guide)
9. [Security Configuration](#9-security-configuration)
10. [Backup and Recovery](#10-backup-and-recovery)
11. [Monitoring and Maintenance](#11-monitoring-and-maintenance)
12. [Troubleshooting](#12-troubleshooting)

---

## 1. System Overview

HIMS (Hospital Information Management System) is an enterprise-grade, multi-tenant hospital management platform built on Laravel 12.0 with a Vue 3 frontend. It provides comprehensive clinical and administrative workflows for healthcare facilities.

### Key Features

- **Multi-Tenant Architecture**: Support for multiple hospitals with data isolation
- **Role-Based Access Control (RBAC)**: Granular permissions system
- **Clinical Modules**: OPD, IPD, Laboratory, Radiology, Pharmacy, Operation Theater
- **Administrative Modules**: Billing, Inventory, MRD, Birth/Death Registration
- **Integration Support**: ABHA/ABDM (Indian Health ID), FHIR R4, HL7
- **Patient Portal**: Self-service appointment booking and record access
- **Notification System**: SMS (multi-provider) and email notifications

### Technology Stack

| Component | Technology | Version |
|-----------|------------|---------|
| Backend Framework | Laravel | 12.0 |
| PHP Version | PHP | 8.2+ |
| Frontend Framework | Vue.js | 3.5.26 |
| State Management | Pinia | 3.0.4 |
| CSS Framework | Tailwind CSS + Bootstrap | 4.0 / 5.3.8 |
| Build Tool | Vite | 7.0.7 |
| Database | MySQL / MariaDB | 8.0+ / 10.6+ |
| API Authentication | Laravel Sanctum | 4.0 |
| Queue System | Database Driver | - |

---

## 2. System Requirements

### Minimum Hardware Requirements

| Resource | Minimum | Recommended |
|----------|---------|-------------|
| CPU | 2 cores | 4+ cores |
| RAM | 4 GB | 8+ GB |
| Storage | 50 GB SSD | 200+ GB SSD |
| Network | 100 Mbps | 1 Gbps |

### Software Requirements

#### Server Requirements

```
Operating System: Ubuntu 22.04 LTS / Debian 12 / RHEL 9+
Web Server: Apache 2.4+ or Nginx 1.18+
PHP: 8.2 or higher
Database: MySQL 8.0+ or MariaDB 10.6+
Node.js: 20.x LTS
Composer: 2.x
```

#### Required PHP Extensions

```
php-bcmath
php-ctype
php-curl
php-dom
php-fileinfo
php-json
php-mbstring
php-openssl
php-pdo
php-pdo_mysql
php-tokenizer
php-xml
php-zip
php-gd (for image processing)
php-redis (optional, for Redis caching)
```

### Network Requirements

| Port | Service | Purpose |
|------|---------|---------|
| 80 | HTTP | Web traffic (redirect to HTTPS) |
| 443 | HTTPS | Secure web traffic |
| 3306 | MySQL | Database connections |
| 6379 | Redis | Cache/Queue (optional) |

---

## 3. Installation Guide

### 3.1 Fresh Installation

#### Step 1: Clone Repository
```bash
cd /var/www/html
git clone <repository-url> hims-laravel
cd hims-laravel
```

#### Step 2: Install Dependencies
```bash
# PHP dependencies
composer install --optimize-autoloader --no-dev

# Node.js dependencies
npm install
```

#### Step 3: Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### Step 4: Configure Environment Variables
Edit `.env` file with your settings:

```ini
# Application
APP_NAME="HIMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hims_production
DB_USERNAME=hims_user
DB_PASSWORD=secure_password

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Sanctum (API Authentication)
SANCTUM_STATEFUL_DOMAINS=your-domain.com

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="HIMS"
```

#### Step 5: Database Setup
```bash
# Run migrations
php artisan migrate --force

# Seed initial data (optional)
php artisan db:seed
```

#### Step 6: Build Frontend Assets
```bash
npm run build
```

#### Step 7: Set Permissions
```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/html/hims-laravel

# Set directory permissions
sudo find /var/www/html/hims-laravel -type d -exec chmod 755 {} \;
sudo find /var/www/html/hims-laravel -type f -exec chmod 644 {} \;

# Writable directories
sudo chmod -R 775 storage bootstrap/cache
```

#### Step 8: Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 3.2 Web Server Configuration

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/html/hims-laravel/public;

    ssl_certificate /etc/ssl/certs/your-domain.crt;
    ssl_certificate_key /etc/ssl/private/your-domain.key;

    index index.php;
    charset utf-8;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # File upload size
    client_max_body_size 50M;
}
```

#### Apache Configuration (.htaccess in public/)
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### 3.3 Queue Worker Setup

Create a systemd service for queue processing:

```ini
# /etc/systemd/system/hims-queue.service
[Unit]
Description=HIMS Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/html/hims-laravel/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

Enable and start the service:
```bash
sudo systemctl enable hims-queue
sudo systemctl start hims-queue
```

---

## 4. Architecture Overview

### 4.1 Directory Structure

```
hims-laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/    # 61 API Controllers
│   │   └── Middleware/         # 4 Custom Middleware
│   ├── Models/                 # 118 Eloquent Models
│   ├── Services/               # Business Logic Services
│   ├── Jobs/                   # 5 Queue Jobs
│   ├── Traits/                 # 2 Shared Traits
│   └── Scopes/                 # Query Scopes
├── config/                     # 11 Configuration Files
├── database/
│   ├── migrations/             # 23 Migration Files
│   ├── seeders/                # Database Seeders
│   └── factories/              # Model Factories
├── routes/
│   ├── api.php                 # API Routes (517 lines)
│   └── web.php                 # Web Routes
├── resources/
│   ├── js/
│   │   ├── components/         # Vue Components
│   │   ├── views/              # 54 Vue Views
│   │   └── stores/             # Pinia Stores
│   └── views/prints/           # Blade Print Templates
├── storage/                    # Logs, Uploads, Cache
└── public/                     # Public Assets
```

### 4.2 Multi-Tenant Architecture

The system uses hospital-based data isolation:

1. **Hospital Scope**: All models with hospital-specific data use the `BelongsToHospital` trait
2. **Automatic Filtering**: `HospitalScope` automatically filters queries by the current hospital
3. **Middleware**: `SetCurrentHospital` middleware sets the active hospital context

```php
// Example: BelongsToHospital trait
trait BelongsToHospital
{
    protected static function bootBelongsToHospital()
    {
        static::addGlobalScope(new HospitalScope);

        static::creating(function ($model) {
            $model->hospital_id = auth()->user()->current_hospital_id;
        });
    }
}
```

### 4.3 Authentication Flow

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   Client    │────▶│ POST /login  │────▶│   Sanctum   │
│  (Browser)  │◀────│              │◀────│   Token     │
└─────────────┘     └──────────────┘     └─────────────┘
       │                                        │
       │    ┌──────────────────────────┐        │
       └───▶│ Authorization: Bearer    │◀───────┘
            │ <token>                  │
            └──────────────────────────┘
```

**Two Authentication Guards:**
- `sanctum`: Staff users (doctors, nurses, admin)
- `patient`: Patient portal users

---

## 5. Module Documentation

### 5.1 Patient Management

**Location:** `app/Http/Controllers/Api/PatientController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/patients` | GET | List patients with filtering |
| `/api/patients` | POST | Create new patient |
| `/api/patients/{id}` | GET | Get patient details |
| `/api/patients/{id}` | PUT | Update patient |
| `/api/patients/{id}/history` | GET | Get patient history |
| `/api/patients-search` | GET | Advanced search |
| `/api/patients/{id}/mark-vip` | POST | Mark as VIP |
| `/api/patients/{id}/upload-document` | POST | Upload document |

**Key Models:**
- `Patient` - Core patient entity
- `PatientClass` - Patient classification (General, VIP, Insurance, etc.)
- `PatientVital` - Vital signs records
- `PatientDocument` - Medical documents

### 5.2 OPD (Outpatient Department)

**Location:** `app/Http/Controllers/Api/OpdVisitController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/opd-visits` | GET | List OPD visits |
| `/api/opd-visits` | POST | Create OPD visit |
| `/api/opd-visits/{id}/start-consultation` | POST | Start consultation |
| `/api/opd-visits/{id}/complete-consultation` | POST | Complete with diagnosis |
| `/api/opd-visits/{id}/add-investigation` | POST | Add investigation |
| `/api/opd-visits/{id}/add-service` | POST | Add billable service |
| `/api/opd-visits/{id}/payment` | POST | Record payment |
| `/api/opd-visits/{id}/case-paper` | GET | Generate case paper |
| `/api/opd-visits/doctor/{id}/queue` | GET | Doctor's today queue |

**Workflow:**
```
Patient Registration → OPD Visit Created → Waiting Queue
       ↓
Start Consultation → Vitals Recording → Doctor Consultation
       ↓
Add Investigations/Services → Complete Consultation
       ↓
Generate Bill → Payment → Case Paper/Receipt
```

### 5.3 IPD (Inpatient Department)

**Location:** `app/Http/Controllers/Api/IpdAdmissionController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/ipd-admissions` | GET/POST | List/Create admissions |
| `/api/ipd-admissions-summary` | GET | Dashboard summary |
| `/api/ipd-admissions-bed-availability` | GET | Bed availability |
| `/api/ipd-admissions/{id}/transfer-bed` | POST | Transfer bed |
| `/api/ipd-admissions/{id}/progress-notes` | GET/POST | Progress notes |
| `/api/ipd-admissions/{id}/nursing-charts` | GET/POST | Nursing charts |
| `/api/ipd-admissions/{id}/medications` | GET/POST | Medications |
| `/api/ipd-admissions/{id}/running-bill` | GET | Current bill |
| `/api/ipd-admissions/{id}/initiate-discharge` | POST | Start discharge |
| `/api/ipd-admissions/{id}/complete-discharge` | POST | Final discharge |

**Key Components:**
- Ward and Bed management
- Progress notes (Doctor)
- Nursing charts (Nurse observations)
- Medication administration
- Services and investigations
- Running bill calculation
- Discharge workflow

### 5.4 Appointments

**Location:** `app/Http/Controllers/Api/AppointmentController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/appointments` | GET/POST | List/Create |
| `/api/appointments/{id}/confirm` | POST | Confirm appointment |
| `/api/appointments/{id}/check-in` | POST | Patient check-in |
| `/api/appointments/{id}/convert-to-opd` | POST | Convert to OPD visit |
| `/api/appointments/{id}/reschedule` | POST | Reschedule |
| `/api/appointments/{id}/cancel` | POST | Cancel with reason |
| `/api/appointments/doctor/{id}/available-slots` | GET | Available slots |
| `/api/appointments-transfer` | POST | Transfer to another doctor |
| `/api/appointments-bulk` | POST | Bulk create |

### 5.5 Laboratory

**Location:** `app/Http/Controllers/Api/LabOrderController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/lab-categories` | CRUD | Test categories |
| `/api/lab-tests` | CRUD | Test definitions |
| `/api/lab-orders` | CRUD | Test orders |
| `/api/lab-orders/{id}/results` | POST | Enter results |

### 5.6 Radiology

**Location:** `app/Http/Controllers/Api/RadiologyOrderController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/radiology/modalities` | CRUD | Equipment types (X-ray, CT, MRI) |
| `/api/radiology/tests` | CRUD | Radiology procedures |
| `/api/radiology/orders` | GET/POST | Imaging orders |
| `/api/radiology/orders/worklist` | GET | Technician worklist |
| `/api/radiology/reports` | CRUD | Radiology reports |
| `/api/radiology/reports/{id}/verify` | POST | Verify report |
| `/api/radiology/reports/{id}/upload-image` | POST | Upload images |
| `/api/radiology/reports/{id}/pdf` | GET | Generate PDF |

### 5.7 Operation Theater

**Location:** `app/Http/Controllers/Api/OtScheduleController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/ot/theaters` | CRUD | OT rooms |
| `/api/ot/surgery-types` | CRUD | Surgery types |
| `/api/ot/schedules` | GET/POST | Surgery schedules |
| `/api/ot/schedules/today` | GET | Today's schedule |
| `/api/ot/schedules/{id}/checklist` | POST | Pre-op checklist |
| `/api/ot/schedules/{id}/start` | POST | Start procedure |
| `/api/ot/procedures/{id}/complete` | POST | Complete procedure |
| `/api/ot/procedures/{id}/consumables` | POST | Add consumables |
| `/api/ot/procedures/{id}/anesthesia` | POST | Anesthesia record |

### 5.8 Pharmacy

**Location:** `app/Http/Controllers/Api/DrugController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/drug-categories` | CRUD | Drug categories |
| `/api/drugs` | CRUD | Drug master |
| `/api/drug-batches` | CRUD | Batch tracking |
| `/api/pharmacy-sales` | CRUD | Sales transactions |

### 5.9 Inventory Management

**Location:** `app/Http/Controllers/Api/ItemController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/inventory/stores` | CRUD | Storage locations |
| `/api/inventory/item-categories` | CRUD | Item categories |
| `/api/inventory/items` | CRUD | Items |
| `/api/inventory/items/low-stock` | GET | Low stock alerts |
| `/api/inventory/items/expiring` | GET | Expiring items |
| `/api/inventory/suppliers` | CRUD | Vendors |
| `/api/inventory/indents` | GET/POST | Stock requests |
| `/api/inventory/purchase-orders` | GET/POST | Purchase orders |
| `/api/inventory/purchase-orders/{id}/receive` | POST | Receive goods |

### 5.10 Billing & Services

**Location:** `app/Http/Controllers/Api/ServiceController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/services` | CRUD | Service master |
| `/api/services/{id}/revise-rate` | POST | Rate revision |
| `/api/services-get-rate` | GET | Get rate for class |
| `/api/bills` | CRUD | Bills |
| `/api/payments` | CRUD | Payments |
| `/api/payment-modes` | CRUD | Payment methods |
| `/api/cashless-price-lists` | CRUD | Insurance pricing |

### 5.11 Birth & Death Registration

**Location:** `app/Http/Controllers/Api/BirthRegistrationController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/birth-registrations` | CRUD | Birth records |
| `/api/birth-registrations/{id}/issue-certificate` | POST | Issue certificate |
| `/api/birth-registrations/{id}/govt-register` | POST | Government registration |
| `/api/death-registrations` | CRUD | Death records |
| `/api/death-registrations/{id}/issue-certificate` | POST | Issue certificate |

### 5.12 Medical Records Department (MRD)

**Location:** `app/Http/Controllers/Api/MrdController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/mrd/patients/{id}/documents` | GET/POST | Patient documents |
| `/api/mrd/patients/{id}/file-movements` | GET | File tracking |
| `/api/mrd/patients/{id}/issue-file` | POST | Issue file |
| `/api/mrd/record-requests` | GET/POST | Record requests |
| `/api/mrd/patients/{id}/consents` | GET/POST | Consent records |
| `/api/mrd/patients/{id}/diagnoses` | GET/POST | ICD coding |

### 5.13 ABHA/ABDM Integration

**Location:** `app/Http/Controllers/Api/AbhaController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/abha/generate-aadhaar-otp` | POST | Generate Aadhaar OTP |
| `/api/abha/verify-aadhaar-otp` | POST | Verify OTP |
| `/api/abha/create-abha` | POST | Create ABHA ID |
| `/api/abha/link-patient/{id}` | POST | Link to patient |
| `/api/abha/share-records/{id}` | POST | Share health records |
| `/api/abha/consents/{id}` | GET | Consent requests |

### 5.14 FHIR R4 API

**Location:** `app/Http/Controllers/Api/FhirController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/fhir/r4/metadata` | GET | Capability statement |
| `/api/fhir/r4/Patient` | GET | Search patients |
| `/api/fhir/r4/Patient/{id}` | GET | Read patient |
| `/api/fhir/r4/Observation` | GET | Search observations |
| `/api/fhir/r4/DiagnosticReport` | GET | Diagnostic reports |
| `/api/fhir/r4/MedicationRequest` | GET | Medication requests |

### 5.15 Patient Portal

**Location:** `app/Http/Controllers/Api/PatientPortalController.php`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/patient-portal/register` | POST | Patient registration |
| `/api/patient-portal/login` | POST | Portal login |
| `/api/patient-portal/verify-otp` | POST | OTP verification |
| `/api/patient-portal/profile` | GET/PUT | Profile management |
| `/api/patient-portal/appointment-requests` | GET/POST | Appointment requests |
| `/api/patient-portal/lab-reports` | GET | View lab reports |
| `/api/patient-portal/prescriptions` | GET | View prescriptions |
| `/api/patient-portal/bills` | GET | View bills |
| `/api/patient-portal/feedback` | POST | Submit feedback |

---

## 6. Database Schema

### 6.1 Migration Files

| Migration | Description | Tables Created |
|-----------|-------------|----------------|
| `create_users_table` | User authentication | users, sessions, password_reset_tokens |
| `create_hospitals_table` | Multi-tenancy | hospitals |
| `create_hims_tables` | Core HIMS entities | patients, doctors, departments, services, bills, etc. |
| `create_opd_masters_tables` | OPD master data | skill_sets, health_packages, doctor_groups, etc. |
| `create_opd_transaction_tables` | OPD transactions | opd_visits, opd_investigations, prescriptions |
| `create_opd_configuration_tables` | OPD configuration | opd_configurations, doctor_opd_rates, time_slots |
| `enhance_appointments_table` | Appointment enhancements | appointments (enhanced) |
| `enhance_ipd_module` | IPD enhancements | ipd_admissions, progress_notes, nursing_charts |
| `create_rbac_tables` | Access control | roles, permissions, role_user, permission_role |
| `create_notification_tables` | Notifications | sms_gateways, notification_templates, notification_logs |
| `create_radiology_tables` | Radiology module | modalities, radiology_tests, orders, reports, images |
| `create_ot_tables` | Operation theater | operation_theaters, ot_schedules, procedures, anesthesia |
| `create_inventory_tables` | Inventory | stores, items, suppliers, indents, purchase_orders, grn |
| `create_birth_death_tables` | Vital registration | birth_registrations, death_registrations |
| `create_mrd_tables` | Medical records | medical_record_requests, file_movements, icd_codes |
| `create_patient_portal_tables` | Patient portal | patient_users, sessions, appointment_requests, feedback |
| `create_abha_tables` | ABHA integration | abha_registrations, auth_tokens, consent_requests |
| `create_fhir_tables` | FHIR/HL7 | fhir_endpoints, fhir_resources, hl7_messages |

### 6.2 Core Entity Relationships

```
Hospital (1)
    ├── Users (n)
    ├── Patients (n)
    │   ├── OPD Visits (n)
    │   ├── IPD Admissions (n)
    │   ├── Lab Orders (n)
    │   ├── Radiology Orders (n)
    │   ├── Bills (n)
    │   └── Appointments (n)
    ├── Doctors (n)
    │   ├── Appointments (n)
    │   ├── OPD Visits (n)
    │   └── Doctor Groups (n)
    ├── Departments (n)
    │   ├── Doctors (n)
    │   ├── Services (n)
    │   └── Wards (n)
    ├── Wards (n)
    │   └── Beds (n)
    └── Services (n)
        └── Cashless Price Lists (n)
```

### 6.3 Database Commands

```bash
# Check migration status
php artisan migrate:status

# Run pending migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (WARNING: deletes all data)
php artisan migrate:fresh

# Seed database
php artisan db:seed

# Generate model from existing table
php artisan make:model ModelName -m
```

---

## 7. API Reference

### 7.1 Authentication

#### Login
```http
POST /api/login
Content-Type: application/json

{
    "email": "admin@hospital.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "Admin User",
        "email": "admin@hospital.com",
        "current_hospital_id": 1
    }
}
```

#### All Authenticated Requests
```http
Authorization: Bearer <token>
Accept: application/json
```

### 7.2 Common Response Formats

**Success:**
```json
{
    "success": true,
    "data": { ... },
    "message": "Operation successful"
}
```

**Error:**
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

**Paginated List:**
```json
{
    "data": [ ... ],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": "..."
    },
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150
    }
}
```

### 7.3 Rate Limiting

API requests are limited to prevent abuse:
- **Authenticated users**: 60 requests per minute
- **Unauthenticated**: 10 requests per minute

---

## 8. Configuration Guide

### 8.1 Environment Variables

#### Application Settings
```ini
APP_NAME="HIMS"                    # Application name
APP_ENV=production                  # Environment (local/staging/production)
APP_DEBUG=false                     # Debug mode (false in production)
APP_URL=https://hims.example.com    # Application URL
APP_KEY=                            # Auto-generated encryption key
```

#### Database Configuration
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hims_production
DB_USERNAME=hims_user
DB_PASSWORD=secure_password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

#### Session & Cache
```ini
SESSION_DRIVER=database             # Options: file, cookie, database, redis
SESSION_LIFETIME=120                # Minutes
CACHE_STORE=database                # Options: file, database, redis, memcached
QUEUE_CONNECTION=database           # Options: sync, database, redis
```

#### Mail Configuration
```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Sanctum (API Auth)
```ini
SANCTUM_STATEFUL_DOMAINS=hims.example.com
```

### 8.2 SMS Gateway Configuration

Configure via Admin Panel: **Settings → Notifications → SMS Gateways**

Supported Providers:
- **Msg91** (India)
- **TextLocal** (UK/India)
- **Twilio** (International)

### 8.3 ABHA/ABDM Configuration

For Indian Health ID integration:
```ini
ABDM_BASE_URL=https://healthidsbx.abdm.gov.in
ABDM_CLIENT_ID=your-client-id
ABDM_CLIENT_SECRET=your-client-secret
```

---

## 9. Security Configuration

### 9.1 Authentication Security

```ini
# Password hashing strength
BCRYPT_ROUNDS=12

# Session security
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

### 9.2 CORS Configuration

Edit `config/cors.php`:
```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://your-frontend-domain.com'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### 9.3 RBAC (Role-Based Access Control)

#### Default Roles
| Role | Description |
|------|-------------|
| super_admin | Full system access, manage hospitals |
| hospital_admin | Hospital-level administration |
| doctor | Clinical access (OPD, IPD, prescriptions) |
| nurse | Nursing functions (vitals, nursing charts) |
| receptionist | Front desk (registration, appointments) |
| lab_technician | Laboratory operations |
| radiologist | Radiology operations |
| pharmacist | Pharmacy operations |
| billing_staff | Billing and payments |
| store_manager | Inventory management |

#### Permission Modules
- patients, appointments, opd, ipd
- laboratory, radiology, pharmacy
- billing, inventory, reports
- users, roles, settings

### 9.4 Middleware

| Middleware | Purpose |
|------------|---------|
| `auth:sanctum` | API authentication |
| `auth:patient` | Patient portal authentication |
| `super_admin` | SuperAdmin-only routes |
| `check_permission` | Permission verification |
| `check_role` | Role verification |
| `set_current_hospital` | Multi-tenant context |

### 9.5 Security Best Practices

1. **Always use HTTPS** in production
2. **Rotate API tokens** periodically
3. **Enable audit logging** for sensitive operations
4. **Regular security updates** for Laravel and dependencies
5. **Database backups** with encryption
6. **Firewall rules** to restrict database access
7. **Input validation** on all user inputs
8. **SQL injection protection** (Eloquent handles this)
9. **XSS protection** via Blade templating
10. **CSRF protection** for web forms

---

## 10. Backup and Recovery

### 10.1 Database Backup

#### Manual Backup
```bash
# Full database backup
mysqldump -u hims_user -p hims_production > backup_$(date +%Y%m%d_%H%M%S).sql

# Compressed backup
mysqldump -u hims_user -p hims_production | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

#### Automated Backup Script
```bash
#!/bin/bash
# /opt/scripts/hims-backup.sh

BACKUP_DIR="/var/backups/hims"
DB_NAME="hims_production"
DB_USER="hims_user"
DB_PASS="your_password"
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$(date +%Y%m%d_%H%M%S).sql.gz

# Files backup (storage directory)
tar -czf $BACKUP_DIR/files_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/html/hims-laravel/storage

# Remove old backups
find $BACKUP_DIR -type f -mtime +$RETENTION_DAYS -delete

echo "Backup completed: $(date)"
```

Add to crontab:
```bash
# Daily backup at 2 AM
0 2 * * * /opt/scripts/hims-backup.sh >> /var/log/hims-backup.log 2>&1
```

### 10.2 Recovery Procedures

#### Database Recovery
```bash
# Stop application
sudo systemctl stop php8.2-fpm

# Restore database
gunzip < backup_20260101_020000.sql.gz | mysql -u hims_user -p hims_production

# Clear cache
cd /var/www/html/hims-laravel
php artisan cache:clear
php artisan config:clear

# Restart application
sudo systemctl start php8.2-fpm
```

#### Files Recovery
```bash
# Restore storage files
tar -xzf files_20260101_020000.tar.gz -C /

# Fix permissions
sudo chown -R www-data:www-data /var/www/html/hims-laravel/storage
```

### 10.3 Disaster Recovery Plan

1. **Daily backups** to local storage
2. **Weekly offsite backups** to cloud storage (AWS S3, Google Cloud)
3. **Test recovery** monthly
4. **Document RTO/RPO** (Recovery Time/Point Objective)

---

## 11. Monitoring and Maintenance

### 11.1 Log Files

| Log | Location | Purpose |
|-----|----------|---------|
| Laravel Log | `storage/logs/laravel.log` | Application errors |
| Queue Log | `storage/logs/queue.log` | Job processing |
| Nginx Access | `/var/log/nginx/access.log` | HTTP requests |
| Nginx Error | `/var/log/nginx/error.log` | Server errors |
| PHP-FPM | `/var/log/php8.2-fpm.log` | PHP errors |
| MySQL | `/var/log/mysql/error.log` | Database errors |

### 11.2 Log Rotation

Configure `/etc/logrotate.d/hims`:
```
/var/www/html/hims-laravel/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

### 11.3 Health Checks

#### Application Health
```bash
# Check if application responds
curl -I https://hims.example.com/api/login

# Check queue worker
sudo systemctl status hims-queue

# Check disk space
df -h /var/www/html/hims-laravel
```

#### Database Health
```bash
# Check MySQL status
sudo systemctl status mysql

# Check connections
mysql -u root -p -e "SHOW PROCESSLIST;"

# Check table sizes
mysql -u root -p -e "SELECT table_name, ROUND((data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.tables WHERE table_schema = 'hims_production' ORDER BY (data_length + index_length) DESC LIMIT 10;"
```

### 11.4 Performance Monitoring

#### Slow Query Log
Enable in MySQL:
```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

#### PHP-FPM Status
Enable status page in pool configuration and access via `/status`.

### 11.5 Maintenance Commands

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush

# Clear old sessions
php artisan session:table
```

### 11.6 Update Procedures

```bash
# Enable maintenance mode
php artisan down --message="System upgrade in progress"

# Backup database
mysqldump -u hims_user -p hims_production > pre_update_backup.sql

# Pull latest code
git pull origin main

# Update dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan optimize:clear
php artisan optimize

# Restart queue worker
sudo systemctl restart hims-queue

# Disable maintenance mode
php artisan up
```

---

## 12. Troubleshooting

### 12.1 Common Issues

#### 500 Internal Server Error
```bash
# Check Laravel log
tail -f storage/logs/laravel.log

# Check permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Clear config cache
php artisan config:clear
```

#### Database Connection Error
```bash
# Test MySQL connection
mysql -u hims_user -p -h 127.0.0.1 hims_production

# Check .env database settings
cat .env | grep DB_

# Clear config cache
php artisan config:clear
```

#### Queue Not Processing
```bash
# Check queue worker status
sudo systemctl status hims-queue

# View queue worker logs
sudo journalctl -u hims-queue -f

# Restart queue worker
sudo systemctl restart hims-queue

# Process single job manually
php artisan queue:work --once
```

#### Session Issues
```bash
# Clear sessions
php artisan session:table
php artisan cache:clear

# Check session table
mysql -u hims_user -p hims_production -e "SELECT COUNT(*) FROM sessions;"
```

#### Storage Permission Denied
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage

# Create symbolic link
php artisan storage:link
```

### 12.2 Performance Issues

#### Slow Page Load
1. Enable query logging:
```php
DB::enableQueryLog();
// ... your code ...
dd(DB::getQueryLog());
```

2. Check for N+1 queries
3. Add database indexes
4. Enable caching

#### High Memory Usage
```bash
# Check PHP memory limit
php -i | grep memory_limit

# Increase in php.ini
memory_limit = 256M
```

#### High CPU Usage
```bash
# Check process usage
top -c

# Identify slow queries
tail -f /var/log/mysql/slow.log
```

### 12.3 API Debugging

#### Test API Endpoint
```bash
# Get token
TOKEN=$(curl -s -X POST https://hims.example.com/api/login \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@hospital.com","password":"password"}' \
    | jq -r '.token')

# Test endpoint
curl -s -X GET https://hims.example.com/api/patients \
    -H "Authorization: Bearer $TOKEN" \
    -H "Accept: application/json" | jq
```

#### Debug Mode (Development Only)
```ini
# .env
APP_DEBUG=true
LOG_LEVEL=debug
```

### 12.4 Support Resources

- **Application Logs**: `storage/logs/laravel.log`
- **Artisan Tinker**: `php artisan tinker` (interactive shell)
- **Route List**: `php artisan route:list`
- **Laravel Documentation**: https://laravel.com/docs
- **Queue Dashboard**: Consider installing Laravel Horizon for advanced queue monitoring

---

## Appendix A: Artisan Commands Reference

```bash
# Application
php artisan about                    # Display application info
php artisan env                      # Display current environment
php artisan down                     # Put application in maintenance mode
php artisan up                       # Bring application out of maintenance

# Cache
php artisan cache:clear              # Clear application cache
php artisan config:cache             # Cache configuration
php artisan config:clear             # Clear configuration cache
php artisan route:cache              # Cache routes
php artisan route:clear              # Clear route cache
php artisan view:cache               # Cache views
php artisan view:clear               # Clear view cache
php artisan optimize                 # Cache everything
php artisan optimize:clear           # Clear all caches

# Database
php artisan migrate                  # Run migrations
php artisan migrate:status           # Show migration status
php artisan migrate:rollback         # Rollback last migration
php artisan db:seed                  # Run database seeders
php artisan tinker                   # Interactive shell

# Queue
php artisan queue:work               # Process queue jobs
php artisan queue:listen             # Listen for queue jobs
php artisan queue:failed             # List failed jobs
php artisan queue:retry all          # Retry all failed jobs
php artisan queue:flush              # Delete all failed jobs

# Development
php artisan make:controller Name     # Create controller
php artisan make:model Name -m       # Create model with migration
php artisan make:migration name      # Create migration
php artisan make:seeder Name         # Create seeder
```

---

## Appendix B: API Endpoints Summary

Total API Endpoints: **200+**

| Module | Endpoint Prefix | Count |
|--------|-----------------|-------|
| Authentication | `/api/` | 5 |
| Hospitals | `/api/hospitals` | 6 |
| Patients | `/api/patients` | 12 |
| Appointments | `/api/appointments` | 15 |
| OPD Visits | `/api/opd-visits` | 12 |
| IPD Admissions | `/api/ipd-admissions` | 23 |
| Laboratory | `/api/lab-*` | 8 |
| Radiology | `/api/radiology/*` | 16 |
| Operation Theater | `/api/ot/*` | 16 |
| Pharmacy | `/api/drug-*, pharmacy-*` | 8 |
| Inventory | `/api/inventory/*` | 18 |
| Billing | `/api/services, bills, payments` | 15 |
| Birth/Death | `/api/birth-*, death-*` | 12 |
| MRD | `/api/mrd/*` | 10 |
| ABHA/ABDM | `/api/abha/*` | 11 |
| FHIR | `/api/fhir/r4/*` | 7 |
| RBAC | `/api/roles/*` | 7 |
| Notifications | `/api/notifications/*` | 8 |
| Patient Portal | `/api/patient-portal/*` | 15 |

---

## Appendix C: Model Count by Category

| Category | Models | Examples |
|----------|--------|----------|
| Core/Admin | 10 | Hospital, User, Role, Permission, Setting |
| Patient | 10 | Patient, PatientClass, PatientVital, PatientDocument |
| OPD | 5 | OpdVisit, OpdConfiguration, OpdTimeSlot |
| IPD | 11 | IpdAdmission, Ward, Bed, ProgressNote, NursingChart |
| Appointments | 1 | Appointment |
| Doctors | 5 | Doctor, DoctorGroup, DoctorOpdRate, ReferenceDoctor |
| Laboratory | 4 | LabCategory, LabTest, LabOrder, LabOrderDetail |
| Radiology | 6 | RadiologyModality, RadiologyTest, RadiologyOrder, RadiologyReport |
| Operation Theater | 6 | OperationTheater, OtSchedule, OtProcedure, SurgeryType |
| Pharmacy | 8 | Drug, DrugCategory, DrugBatch, Prescription, PharmacySale |
| Inventory | 14 | Item, Store, Supplier, PurchaseOrder, Indent, GRN |
| Billing | 8 | Service, Bill, Payment, PaymentMode, CashlessPriceList |
| Birth/Death | 3 | BirthRegistration, DeathRegistration, StillbirthRegistration |
| MRD | 4 | MedicalRecordRequest, MrdFileMovement, CodingDiagnosis, IcdCode |
| Patient Portal | 4 | PatientUser, PatientPortalSession, PatientAppointmentRequest |
| ABHA/ABDM | 5 | AbhaRegistration, AbdmConsentRequest, AbdmHealthRecord |
| FHIR/HL7 | 4 | FhirEndpoint, FhirResource, FhirSubscription, Hl7Message |
| **Total** | **118** | |

---

**Document Version:** 1.0
**Generated:** January 2026
**Platform Version:** Laravel 12.0 / Vue 3.5.26
