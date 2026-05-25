# 🚀 PANDUAN DEPLOYMENT LARAVEL-SWARA (BACKEND ONLY)

## ⚠️ PERSIAPAN AWAL
- Droplet DigitalOcean (OS: Ubuntu 22.04 atau 24.04 LTS)
- SSH Key sudah connected
- Domain menunjuk ke IP Droplet (optional)

---

## 📋 LANGKAH 1: KONEKSI KE SERVER

```bash
ssh root@IP_DROPLET
# Contoh: ssh root@167.99.123.45
```

---

## 📋 LANGKAH 2: UPDATE SISTEM

```bash
apt update
apt upgrade -y
```

---

## 📋 LANGKAH 3: INSTALL DEPENDENCIES

### A. Setup Firewall
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
systemctl start postgresql
systemctl enable postgresql
```

### D. Setup Database
```bash
sudo -u postgres psql

# Di PostgreSQL shell, jalankan:
CREATE USER swarauser WITH PASSWORD 'GantiDenganPasswordKuat123!';
CREATE DATABASE swaradb OWNER swarauser;
ALTER ROLE swarauser SET client_encoding TO 'utf8';
ALTER ROLE swarauser SET default_transaction_isolation TO 'read committed';
GRANT ALL PRIVILEGES ON DATABASE swaradb TO swarauser;
\q
```

### E. Install Composer
```bash
apt install -y composer
```

### F. Install Nginx
```bash
apt install -y nginx
systemctl start nginx
systemctl enable nginx
```

---

## 📋 LANGKAH 4: SETUP PROJECT

### A. Clone Project
```bash
cd /var/www
git clone https://github.com/ANDA/laravel-swara.git
cd laravel-swara
```

### B. Setup Permissions
```bash
chown -R www-data:www-data /var/www/laravel-swara
chmod -R 755 /var/www/laravel-swara
chmod -R 775 /var/www/laravel-swara/storage
chmod -R 775 /var/www/laravel-swara/bootstrap/cache
```

### C. Install PHP Dependencies (NO NPM)
```bash
composer install --no-dev --optimize-autoloader
```

### D. Setup Environment File
```bash
nano .env
```

**Copy-paste ini ke .env:**
```env
APP_NAME=Swara
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:4sy7Nd9O4Hx2uiXFlbzS6mCHa676k83vXSvB1N8H+7c=
APP_URL=https://domain-anda.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=swaradb
DB_USERNAME=swarauser
DB_PASSWORD=GantiDenganPasswordKuat123!

LOG_CHANNEL=stack
LOG_LEVEL=info

QUEUE_CONNECTION=database
CACHE_STORE=file
SESSION_DRIVER=file

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=12345
REVERB_APP_KEY=laravel-reverb-key
REVERB_APP_SECRET=laravel-reverb-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2465
MAIL_USERNAME=username
MAIL_PASSWORD=password

FCM_PROJECT_ID=swara-cd4b2
FCM_SERVICE_ACCOUNT_JSON=/var/www/laravel-swara/storage/app/firebase/swara-cd4b2-firebase-adminsdk-fbsvc-86ca24faf6.json
```

**Tekan Ctrl+X → Y → Enter**

### E. Generate Key & Migrate
```bash
php artisan key:generate
php artisan migrate --force
```

### F. Cache Configuration
```bash
php artisan config:cache
php artisan route:cache
```

---

## 📋 LANGKAH 5: SETUP NGINX

### A. Buat Nginx Config
```bash
nano /etc/nginx/sites-available/laravel-swara
```

**Paste ini:**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name domain-anda.com www.domain-anda.com;

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

    location ~ /\.env {
        deny all;
    }

    location ~ /storage/ {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

### B. Enable & Test
```bash
ln -s /etc/nginx/sites-available/laravel-swara /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
```

---

## 📋 LANGKAH 6: SETUP SSL (HTTPS)

```bash
# Install Certbot
apt install -y certbot python3-certbot-nginx

# Generate SSL
certbot certonly --nginx -d domain-anda.com -d www.domain-anda.com

# Update Nginx untuk HTTPS
nano /etc/nginx/sites-available/laravel-swara
```

**Update jadi:**
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

    location ~ /\.env {
        deny all;
    }

    location ~ /storage/ {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}
```

**Restart:**
```bash
nginx -t
systemctl restart nginx
```

---

## 📋 LANGKAH 7: SETUP QUEUE WORKER

```bash
nano /etc/systemd/system/laravel-swara-queue.service
```

**Paste:**
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

**Enable:**
```bash
systemctl daemon-reload
systemctl enable laravel-swara-queue
systemctl start laravel-swara-queue
```

---

## 📋 LANGKAH 8: SETUP SCHEDULER (CRON)

```bash
crontab -e -u www-data

# Tambahkan ini:
* * * * * cd /var/www/laravel-swara && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📋 LANGKAH 9: SETUP REVERB WEBSOCKET (OPTIONAL)

```bash
nano /etc/systemd/system/laravel-reverb.service
```

**Paste:**
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

**Enable:**
```bash
systemctl daemon-reload
systemctl enable laravel-reverb
systemctl start laravel-reverb
```

**Update Nginx untuk WebSocket (tambahkan ke server block 443):**
```nginx
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
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

## ✅ TEST DEPLOYMENT

```bash
# Test API
curl -X GET https://domain-anda.com/api/user

# Test Database
cd /var/www/laravel-swara
php artisan tinker
>>> DB::table('users')->count()
>>> exit()

# Check Services
systemctl status nginx
systemctl status php8.2-fpm
systemctl status postgresql
systemctl status laravel-swara-queue
systemctl status laravel-reverb
```

---

## 📊 QUICK CHECKLIST

- [ ] SSH ke server
- [ ] Update sistem
- [ ] Install PHP 8.2 & extensions
- [ ] Install PostgreSQL
- [ ] Create database & user
- [ ] Install Composer
- [ ] Install Nginx
- [ ] Clone project
- [ ] Setup permissions
- [ ] Composer install (no npm)
- [ ] Setup .env
- [ ] Generate key
- [ ] Migrate database
- [ ] Cache config
- [ ] Setup Nginx config
- [ ] Setup SSL
- [ ] Setup Queue Worker
- [ ] Setup Cron
- [ ] Setup Reverb (optional)
- [ ] Test aplikasi

---

## 🔧 TROUBLESHOOTING

### Database Connection Error:
```bash
# Check PostgreSQL
sudo -u postgres psql -U swarauser -d swaradb -c "SELECT 1;"

# Check .env
grep DB_ /var/www/laravel-swara/.env
```

### Nginx Error:
```bash
tail -f /var/log/nginx/error.log
nginx -t
systemctl restart nginx
```

### Certificate Generation Error:
```bash
# Check permissions
ls -la /var/www/laravel-swara/storage/

# Rebuild cache
php artisan config:clear
php artisan cache:clear
```

### Queue Not Working:
```bash
systemctl restart laravel-swara-queue
journalctl -u laravel-swara-queue -f
```

---

**Selesai! 🎉 Server Anda siap untuk production certificate generation.**
