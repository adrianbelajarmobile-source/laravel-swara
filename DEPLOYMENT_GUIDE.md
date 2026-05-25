# 🚀 PANDUAN DEPLOYMENT LARAVEL-SWARA KE DROPLET BARU

## ⚠️ PERSIAPAN AWAL
- Sudah membuat Droplet di DigitalOcean (OS: Ubuntu 22.04 atau 24.04 LTS)
- SSH Key sudah terhubung ke Droplet
- Domain sudah menunjuk ke IP Droplet (opsional, bisa pakai IP)

---

## 📋 LANGKAH 1: KONEKSI KE SERVER

```bash
# SSH ke Droplet Anda (ganti IP_DROPLET dengan IP Anda)
ssh root@IP_DROPLET

# Contoh:
# ssh root@167.99.123.45
```

---

## 📋 LANGKAH 2: UPDATE SISTEM

```bash
apt update
apt upgrade -y
```

---

## 📋 LANGKAH 3: INSTALL DEPENDENCIES (PHP & Database)

### A. Setup firewall
```bash
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

### B. Install PHP 8.2 + Extensions
```bash
apt install -y php8.2 \
  php8.2-fpm \
  php8.2-cli \
  php8.2-pgsql \
  php8.2-curl \
  php8.2-mbstring \
  php8.2-xml \
  php8.2-zip \
  php8.2-gd \
  php8.2-bcmath \
  php8.2-fileinfo
```

### C. Install PostgreSQL
```bash
apt install -y postgresql postgresql-contrib

# Mulai PostgreSQL service
systemctl start postgresql
systemctl enable postgresql
```

### D. Setup PostgreSQL Database
```bash
# Login ke PostgreSQL
sudo -u postgres psql

# Jalankan perintah berikut di PostgreSQL shell:
CREATE USER swarauser WITH PASSWORD 'GantiDenganPasswordKuat123!';
CREATE DATABASE swaradb OWNER swarauser;
ALTER ROLE swarauser SET client_encoding TO 'utf8';
ALTER ROLE swarauser SET default_transaction_isolation TO 'read committed';
ALTER ROLE swarauser SET default_transaction_deferrable TO on;
ALTER ROLE swarauser SET default_transaction_level TO 'read committed';
GRANT ALL PRIVILEGES ON DATABASE swaradb TO swarauser;
\q
```

### E. Install Composer
```bash
apt install -y composer
```

### F. Install Node.js & npm
```bash
apt install -y nodejs npm
```

### G. Install Nginx
```bash
apt install -y nginx
systemctl start nginx
systemctl enable nginx
```

---

## 📋 LANGKAH 4: SETUP PROJECT

### A. Clone Project dari GitHub
```bash
cd /var/www
git clone https://github.com/ANDA/laravel-swara.git
cd laravel-swara
```

### B. Setup File Permission
```bash
chown -R www-data:www-data /var/www/laravel-swara
chmod -R 755 /var/www/laravel-swara
chmod -R 775 /var/www/laravel-swara/storage
chmod -R 775 /var/www/laravel-swara/bootstrap/cache
```

### C. Install PHP Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### D. Setup Environment File
```bash
# Copy dari .env.example atau buat baru
cp .env.example .env

# Edit .env untuk production:
nano .env
```

**Isi .env untuk Production:**
```env
APP_NAME=Swara
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=swaradb
DB_USERNAME=swarauser
DB_PASSWORD=GantiDenganPasswordKuat123!

LOG_CHANNEL=stack
LOG_LEVEL=info

# Queue (biarkan dengan database)
QUEUE_CONNECTION=database

# Cache & Session
CACHE_STORE=file
SESSION_DRIVER=file

# Reverb WebSocket
REVERB_APP_ID=12345
REVERB_APP_KEY=laravel-reverb-key
REVERB_APP_SECRET=laravel-reverb-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http

# Mail (gunakan Mailgun atau SMTP real)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2465
MAIL_USERNAME=username_anda
MAIL_PASSWORD=password_anda

# Firebase (sesuaikan path)
FCM_PROJECT_ID=swara-cd4b2
FCM_SERVICE_ACCOUNT_JSON=/var/www/laravel-swara/storage/app/firebase/swara-cd4b2-firebase-adminsdk-fbsvc-86ca24faf6.json
```

**Tekan Ctrl+X, lalu Y, lalu Enter untuk save**

### E. Generate App Key
```bash
php artisan key:generate
```

### F. Jalankan Database Migration
```bash
php artisan migrate --force
```

### G. Install NPM Dependencies
```bash
npm install
npm run build
```

### H. Cache Config
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📋 LANGKAH 5: SETUP NGINX

### A. Buat Nginx Config
```bash
nano /etc/nginx/sites-available/laravel-swara
```

**Paste konfigurasi ini:**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name domain-anda.com www.domain-anda.com;

    root /var/www/laravel-swara/public;
    index index.php index.html index.htm;

    # Redirect HTTP ke HTTPS (opsional, tapi recommended)
    # return 301 https://$server_name$request_uri;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    # Cache assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Disable access to certain files
    location ~ /\.env {
        deny all;
    }

    location ~ /storage/ {
        deny all;
    }
}
```

### B. Enable Nginx Config
```bash
ln -s /etc/nginx/sites-available/laravel-swara /etc/nginx/sites-enabled/

# Hapus default config
rm /etc/nginx/sites-enabled/default

# Test Nginx config
nginx -t

# Restart Nginx
systemctl restart nginx
```

---

## 📋 LANGKAH 6: SETUP SSL CERTIFICATE (HTTPS)

```bash
# Install Certbot
apt install -y certbot python3-certbot-nginx

# Generate SSL Certificate (ganti domain-anda.com)
certbot certonly --nginx -d domain-anda.com -d www.domain-anda.com

# Update Nginx config untuk HTTPS
nano /etc/nginx/sites-available/laravel-swara
```

**Update server block ke ini:**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name domain-anda.com www.domain-anda.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name domain-anda.com www.domain-anda.com;

    # SSL Certificate
    ssl_certificate /etc/letsencrypt/live/domain-anda.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/domain-anda.com/privkey.pem;

    root /var/www/laravel-swara/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\.env {
        deny all;
    }

    location ~ /storage/ {
        deny all;
    }
}
```

**Test dan restart:**
```bash
nginx -t
systemctl restart nginx
```

---

## 📋 LANGKAH 7: SETUP BACKGROUND JOBS (QUEUE)

### A. Buat Systemd Service
```bash
nano /etc/systemd/system/laravel-swara-queue.service
```

**Paste ini:**
```ini
[Unit]
Description=Laravel Swara Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/laravel-swara
ExecStart=/usr/bin/php /var/www/laravel-swara/artisan queue:work --tries=3 --timeout=60
Restart=on-failure
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

**Enable dan jalankan:**
```bash
systemctl daemon-reload
systemctl enable laravel-swara-queue
systemctl start laravel-swara-queue
systemctl status laravel-swara-queue
```

---

## 📋 LANGKAH 8: SETUP REVERB WEBSOCKET (OPSIONAL)

### A. Buat Service
```bash
nano /etc/systemd/system/laravel-reverb.service
```

**Paste ini:**
```ini
[Unit]
Description=Laravel Reverb WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/laravel-swara
ExecStart=/usr/bin/php /var/www/laravel-swara/artisan reverb:start --host=0.0.0.0 --port=8080
Restart=on-failure
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

**Enable dan jalankan:**
```bash
systemctl daemon-reload
systemctl enable laravel-reverb
systemctl start laravel-reverb
```

### B. Setup Nginx Proxy untuk Reverb (Tambahkan ke server block 443)
```nginx
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}

location /ws {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
}
```

---

## 📋 LANGKAH 9: SETUP CRON JOB (SCHEDULER)

```bash
# Edit crontab
crontab -e -u www-data

# Tambahkan ini di akhir file:
* * * * * cd /var/www/laravel-swara && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📋 LANGKAH 10: SETUP AUTO SSL RENEWAL

```bash
# Cron sudah auto-renew Let's Encrypt, verify:
certbot renew --dry-run
```

---

## ✅ TEST DEPLOYMENT

### 1. Test Routes
```bash
curl https://domain-anda.com/

# Atau buka di browser:
# https://domain-anda.com
```

### 2. Test API
```bash
curl -X POST https://domain-anda.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

### 3. Check Service Status
```bash
# PHP-FPM
systemctl status php8.2-fpm

# Nginx
systemctl status nginx

# PostgreSQL
systemctl status postgresql

# Queue Worker
systemctl status laravel-swara-queue

# Reverb (jika aktif)
systemctl status laravel-reverb
```

---

## 🔧 TROUBLESHOOTING

### Jika ERROR Permission Denied:
```bash
chmod -R 755 /var/www/laravel-swara
chown -R www-data:www-data /var/www/laravel-swara/storage
```

### Jika ERROR Database Connection:
```bash
# Verifikasi PostgreSQL user
sudo -u postgres psql -U swarauser -d swaradb -c "SELECT 1;"

# Check .env DB settings
cat /var/www/laravel-swara/.env | grep DB_
```

### Jika Nginx Error:
```bash
# Check error log
tail -f /var/log/nginx/error.log

# Test config
nginx -t

# Restart
systemctl restart nginx
```

### Jika Queue Tidak Berjalan:
```bash
# Restart queue worker
systemctl restart laravel-swara-queue

# Check status
systemctl status laravel-swara-queue

# View logs
journalctl -u laravel-swara-queue -f
```

---

## 📊 CHECKLIST DEPLOYMENT

- [ ] SSH ke Droplet baru
- [ ] Update sistem
- [ ] Install PHP 8.2 dan extensions
- [ ] Install PostgreSQL
- [ ] Setup database dan user
- [ ] Install Composer & Node.js
- [ ] Install Nginx
- [ ] Clone project
- [ ] Setup permissions
- [ ] Install dependencies (composer & npm)
- [ ] Setup .env untuk production
- [ ] Generate app key
- [ ] Migrate database
- [ ] Build assets (npm run build)
- [ ] Setup Nginx config
- [ ] Setup SSL (HTTPS)
- [ ] Setup Queue Worker service
- [ ] Setup Reverb WebSocket (optional)
- [ ] Setup Cron Job
- [ ] Test aplikasi di browser
- [ ] Test API endpoints

---

## 📞 DEBUGGING COMMANDS

```bash
# Cek semua services
systemctl status nginx
systemctl status php8.2-fpm
systemctl status postgresql
systemctl status laravel-swara-queue

# Lihat logs real-time
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
tail -f /var/log/php8.2-fpm.log

# Check storage permissions
ls -la /var/www/laravel-swara/storage/

# Test database connection
php /var/www/laravel-swara/artisan db:show

# Test artisan commands
php /var/www/laravel-swara/artisan tinker
```

**Selamat! Server Anda sudah siap.** 🎉
