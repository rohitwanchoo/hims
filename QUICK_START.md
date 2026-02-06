# HIMS - Quick Start Guide

## Installation in 5 Minutes

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+ or SQLite

### Quick Setup

```bash
# 1. Clone and navigate
cd hims-laravel

# 2. Run automated setup
composer setup

# 3. Configure database (.env file)
# Edit .env and set your database credentials

# 4. Start development server
composer dev
```

Access: http://localhost:8000

---

## Manual Setup

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env, then:
php artisan migrate

# Build assets
npm run build

# Start server
php artisan serve
```

---

## Key Commands

### Development
```bash
composer dev          # Start all services (server, queue, logs, vite)
php artisan serve     # Start Laravel server only
npm run dev          # Start Vite dev server only
```

### Database
```bash
php artisan migrate           # Run migrations
php artisan migrate:fresh     # Fresh migration (drops all tables)
php artisan db:seed          # Seed database
```

### Cache
```bash
php artisan config:clear     # Clear config cache
php artisan cache:clear      # Clear application cache
php artisan view:clear       # Clear view cache
php artisan route:clear      # Clear route cache
```

### Production
```bash
npm run build                # Build production assets
php artisan config:cache     # Cache configuration
php artisan route:cache      # Cache routes
php artisan view:cache       # Cache views
```

---

## Essential .env Settings

### Development
```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
MAIL_MAILER=log
QUEUE_CONNECTION=sync
```

### Production
```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

## File Permissions

```bash
sudo chown -R www-data:www-data /path/to/hims-laravel
chmod -R 775 storage bootstrap/cache
```

---

## Common Issues

**Error: Key not specified**
```bash
php artisan key:generate
```

**Error: Vite manifest not found**
```bash
npm run build
```

**Error: Database connection**
- Check .env database settings
- Ensure database exists
- Test: `mysql -u username -p`

---

## Project Structure

```
hims-laravel/
├── app/              # Application code
├── database/         # Migrations, seeders
├── resources/
│   ├── js/          # Vue.js frontend
│   └── views/       # Blade templates
├── routes/          # API & web routes
├── public/          # Public assets
└── .env            # Configuration
```

---

## Key Modules

1. **Patient Management** - Registration, demographics
2. **OPD** - Outpatient consultations
3. **IPD** - Inpatient admissions
4. **Billing** - Bills and payments
5. **Pathology** - Lab tests and reports
6. **Pharmacy** - Medicine dispensing
7. **Doctor Workbench** - Clinical interface
8. **Appointments** - Scheduling system

---

## Documentation

- [README.md](README.md) - Complete documentation
- [WORKFLOW.md](WORKFLOW.md) - System workflows
- [ENVIRONMENT_SETUP.md](ENVIRONMENT_SETUP.md) - Environment guide
- [ERROR_WORKFLOW.md](ERROR_WORKFLOW.md) - Error handling

---

## Support

- Documentation: `/docs` folder
- Logs: `storage/logs/laravel.log`
- Laravel Docs: https://laravel.com/docs
- Vue.js Docs: https://vuejs.org

---

Ready to start! Run `composer dev` and visit http://localhost:8000
