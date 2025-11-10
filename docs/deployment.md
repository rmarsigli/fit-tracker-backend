# FitTrack BR - Deployment Checklist

**Version**: 1.0.0
**Last Updated**: 2025-11-10
**Target Environment**: Production (Linux/Ubuntu)

---

## 📋 Pre-Deployment Checklist

Run these checks **before deploying** to ensure the application is ready:

### 1. Configuration Validation

```bash
# Validate all required environment variables
php artisan config:validate
```

**Expected Output**: `✅ All configuration validated successfully!`

**If validation fails**: Fix missing environment variables in `.env` before proceeding.

---

### 2. Run Full Test Suite

```bash
# Run all tests (must pass 100%)
php artisan test
```

**Expected Output**: `Tests: 218 passed (656 assertions)`

**If tests fail**: Fix failing tests before deploying. DO NOT deploy with failing tests.

---

### 3. Security Audit

```bash
# Check for known vulnerabilities in dependencies
composer audit
```

**Expected Output**: `No security vulnerability advisories found`

**If vulnerabilities found**: Run `composer update` to patch, then re-run tests.

---

### 4. Code Formatting

```bash
# Format code according to Laravel Pint rules
vendor/bin/pint
```

**Expected Output**: `✨ [0 files] changed`

**If files changed**: Commit formatted files, re-run tests, then proceed.

---

### 5. Clear Application Caches

```bash
# Clear all caches before deployment
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

### 6. Database Backup

**CRITICAL**: Always backup the database before deployment.

```bash
# PostgreSQL backup
pg_dump -U postgres -h localhost fittrack_br > backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup file exists and is not empty
ls -lh backup_*.sql
```

**Store backup securely** before proceeding.

---

## 🚀 Deployment Steps

Follow these steps **in order** during deployment:

### 1. Enable Maintenance Mode

```bash
# Put application in maintenance mode
php artisan down --retry=60
```

**Users will see**: "Service Unavailable" (503) with retry-after header.

---

### 2. Pull Latest Code

```bash
# Pull from production branch
git fetch origin
git checkout production
git pull origin production

# Verify correct commit
git log -1 --oneline
```

---

### 3. Install Dependencies

```bash
# Install production dependencies (no dev packages)
composer install --no-dev --optimize-autoloader --no-interaction

# Clear composer cache if needed
composer clear-cache
```

---

### 4. Run Database Migrations

```bash
# Run migrations (with --force for production)
php artisan migrate --force

# If migrations fail, ROLLBACK immediately (see rollback section)
```

**⚠️ WARNING**: Test migrations in staging first. Never deploy untested migrations.

---

### 5. Cache Configuration

```bash
# Cache configuration for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

**NOTE**: After caching, `env()` calls outside `config/` will return `null`.

---

### 6. Restart Queue Workers

```bash
# Gracefully restart queue workers (Supervisor)
php artisan queue:restart

# If using Supervisor, reload config
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart fittrack-worker:*
```

---

### 7. Restart PHP-FPM / Octane

**For PHP-FPM**:
```bash
sudo systemctl restart php8.4-fpm
```

**For Laravel Octane**:
```bash
php artisan octane:reload
```

---

### 8. Disable Maintenance Mode

```bash
# Bring application back online
php artisan up
```

---

## ✅ Post-Deployment Verification

Run these checks **immediately after deployment**:

### 1. Health Checks

```bash
# Basic health check (should return 200)
curl -I https://api.fittrackbr.com/api/health

# Readiness check (should return 200 with all checks passing)
curl https://api.fittrackbr.com/api/health/ready | jq

# Detailed health (should show versions)
curl https://api.fittrackbr.com/api/health/detailed | jq
```

**Expected**:
- `/health` → `200 OK`
- `/health/ready` → `{"status":"ready","checks":{"database":true,"redis":true,"sentry":true}}`

**If any check fails**: Investigate immediately (see troubleshooting section).

---

### 2. Smoke Tests (Critical Endpoints)

Test the most important endpoints manually:

```bash
# 1. User Registration
curl -X POST https://api.fittrackbr.com/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# 2. User Login
curl -X POST https://api.fittrackbr.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'

# 3. List Activities (with auth token from login)
curl https://api.fittrackbr.com/api/v1/activities \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

**Expected**: All endpoints return valid JSON responses (200, 201, etc.)

---

### 3. Monitor Logs (15 minutes)

```bash
# Watch Laravel logs for errors
tail -f storage/logs/laravel.log

# Watch PHP-FPM errors
sudo tail -f /var/log/php8.4-fpm.log

# Watch Nginx errors
sudo tail -f /var/log/nginx/error.log
```

**Look for**:
- ❌ PHP errors/exceptions
- ❌ Database connection errors
- ❌ Redis connection errors
- ❌ Queue job failures

**If errors appear**: Check Sentry dashboard for full stack traces.

---

### 4. Verify Queue Processing

```bash
# Check queue workers are running
php artisan queue:monitor

# Check for failed jobs
php artisan queue:failed

# If failed jobs exist, inspect and retry
php artisan queue:retry all
```

---

### 5. Check Sentry

1. Open Sentry dashboard: https://sentry.io/organizations/YOUR_ORG/issues/
2. Filter by: `environment:production`, `last 15 minutes`
3. **Expected**: Zero new errors
4. **If errors exist**: Investigate and fix immediately

---

## 🔄 Rollback Procedure

If deployment fails or critical issues are detected, **rollback immediately**:

### 1. Enable Maintenance Mode

```bash
php artisan down --retry=60
```

---

### 2. Restore Database Backup

```bash
# Find latest backup
ls -lht backup_*.sql | head -1

# Restore backup (DESTRUCTIVE - all recent data lost!)
psql -U postgres -h localhost fittrack_br < backup_YYYYMMDD_HHMMSS.sql
```

---

### 3. Checkout Previous Release

```bash
# Find previous stable commit
git log --oneline -10

# Checkout previous release
git checkout PREVIOUS_COMMIT_SHA

# Or revert to previous tag
git checkout v1.2.3
```

---

### 4. Reinstall Dependencies

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

---

### 5. Rollback Migrations (if needed)

```bash
# Rollback last batch of migrations
php artisan migrate:rollback --force

# Or rollback specific migration
php artisan migrate:rollback --path=database/migrations/2025_11_10_*.php --force
```

---

### 6. Clear All Caches

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

### 7. Restart Services

```bash
php artisan queue:restart
sudo systemctl restart php8.4-fpm
```

---

### 8. Verify Health and Re-enable

```bash
# Check health
curl https://api.fittrackbr.com/api/health/ready | jq

# If healthy, bring back online
php artisan up
```

---

### 9. Notify Team

Immediately notify team via Slack/email:

```
🚨 ROLLBACK EXECUTED
Environment: Production
Time: 2025-11-10 14:30:00 UTC
Reason: [Brief explanation]
Status: Application restored to previous version
Action Required: Investigate root cause before next deployment
```

---

## 🐛 Troubleshooting

### Database Connection Failed

**Symptom**: `/health/ready` returns `"database": false`

**Fix**:
1. Check database credentials in `.env`
2. Verify PostgreSQL is running: `sudo systemctl status postgresql`
3. Test connection: `psql -U postgres -h localhost -d fittrack_br`
4. Check firewall rules if database is remote

---

### Redis Connection Failed

**Symptom**: `/health/ready` returns `"redis": false`

**Fix**:
1. Check Redis credentials in `.env`
2. Verify Redis is running: `sudo systemctl status redis`
3. Test connection: `redis-cli ping` (should return `PONG`)
4. Clear Redis cache: `redis-cli FLUSHALL` (careful in production!)

---

### 500 Internal Server Error

**Symptom**: All endpoints return 500

**Fix**:
1. Check Laravel logs: `tail -50 storage/logs/laravel.log`
2. Check PHP-FPM logs: `sudo tail -50 /var/log/php8.4-fpm.log`
3. Check Sentry dashboard for stack trace
4. Common causes:
   - Missing `APP_KEY`: Run `php artisan key:generate`
   - Incorrect permissions: `sudo chown -R www-data:www-data storage bootstrap/cache`
   - Cached config with missing env vars: `php artisan config:clear`

---

### Queue Jobs Not Processing

**Symptom**: Jobs stuck in `jobs` table, not processing

**Fix**:
1. Check queue workers: `php artisan queue:monitor`
2. Restart workers: `php artisan queue:restart`
3. Check Supervisor status: `sudo supervisorctl status`
4. Manually process jobs: `php artisan queue:work --once`
5. Check failed jobs: `php artisan queue:failed`

---

## 📊 Monitoring Recommendations

### Uptime Monitoring

Set up external uptime monitoring (e.g., UptimeRobot, Pingdom):

- **Endpoint**: `https://api.fittrackbr.com/api/health`
- **Interval**: 1 minute
- **Alert on**: Status ≠ 200, Response time > 5 seconds

---

### Sentry Alerts

Configure Sentry to alert on:
- Any error in production
- Performance degradation (P95 > 1 second)
- High error rate (> 10 errors/minute)

**Integrations**: Slack, PagerDuty, Email

---

### Server Monitoring

Monitor server resources:
- CPU usage (alert if > 80% for 5 minutes)
- Memory usage (alert if > 85%)
- Disk space (alert if < 10% free)
- Database connections (alert if > 90% of max)

**Tools**: Datadog, New Relic, CloudWatch, or simple cron + email

---

## 🔐 Security Notes

### Environment Variables

**NEVER commit** `.env` to version control. Store securely in:
- AWS Secrets Manager
- HashiCorp Vault
- Encrypted config management

---

### Telescope in Production

**CRITICAL**: Telescope is disabled in production by default.

Verify in `config/telescope.php`:
```php
'enabled' => env('TELESCOPE_ENABLED', env('APP_ENV') === 'local'),
```

And in `.env`:
```
TELESCOPE_ENABLED=false
```

**NEVER set** `TELESCOPE_ENABLED=true` in production!

---

### Metrics Endpoint Security

The `/metrics` endpoint currently has **no authentication**.

**TODO for production**: Add authentication middleware.

**Option 1** - Basic Auth:
```php
Route::get('metrics', [MetricsController::class, 'index'])
    ->middleware('auth.basic');
```

**Option 2** - Token-based:
```php
Route::get('metrics', [MetricsController::class, 'index'])
    ->middleware('metrics.token');
```

Create middleware to check `METRICS_TOKEN` header matches `.env` value.

---

## 📞 Support Contacts

**On-Call Engineer**: +55 (XX) XXXXX-XXXX
**Slack Channel**: #fittrack-production-alerts
**Sentry**: https://sentry.io/organizations/fittrack/issues/
**Status Page**: https://status.fittrackbr.com

---

## 📝 Deployment Log Template

Document every deployment in `docs/deployment-log.md`:

```markdown
## Deployment - 2025-11-10 14:00 UTC

**Version**: v1.3.0
**Deployed By**: John Doe
**Duration**: 5 minutes
**Downtime**: 2 minutes

### Changes
- Added health check endpoints
- Implemented metrics endpoint
- Upgraded Laravel to 12.37.0

### Pre-Deployment
- ✅ Tests passed (218/218)
- ✅ Security audit clean
- ✅ Database backup created
- ✅ Code formatted with Pint

### Post-Deployment
- ✅ Health checks passing
- ✅ Smoke tests successful
- ✅ No Sentry errors (15 min)
- ✅ Queue processing normally

### Issues
- None

### Rollback Required
- No
```

---

## 🎯 Summary

**Before Deploy**:
1. ✅ `php artisan config:validate`
2. ✅ `php artisan test`
3. ✅ `composer audit`
4. ✅ `vendor/bin/pint`
5. ✅ Database backup

**Deploy**:
1. ✅ Maintenance mode ON
2. ✅ Pull code
3. ✅ `composer install --no-dev`
4. ✅ `php artisan migrate --force`
5. ✅ Cache configs
6. ✅ Restart queue workers
7. ✅ Restart PHP-FPM
8. ✅ Maintenance mode OFF

**After Deploy**:
1. ✅ Health checks (all passing)
2. ✅ Smoke tests (critical endpoints)
3. ✅ Monitor logs (15 minutes)
4. ✅ Check Sentry (zero errors)

**If anything fails**: ROLLBACK immediately!

---

**Last Updated**: 2025-11-10
**Next Review**: Before next major release
