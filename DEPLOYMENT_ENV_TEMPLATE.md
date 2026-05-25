# .ENV TEMPLATE UNTUK DEPLOYMENT

## Gunakan template ini ketika di server baru:

```env
APP_NAME=Swara
APP_ENV=production
APP_KEY=base64:4sy7Nd9O4Hx2uiXFlbzS6mCHa676k83vXSvB1N8H+7c=
APP_DEBUG=false
APP_URL=https://DOMAIN_ANDA_DISINI.com

APP_LOCALE=id
APP_FALLBACK_LOCALE=id
APP_FAKER_LOCALE=id_ID

BCRYPT_ROUNDS=12

# ========== DATABASE ==========
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=swaradb
DB_USERNAME=swarauser
DB_PASSWORD=PASSWORD_YANG_KUAT_DISINI

# ========== LOGGING & DEBUG ==========
LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

# ========== SESSION ==========
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# ========== BROADCASTING & QUEUE ==========
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database
CACHE_STORE=file

# ========== REDIS (optional, untuk cache/session) ==========
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ========== MAIL ==========
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=USERNAME_MAILTRAP
MAIL_PASSWORD=PASSWORD_MAILTRAP
MAIL_FROM_ADDRESS="noreply@domain-anda.com"
MAIL_FROM_NAME="${APP_NAME}"

# Optional: Jika menggunakan Mailgun
# MAIL_MAILER=mailgun
# MAILGUN_SECRET=YOUR_MAILGUN_SECRET
# MAILGUN_DOMAIN=YOUR_MAILGUN_DOMAIN

# ========== AWS (jika pakai storage S3) ==========
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# ========== FILESYSTEM ==========
FILESYSTEM_DISK=local

# ========== REVERB WEBSOCKET ==========
REVERB_APP_ID=12345
REVERB_APP_KEY=laravel-reverb-key-prod-ubah-ini
REVERB_APP_SECRET=laravel-reverb-secret-prod-ubah-ini
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_APP_URL=https://DOMAIN_ANDA_DISINI.com

# ========== VITE ==========
VITE_APP_NAME="${APP_NAME}"
VITE_API_URL=https://DOMAIN_ANDA_DISINI.com

# ========== FIREBASE (untuk FCM Push Notification) ==========
FCM_PROJECT_ID=swara-cd4b2
FCM_SERVICE_ACCOUNT_JSON=/var/www/laravel-swara/storage/app/firebase/swara-cd4b2-firebase-adminsdk-fbsvc-86ca24faf6.json

# ========== MAINTENANCE MODE ==========
APP_MAINTENANCE_DRIVER=file
```

---

## 🔧 LANGKAH PERSIAPAN DI LOCAL (SEBELUM DEPLOY)

### 1. Pastikan npm dependencies ter-install di local:
```bash
npm install
npm run build
```

### 2. Test seluruh fitur di local:
```bash
# Jalankan migrations test
php artisan migrate:status

# Test routes
php artisan route:list | grep api

# Test config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Prepare Firebase JSON file:
- Pastikan file ada di: `storage/app/firebase/`
- Nama file: `swara-cd4b2-firebase-adminsdk-fbsvc-86ca24faf6.json`

### 4. Create .env.production (optional, untuk reference):
```bash
cp .env .env.production
```

---

## ⚡ QUICK DEPLOYMENT CHECKLIST

Jalankan ini sebelum push ke production:

```bash
# 1. Clear caches
php artisan config:clear
php artisan cache:clear

# 2. Optimize untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Check migrations
php artisan migrate:status

# 4. Rebuild npm assets
npm install
npm run build

# 5. Verify all files
find app -name "*.php" -type f | wc -l
```

---

## 📋 DI SERVER BARU (Post-Deployment Testing)

```bash
# 1. Verify database
php artisan db:show

# 2. Test migrations
php artisan migrate:status

# 3. Seed data (jika perlu)
php artisan db:seed

# 4. Check artisan commands
php artisan list

# 5. Test API route
php artisan tinker
>>> DB::table('users')->count()
>>> exit()
```

**Catatan:** Ganti semua placeholder (DOMAIN_ANDA_DISINI, PASSWORD_YANG_KUAT_DISINI, dll) dengan nilai real Anda.
