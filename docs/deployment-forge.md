# FitTrack BR - Laravel Forge Deployment Guide

**Version**: 1.0.0
**Last Updated**: 2025-11-10
**Target Platform**: Laravel Forge + DigitalOcean
**Estimated Setup Time**: 45-60 minutes

---

## =Ë Prerequisites

Before starting, ensure you have:

- [ ] Laravel Forge account (https://forge.laravel.com)
- [ ] DigitalOcean account (or AWS/Linode/Vultr)
- [ ] Domain name configured (e.g., `fittrackbr.com`)
- [ ] GitHub repository access
- [ ] Sentry project created (https://sentry.io)
- [ ] This deployment guide open

---

## =€ Part 1: Server Creation (10 minutes)

### Step 1: Create New Server in Forge

1. **Login to Laravel Forge**: https://forge.laravel.com
2. **Click "Create Server"**
3. **Configure Server**:

| Setting | Value | Notes |
|---------|-------|-------|
| **Provider** | DigitalOcean | Recommended for this app |
| **Server Size** | 2GB RAM / 2 vCPUs | Minimum for production |
| **Region** | São Paulo 1 (sfo3) | Closest to Brazil |
| **PHP Version** | PHP 8.4 |   Required |
| **Database** | PostgreSQL 16 |   Required |
| **Server Name** | `fittrack-production` | Descriptive name |

4. **Click "Create Server"**
5. **Wait 5-10 minutes** for provisioning

---

### Step 2: Verify Server Provisioning

Once server shows "Active" status:

```bash
# SSH into server (credentials in Forge)
ssh forge@YOUR_SERVER_IP

# Verify PHP version
php -v
# Expected: PHP 8.4.x

# Verify PostgreSQL
psql --version
# Expected: psql (PostgreSQL) 16.x

# Exit SSH
exit
```

---

## =Ä Part 2: Database Setup (15 minutes)

### Step 1: Install PostGIS Extension

**CRITICAL**: This app requires PostGIS for geospatial features.

```bash
# SSH into server
ssh forge@YOUR_SERVER_IP

# Install PostGIS packages
sudo apt-get update
sudo apt-get install -y postgresql-16-postgis-3

# Verify installation
apt-cache show postgresql-16-postgis-3 | grep Version
# Expected: Version: 3.4.x

# Exit SSH
exit
```

---

### Step 2: Create Database in Forge

1. **In Forge Dashboard** ’ Navigate to your server
2. **Click "Database" tab**
3. **Create new database**:
   - **Name**: `fittrack_br`
   - **User**: `fittrack_user` (auto-created)
   - **Password**: (auto-generated, copy this!)

4. **Enable PostGIS extension**:

```bash
# SSH into server
ssh forge@YOUR_SERVER_IP

# Connect to database
psql -U forge -d fittrack_br

# Enable PostGIS
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS postgis_topology;

# Verify installation
SELECT PostGIS_version();
# Expected: 3.4.x

# Exit psql
\q

# Exit SSH
exit
```

---

### Step 3: Configure Redis

Laravel Forge automatically installs Redis. Verify:

```bash
# SSH into server
ssh forge@YOUR_SERVER_IP

# Check Redis status
sudo systemctl status redis
# Expected: active (running)

# Test Redis
redis-cli ping
# Expected: PONG

# Exit SSH
exit
```

---

## < Part 3: Site Setup (15 minutes)

### Step 1: Create Site in Forge

1. **In Forge** ’ Click "Sites" tab ’ "New Site"
2. **Configure site**:

| Setting | Value |
|---------|-------|
| **Root Domain** | `api.fittrackbr.com` |
| **Aliases** | (leave empty) |
| **Project Type** | General PHP / Laravel |
| **Web Directory** | `/public` |
| **PHP Version** | PHP 8.4 |

3. **Click "Add Site"**

---

### Step 2: Install Git Repository

1. **In site settings** ’ Click "Git Repository"
2. **Configure**:
   - **Provider**: GitHub
   - **Repository**: `your-username/fittrack-backend`
   - **Branch**: `production`
   - **Deploy Script**: (see below)

3. **Deploy Script** (auto-generated, but customize):

```bash
cd /home/forge/api.fittrackbr.com

# Enable maintenance mode
php artisan down --retry=60 || true

# Pull latest changes
git pull origin production

# Install dependencies (no dev packages)
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Run migrations (--force for production)
php artisan migrate --force

# Clear and cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart queue workers
php artisan queue:restart

# Disable maintenance mode
php artisan up
```

4. **Click "Install Repository"**
5. **Forge will run initial deployment** (may take 2-3 minutes)

---

### Step 3: Configure Environment Variables

1. **In site settings** ’ Click "Environment"
2. **Edit `.env` file** with production values:

```env
# Application
APP_NAME="FitTrack BR"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATE_THIS_WITH_php_artisan_key:generate
APP_URL=https://api.fittrackbr.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fittrack_br
DB_USERNAME=fittrack_user
DB_PASSWORD=YOUR_FORGE_GENERATED_PASSWORD

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis

# Queue
QUEUE_CONNECTION=redis

# Cache
CACHE_STORE=redis
SESSION_DRIVER=redis

# Sentry (Error Tracking)
SENTRY_LARAVEL_DSN=https://YOUR_SENTRY_DSN@sentry.io/YOUR_PROJECT_ID
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.1

# Mail (Example: Mailgun)
MAIL_MAILER=mailgun
MAIL_FROM_ADDRESS=noreply@fittrackbr.com
MAIL_FROM_NAME="${APP_NAME}"
MAILGUN_DOMAIN=mg.fittrackbr.com
MAILGUN_SECRET=YOUR_MAILGUN_SECRET

# Telescope (MUST be false in production!)
TELESCOPE_ENABLED=false

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error
```

3. **Click "Save"**

4. **Generate APP_KEY** (if not set):

```bash
# SSH into server
ssh forge@YOUR_SERVER_IP
cd /home/forge/api.fittrackbr.com

# Generate key
php artisan key:generate --force

# Exit SSH
exit
```

---

## = Part 4: SSL & Domain (10 minutes)

### Step 1: Configure DNS

Point your domain to Forge server:

| Type | Name | Value | TTL |
|------|------|-------|-----|
| **A** | `api` | `YOUR_SERVER_IP` | 3600 |

**Wait 5-10 minutes** for DNS propagation.

Verify DNS:
```bash
# Check if DNS is propagated
nslookup api.fittrackbr.com
# Should return YOUR_SERVER_IP
```

---

### Step 2: Install SSL Certificate (Let's Encrypt)

1. **In site settings** ’ Click "SSL"
2. **Choose "LetsEncrypt"**
3. **Domain**: `api.fittrackbr.com`
4. **Click "Obtain Certificate"**
5. **Wait 1-2 minutes** for certificate generation

6. **Verify SSL**:
   - Visit: `https://api.fittrackbr.com`
   - Should show "= Secure" in browser

---

## ™ Part 5: Queue Workers (10 minutes)

### Step 1: Create Queue Worker

1. **In server dashboard** ’ Click "Daemons"
2. **Create new daemon**:

| Setting | Value |
|---------|-------|
| **Command** | `php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600` |
| **User** | `forge` |
| **Directory** | `/home/forge/api.fittrackbr.com` |
| **Processes** | `2` (increase if high traffic) |

3. **Click "Create Daemon"**
4. **Forge automatically creates Supervisor config**

---

### Step 2: Verify Queue Worker

```bash
# SSH into server
ssh forge@YOUR_SERVER_IP

# Check Supervisor status
sudo supervisorctl status
# Expected: fittrack-worker:fittrack-worker_00 RUNNING

# Check queue manually
cd /home/forge/api.fittrackbr.com
php artisan queue:monitor

# Exit SSH
exit
```

---

## = Part 6: Scheduled Tasks (Cron)

### Step 1: Configure Scheduler

1. **In server dashboard** ’ Click "Scheduler"
2. **Add new scheduled job**:

| Setting | Value |
|---------|-------|
| **Command** | `php artisan schedule:run` |
| **User** | `forge` |
| **Frequency** | Every Minute |

3. **Click "Schedule Job"**

---

### Step 2: Verify Scheduler

```bash
# SSH into server
ssh forge@YOUR_SERVER_IP

# Check cron
crontab -l | grep schedule:run
# Expected: * * * * * cd /home/forge/api.fittrackbr.com && php artisan schedule:run

# Test scheduler
cd /home/forge/api.fittrackbr.com
php artisan schedule:list

# Exit SSH
exit
```

---

##  Part 7: Post-Deployment Verification (10 minutes)

### Step 1: Health Checks

```bash
# Basic health check
curl -I https://api.fittrackbr.com/api/health
# Expected: HTTP/2 200

# Detailed health check
curl https://api.fittrackbr.com/api/health/detailed | jq
# Expected: JSON with app version, PHP version, Laravel version

# Readiness check (database + redis + sentry)
curl https://api.fittrackbr.com/api/health/ready | jq
# Expected: {"status":"ready","checks":{"database":true,"redis":true,"sentry":true}}
```

**If any check fails**, see troubleshooting section below.

---

### Step 2: Test Critical Endpoints

```bash
# 1. User Registration
curl -X POST https://api.fittrackbr.com/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
# Expected: 201 Created with user data

# 2. User Login
curl -X POST https://api.fittrackbr.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
# Expected: 200 OK with token

# 3. List Activities (authenticated)
curl https://api.fittrackbr.com/api/v1/activities \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
# Expected: 200 OK with activities array
```

---

### Step 3: Monitor Logs (15 minutes)

```bash
# SSH into server
ssh forge@YOUR_SERVER_IP
cd /home/forge/api.fittrackbr.com

# Watch Laravel logs
tail -f storage/logs/laravel.log

# In another terminal, watch Nginx access logs
sudo tail -f /var/log/nginx/api.fittrackbr.com-access.log

# In another terminal, watch Nginx error logs
sudo tail -f /var/log/nginx/api.fittrackbr.com-error.log
```

**Look for**:
- L PHP errors/exceptions
- L Database connection errors
- L Redis connection errors
- L Queue job failures
-  Successful API requests

**If errors appear**, check Sentry dashboard immediately.

---

### Step 4: Verify Sentry Integration

1. **Visit Sentry**: https://sentry.io/organizations/YOUR_ORG/issues/
2. **Filter by**: `environment:production`, `last 15 minutes`
3. **Expected**: Zero new errors 

If errors exist, investigate stack traces and fix immediately.

---

## =' Part 8: Performance Optimization

### Step 1: OPcache Configuration

```bash
# SSH into server
ssh forge@YOUR_SERVER_IP

# Edit PHP-FPM config
sudo nano /etc/php/8.4/fpm/conf.d/opcache.ini
```

**Recommended OPcache settings**:
```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

**Restart PHP-FPM**:
```bash
sudo systemctl restart php8.4-fpm
exit
```

---

### Step 2: Nginx Configuration

Forge uses optimized Nginx config by default. To customize:

1. **In site settings** ’ Click "Files" ’ "Edit Nginx Configuration"
2. **Add rate limiting** (if needed):

```nginx
# Add to server block
limit_req_zone $binary_remote_addr zone=api_limit:10m rate=10r/s;

location /api/ {
    limit_req zone=api_limit burst=20 nodelay;
    try_files $uri $uri/ /index.php?$query_string;
}
```

3. **Click "Save"**

---

## =¨ Troubleshooting

### Database Connection Failed

**Symptom**: `/health/ready` returns `"database": false`

**Fix**:
```bash
# SSH into server
ssh forge@YOUR_SERVER_IP
cd /home/forge/api.fittrackbr.com

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
# If error, check .env credentials

# Verify PostgreSQL is running
sudo systemctl status postgresql
# If not running: sudo systemctl start postgresql

# Check PostGIS extension
psql -U fittrack_user -d fittrack_br -c "SELECT PostGIS_version();"
# If error: Re-run CREATE EXTENSION postgis;

exit
```

---

### Redis Connection Failed

**Symptom**: `/health/ready` returns `"redis": false`

**Fix**:
```bash
# SSH into server
ssh forge@YOUR_SERVER_IP

# Check Redis status
sudo systemctl status redis
# If not running: sudo systemctl start redis

# Test Redis connection
redis-cli ping
# Expected: PONG

# Clear Redis cache
redis-cli FLUSHALL

exit
```

---

### 500 Internal Server Error

**Symptom**: All endpoints return 500

**Fix**:
```bash
# SSH into server
ssh forge@YOUR_SERVER_IP
cd /home/forge/api.fittrackbr.com

# Check Laravel logs
tail -50 storage/logs/laravel.log

# Common causes:
# 1. Missing APP_KEY
php artisan key:generate --force

# 2. Cached config with wrong env vars
php artisan config:clear
php artisan config:cache

# 3. Wrong file permissions
sudo chown -R forge:forge storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

exit
```

---

### Queue Jobs Not Processing

**Symptom**: Jobs stuck in `jobs` table

**Fix**:
```bash
# SSH into server
ssh forge@YOUR_SERVER_IP

# Check Supervisor status
sudo supervisorctl status
# If not running: sudo supervisorctl start fittrack-worker:*

# Restart all workers
sudo supervisorctl restart fittrack-worker:*

# Check failed jobs
cd /home/forge/api.fittrackbr.com
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

exit
```

---

## = Deploying Updates

### Option 1: Auto-Deploy on Push (Recommended)

1. **In site settings** ’ Click "Apps"
2. **Enable "Quick Deploy"**
3. **Push to `production` branch** ’ Forge auto-deploys

---

### Option 2: Manual Deploy

1. **In site settings** ’ Click "Deployments"
2. **Click "Deploy Now"**
3. **Wait for deployment to complete**

---

### Option 3: Deploy via CLI

```bash
# Install Forge CLI
composer global require laravel/forge-cli

# Authenticate
forge login

# Deploy site
forge deploy api.fittrackbr.com
```

---

## =Ê Monitoring Setup

### 1. Forge Monitoring (Built-in)

Forge automatically monitors:
- Server CPU usage
- Memory usage
- Disk space
- Failed queue jobs

**Alerts**: Configure in Forge ’ Server ’ Monitoring

---

### 2. Sentry Alerts

Configure Sentry to alert on:
- Any error in production
- Performance degradation (P95 > 1s)
- High error rate (> 10 errors/min)

**Integrations**: Slack, Email, PagerDuty

---

### 3. Uptime Monitoring (External)

**Recommended**: UptimeRobot (https://uptimerobot.com)

**Setup**:
1. Add monitor for `https://api.fittrackbr.com/api/health`
2. Check interval: 1 minute
3. Alert on: Status ` 200, Response time > 5s
4. Notification: Email, Slack, SMS

---

## = Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] `TELESCOPE_ENABLED=false`
- [ ] SSL certificate installed and valid
- [ ] Database credentials secure (not default)
- [ ] Redis password set (if public)
- [ ] API rate limiting enabled
- [ ] `/metrics` endpoint secured (TODO - see deployment.md)
- [ ] Firewall configured (Forge default: SSH, HTTP, HTTPS only)
- [ ] Server updates automated (`sudo unattended-upgrades`)
- [ ] Backups enabled (Forge ’ Server ’ Backups)

---

## =¾ Backup Configuration

### Daily Database Backups (Forge)

1. **In server dashboard** ’ Click "Backups"
2. **Configure**:
   - **Provider**: DigitalOcean Spaces (or S3)
   - **Frequency**: Daily at 2:00 AM (low traffic)
   - **Retention**: 7 days
3. **Click "Enable Backups"**

---

### Manual Backup

```bash
# SSH into server
ssh forge@YOUR_SERVER_IP

# Create backup
pg_dump -U fittrack_user -h localhost fittrack_br > backup_$(date +%Y%m%d_%H%M%S).sql

# Download backup to local machine (from local terminal)
scp forge@YOUR_SERVER_IP:/home/forge/backup_*.sql ./backups/

# Verify backup file
ls -lh backups/backup_*.sql
```

---

## =Ý Deployment Checklist

**Before Every Deployment**:
- [ ] All tests passing locally (`php artisan test`)
- [ ] PHPStan Level 5: 0 errors (`vendor/bin/phpstan analyse`)
- [ ] Code formatted (`vendor/bin/pint`)
- [ ] Security audit clean (`composer audit`)
- [ ] `.env.production.example` updated
- [ ] Database migration tested in staging
- [ ] Changelog/release notes updated

**During Deployment**:
- [ ] Maintenance mode enabled (`php artisan down`)
- [ ] Code pulled from `production` branch
- [ ] Dependencies installed (`composer install --no-dev`)
- [ ] Migrations run (`php artisan migrate --force`)
- [ ] Configs cached
- [ ] Queue workers restarted
- [ ] Maintenance mode disabled (`php artisan up`)

**After Deployment**:
- [ ] Health checks passing
- [ ] Critical endpoints tested
- [ ] Logs monitored (15 minutes)
- [ ] Sentry: Zero new errors
- [ ] Queue jobs processing
- [ ] Performance acceptable (< 200ms)

---

## <¯ Summary

### Server Stack
- **OS**: Ubuntu 22.04 LTS
- **Web Server**: Nginx 1.24
- **PHP**: 8.4.14 with OPcache
- **Database**: PostgreSQL 16 + PostGIS 3.4
- **Cache/Queue**: Redis 7
- **Process Manager**: Supervisor
- **SSL**: Let's Encrypt (auto-renew)

### Estimated Costs (DigitalOcean)
- **Server (2GB)**: $12/month
- **Backups**: $2-5/month
- **Bandwidth**: Included (1TB)
- **Total**: ~$15-20/month

### Support
- **Laravel Forge Docs**: https://forge.laravel.com/docs
- **DigitalOcean Docs**: https://docs.digitalocean.com
- **Sentry Docs**: https://docs.sentry.io

---

**Deployment Time**: ~45-60 minutes
**Last Updated**: 2025-11-10
**Next Review**: After first production deployment
