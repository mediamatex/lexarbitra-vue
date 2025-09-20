# LexArbitra Vue - Deployment Guide

## Overview

This is the Vue/Laravel/Inertia version of LexArbitra, designed to replace the original Filament-based application. The main improvement is better handling of multi-database architecture with dynamic database creation per case via All-Inkl's KAS API.

## Key Features

- **Multi-Database Architecture**: Each case gets its own dedicated database for security and data isolation
- **All-Inkl KAS API Integration**: Automatic database creation and management
- **Vue/Inertia Frontend**: Modern, responsive UI with shadcn/ui components
- **UUID Primary Keys**: Enhanced security and better distribution support
- **Local Testing Mode**: SQLite fallback for development

## Prerequisites

- PHP 8.4+
- Node.js 18+
- Composer
- All-Inkl hosting account with KAS API access

## Environment Configuration

### Required Environment Variables

```bash
# Admin User
ADMIN_EMAIL="admin@lexarbitra.com"
ADMIN_PASSWORD="your-secure-password"
ADMIN_NAME="Administrator Name"

# KAS API (All-Inkl)
KAS_USER=your-kas-username
KAS_PASSWORD=your-kas-password
KAS_API_URL=https://kasapi.all-inkl.com/soap

# Production Database Mode
LOCAL_CASE_DB_TEST=false
```

### Optional Variables

```bash
# For local development only
LOCAL_CASE_DB_TEST=true  # Uses SQLite for case databases
```

## Deployment Steps

### 1. Server Setup

```bash
# Clone the repository
git clone <repository-url> lexarbitra-vue
cd lexarbitra-vue

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Set permissions
chmod -R 755 storage bootstrap/cache
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env with your configuration
nano .env
```

### 3. Database Setup

```bash
# Run migrations
php artisan migrate

# Create admin user
php artisan db:seed --class=AdminUserSeeder
```

### 4. Test API Connection

```bash
# Test KAS API connection
php artisan kas:test

# Test complete workflow (local mode)
php artisan test:case-creation
```

### 5. Production Configuration

**For production, ensure:**

```bash
# .env settings
APP_ENV=production
APP_DEBUG=false
LOCAL_CASE_DB_TEST=false

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## API Testing

### KAS API Test

```bash
php artisan kas:test
```

Expected output:
```
✅ KAS API connection successful!
```

### Full Workflow Test

```bash
php artisan test:case-creation
```

This tests:
- Case creation
- Database provisioning
- Connection testing
- Cleanup

## Database Architecture

### Master Database (Laravel Default)
- Users
- Case files metadata
- Database connections
- System configuration

### Case-Specific Databases
- Automatically created via KAS API
- Isolated per case for security
- Can store case-specific data, documents, etc.

## Web Interface

### Admin Login
- URL: `/login`
- Email: Value from `ADMIN_EMAIL`
- Password: Value from `ADMIN_PASSWORD`

### Case Management
- URL: `/cases`
- Create, view, edit, delete cases
- Automatic database creation
- Database status indicators

## Troubleshooting

### KAS API Issues

1. **Connection Failed**
   - Verify KAS_USER and KAS_PASSWORD
   - Check KAS API is enabled in All-Inkl panel
   - Ensure server can reach kasapi.all-inkl.com

2. **Flood Protection**
   - Normal behavior for rapid API calls
   - Connection still works for actual operations

### Database Issues

1. **Local Testing Mode**
   - Set `LOCAL_CASE_DB_TEST=true`
   - Uses SQLite files in `database/case_databases/`

2. **Production Mode**
   - Set `LOCAL_CASE_DB_TEST=false`
   - Uses KAS API for MySQL databases

### Frontend Issues

1. **Build Errors**
   ```bash
   npm run build
   ```

2. **Missing Components**
   ```bash
   npx shadcn@latest add [component-name]
   ```

## Monitoring

### Log Files
- Laravel logs: `storage/logs/laravel.log`
- KAS API operations logged with context

### Database Connections
- Check `/cases` for database status indicators
- Use `php artisan test:case-creation` for testing

## Security Notes

- Database passwords are encrypted in storage
- UUID primary keys prevent enumeration
- Each case has isolated database
- Admin credentials should be rotated regularly

## Migration from Filament Version

1. Export case data from old system
2. Import via Laravel seeders or direct database insert
3. Run database creation for existing cases:
   ```bash
   php artisan cases:create-missing-databases
   ```

## Support

For issues:
1. Check logs in `storage/logs/`
2. Test KAS API connection
3. Verify environment configuration
4. Run unit tests: `php artisan test`