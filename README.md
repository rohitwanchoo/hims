# HIMS - Hospital Information Management System

![Laravel](https://img.shields.io/badge/Laravel-12.0-red.svg)
![PHP](https://img.shields.io/badge/PHP-8.2-blue.svg)
![Vue.js](https://img.shields.io/badge/Vue.js-3.5-green.svg)
![License](https://img.shields.io/badge/License-MIT-yellow.svg)

A comprehensive Hospital Information Management System built with Laravel 12 and Vue 3, designed to manage all aspects of hospital operations from patient registration to discharge.

---

## Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Modules](#modules)
- [Documentation](#documentation)
- [Development](#development)
- [Testing](#testing)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)

---

## Features

### Core Modules

- **Patient Management** - Complete patient registration, demographics, and medical history
- **OPD (Outpatient Department)** - Consultation, prescriptions, and follow-ups
- **IPD (Inpatient Department)** - Admissions, bed management, and discharge
- **Appointment Scheduling** - Token-based appointment system with time slot management
- **Doctor Workbench** - Integrated consultation interface with AI assistance
- **Billing & Payments** - Comprehensive billing with multiple payment modes
- **Pharmacy** - Medicine dispensing, inventory, and stock management
- **Pathology/Laboratory** - Test orders, sample collection, and report generation
- **Radiology** - Imaging orders and report management
- **Prescription Management** - Digital prescription with drug interaction checks
- **EMR (Electronic Medical Records)** - Complete patient medical history
- **Inventory Management** - Stock control for medicines and consumables
- **Birth & Death Registration** - Vital statistics recording
- **ABHA Integration** - Ayushman Bharat Health Account linking
- **RBAC** - Role-based access control

### Key Features

- Real-time patient queue management
- AI-powered clinical assistance (Claude integration)
- Multi-language support
- Responsive design for desktop, tablet, and mobile
- Digital signature support
- Barcode/QR code generation
- Report generation and analytics
- Audit trail for critical operations
- Frontend error logging
- Comprehensive master data management

---

## Technology Stack

### Backend
- **Framework:** Laravel 12.0
- **Language:** PHP 8.2
- **Database:** MySQL/SQLite
- **Authentication:** Laravel Sanctum
- **API:** RESTful API

### Frontend
- **Framework:** Vue 3.5
- **State Management:** Pinia 3.0
- **Routing:** Vue Router 4.6
- **UI Framework:** Bootstrap 5.3 + TailwindCSS 4.0
- **Icons:** Bootstrap Icons
- **Build Tool:** Vite 7.0

### Additional Libraries
- **Markdown Rendering:** Marked
- **Code Highlighting:** Highlight.js
- **Drag & Drop:** Vue Draggable
- **Sanitization:** DOMPurify
- **HTTP Client:** Axios

---

## System Requirements

### Server Requirements
- PHP >= 8.2
- Composer >= 2.x
- Node.js >= 18.x
- NPM >= 9.x
- MySQL >= 8.0 or SQLite

### PHP Extensions
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML

### Recommended Server Specs
- CPU: 2+ cores
- RAM: 4GB minimum, 8GB recommended
- Storage: 20GB minimum
- Web Server: Apache/Nginx

---

## Installation

### Quick Setup

```bash
# Clone the repository
git clone <repository-url>
cd hims-laravel

# Run automated setup
composer setup
```

The `composer setup` script will:
1. Install PHP dependencies
2. Create .env file from .env.example
3. Generate application key
4. Run database migrations
5. Install Node.js dependencies
6. Build frontend assets

### Manual Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Copy environment file
cp .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Configure database in .env file
# Edit .env and set your database credentials

# 5. Run migrations
php artisan migrate

# 6. Install Node.js dependencies
npm install

# 7. Build frontend assets
npm run build
```

---

## Configuration

### Environment Variables

Create a `.env` file in the root directory and configure the following:

#### Application Settings
```env
APP_NAME="HIMS"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=http://your-domain.com
```

#### Database Configuration
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hims_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### Session & Cache
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
```

#### Mail Configuration
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS=noreply@yourhospital.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Claude AI (Optional)
```env
ANTHROPIC_API_KEY=your-anthropic-api-key
```

For complete environment setup guide, see [ENVIRONMENT_SETUP.md](ENVIRONMENT_SETUP.md)

---

## Usage

### Development Mode

Run all services in development mode with hot reload:

```bash
composer dev
```

This starts:
- Laravel development server (http://localhost:8000)
- Queue worker
- Log viewer (Laravel Pail)
- Vite dev server (hot module replacement)

### Individual Services

```bash
# Start Laravel server
php artisan serve

# Start Vite dev server
npm run dev

# Run queue worker
php artisan queue:listen

# View logs
php artisan pail
```

### Production Build

```bash
# Build optimized assets
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Modules

### 1. Patient Management
- Patient registration with UHID generation
- Demographics and contact information
- Medical history and allergies
- ABHA integration
- Patient search and filters

### 2. OPD (Outpatient Department)
- Token-based queue management
- Doctor consultation interface
- Vital signs recording
- Chief complaints and diagnosis
- Treatment plans
- Follow-up scheduling

### 3. IPD (Inpatient Department)
- Admission and bed allocation
- Bed transfer management
- Daily consultation rounds
- Discharge summary
- IPD billing

### 4. Doctor Workbench
- Patient queue view
- Previous visit history
- Clinical templates
- Prescription writing with favorites
- Investigation orders
- AI-powered assistance

### 5. Billing & Payments
- Service and package billing
- Multiple payment modes (Cash, Card, UPI, Insurance)
- Advance payments
- Credit billing
- Refund management
- Receipt generation

### 6. Pathology Module
Complete pathology management with 17+ masters:
- Test catalog and test groups
- Sample types and containers
- Analyzers and test mapping
- External lab centers
- Test methods and units
- Result entry and validation
- Report generation

### 7. Pharmacy
- Medicine dispensing
- Batch and expiry management
- Stock management
- Purchase orders
- Supplier management
- Drug interaction alerts

### 8. Prescription
- Digital prescription generation
- Drug favorites and templates
- Dosage and frequency
- Drug interaction checks
- Allergy alerts
- Print and digital delivery

### 9. Radiology
- Imaging order management
- Multiple modalities support
- Report generation
- Image viewing

### 10. Appointment Scheduling
- Department and doctor selection
- Time slot management
- Token generation
- SMS/Email notifications
- Calendar view

---

## Documentation

- [WORKFLOW.md](WORKFLOW.md) - Complete workflow documentation
- [ENVIRONMENT_SETUP.md](ENVIRONMENT_SETUP.md) - Environment configuration guide
- [ERROR_WORKFLOW.md](ERROR_WORKFLOW.md) - Error handling workflow
- [CONSOLE_ERROR_LOGGING.md](CONSOLE_ERROR_LOGGING.md) - Frontend error logging

### Existing Documentation
- [1 OPD Masters.docx](1%20OPD%20Masters.docx)
- [2 OPD Transactions.docx](2%20OPD%20Transactions.docx)
- [3 OPD Configuration.docx](3%20OPD%20Configuration.docx)
- [4 Appointment Module.docx](4%20Appointment%20Module.docx)
- [6 EMR.docx](6%20EMR.docx)

---

## Development

### Project Structure

```
hims-laravel/
├── app/
│   ├── Console/          # Console commands
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/      # API controllers
│   └── Models/           # Eloquent models
├── database/
│   ├── migrations/       # Database migrations
│   └── seeders/         # Database seeders
├── resources/
│   ├── js/
│   │   ├── components/  # Vue components
│   │   ├── views/       # Vue pages
│   │   ├── router/      # Vue Router
│   │   └── stores/      # Pinia stores
│   └── views/           # Blade templates
├── routes/
│   ├── api.php          # API routes
│   └── web.php          # Web routes
└── public/              # Public assets
```

### Coding Standards

- Follow PSR-12 for PHP
- Use Laravel best practices
- Follow Vue 3 Composition API guidelines
- Use ESLint for JavaScript
- Use Laravel Pint for PHP formatting

### Git Workflow

```bash
# Create feature branch
git checkout -b feature/your-feature-name

# Make changes and commit
git add .
git commit -m "Description of changes"

# Push to remote
git push origin feature/your-feature-name
```

---

## Testing

### Run Tests

```bash
# Run all tests
composer test

# Run specific test file
php artisan test --filter=TestClassName

# Run with coverage
php artisan test --coverage
```

### Writing Tests

Place tests in `tests/` directory:
- `tests/Feature/` - Feature/integration tests
- `tests/Unit/` - Unit tests

---

## Deployment

### Production Checklist

1. Set `APP_ENV=production` and `APP_DEBUG=false`
2. Set proper `APP_URL`
3. Configure database credentials
4. Run migrations: `php artisan migrate --force`
5. Build assets: `npm run build`
6. Cache configuration: `php artisan config:cache`
7. Cache routes: `php artisan route:cache`
8. Set proper file permissions
9. Configure queue worker (supervisor)
10. Setup cron for scheduled tasks
11. Configure SSL certificate
12. Setup backup system

### Queue Worker (Supervisor)

Create supervisor configuration:

```ini
[program:hims-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
```

### Scheduled Tasks

Add to crontab:

```bash
* * * * * cd /path/to/hims-laravel && php artisan schedule:run >> /dev/null 2>&1
```

---

## API Documentation

### Authentication

All API requests require authentication using Laravel Sanctum tokens.

```bash
# Login
POST /api/login
{
  "email": "user@example.com",
  "password": "password"
}

# Response
{
  "token": "your-sanctum-token",
  "user": { ... }
}
```

Use the token in subsequent requests:

```bash
Authorization: Bearer your-sanctum-token
```

### Key Endpoints

```
# Patients
GET    /api/patients
POST   /api/patients
GET    /api/patients/{id}
PUT    /api/patients/{id}

# OPD Visits
GET    /api/opd-visits
POST   /api/opd-visits
GET    /api/opd-visits/{id}

# Appointments
GET    /api/appointments
POST   /api/appointments

# Bills
GET    /api/bills
POST   /api/bills
GET    /api/bills/{id}

# Prescriptions
GET    /api/prescriptions
POST   /api/prescriptions
```

---

## Performance Optimization

### Database
- Use database indexes
- Optimize queries with eager loading
- Use database transactions
- Regular database maintenance

### Caching
- Cache configuration and routes
- Use Redis for session and cache (recommended)
- Implement query caching for reports

### Frontend
- Lazy load components
- Code splitting
- Optimize images
- Use CDN for static assets

---

## Security

### Best Practices
- Keep Laravel and dependencies updated
- Use HTTPS in production
- Implement CSRF protection
- Sanitize user inputs
- Use prepared statements (Eloquent does this)
- Implement rate limiting
- Regular security audits
- Proper file permissions

### File Permissions

```bash
# Set proper ownership
sudo chown -R www-data:www-data /path/to/hims-laravel

# Set directory permissions
find /path/to/hims-laravel -type d -exec chmod 755 {} \;

# Set file permissions
find /path/to/hims-laravel -type f -exec chmod 644 {} \;

# Storage and cache writable
chmod -R 775 storage bootstrap/cache
```

---

## Troubleshooting

### Common Issues

**Issue:** 500 Internal Server Error
**Solution:** Check `storage/logs/laravel.log`, ensure proper file permissions

**Issue:** Vite manifest not found
**Solution:** Run `npm run build`

**Issue:** Database connection error
**Solution:** Verify database credentials in `.env`, ensure database exists

**Issue:** Queue jobs not processing
**Solution:** Ensure queue worker is running: `php artisan queue:listen`

**Issue:** Session not persisting
**Solution:** Clear cache: `php artisan config:clear`, check session driver

---

## Support

### Getting Help
- Check documentation in `/docs` directory
- Review existing documentation (.docx files)
- Check Laravel documentation: https://laravel.com/docs
- Check Vue.js documentation: https://vuejs.org

### Reporting Issues
- Check existing issues first
- Provide detailed description
- Include error messages and logs
- Specify environment details

---

## Contributing

We welcome contributions! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Write/update tests
5. Ensure code follows standards
6. Submit a pull request

---

## Changelog

### Version 1.0.0 (Current)
- Complete OPD module
- IPD management
- Pathology module with 17 masters
- Billing and payment processing
- Prescription management
- Doctor workbench
- ABHA integration
- Frontend error logging
- AI assistance integration

---

## License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## Acknowledgments

- Laravel Framework
- Vue.js Team
- All contributors and users

---

## Contact

For inquiries and support:
- Email: support@yourhospital.com
- Website: https://yourhospital.com

---

**Version:** 1.0.0
**Last Updated:** February 2026
**Maintained By:** Development Team
