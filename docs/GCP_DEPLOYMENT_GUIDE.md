# Panduan Lengkap Deployment VIYGO ke Google Cloud Platform (GCP)

Dokumen ini berisi langkah-langkah komprehensif untuk mendeploy aplikasi VIYGO (Laravel 13) ke instance Compute Engine di Google Cloud Platform (GCP), termasuk konfigurasi server, pointing domain, dan checklist keamanan pra-rilis.

---

## 1. Persiapan Awal (Pre-requisites)
Sebelum memulai, pastikan Anda memiliki:
- **Akun GCP** dengan billing yang sudah aktif.
- **Akses ke Domain Registrar** (misal: Niagahoster, Rumahweb, Cloudflare) untuk pointing DNS.
- **Akses ke repositori Git** VIYGO.
- **Kredensial Midtrans Production** (Server Key & Client Key).

---

## 2. Pembuatan Instance GCP (Compute Engine)

1. Masuk ke **GCP Console** > **Compute Engine** > **VM instances** > **Create Instance**.
2. **Spesifikasi Rekomendasi**:
   - **Name**: `viygo-production`
   - **Region/Zone**: Pilih region terdekat dengan target user (misal: `asia-southeast2` Jakarta atau `asia-southeast1` Singapore).
   - **Machine type**: Minimal `e2-medium` (2 vCPU, 4GB RAM) untuk menjalankan MySQL, Nginx, PHP-FPM, dan Composer secara stabil.
3. **Boot Disk**:
   - OS: **Ubuntu 24.04 LTS** (atau 22.04 LTS).
   - Size: Minimal **30 GB** (SSD Persistent Disk).
4. **Firewall**: 
   - Centang **Allow HTTP traffic** (Port 80).
   - Centang **Allow HTTPS traffic** (Port 443).
5. Klik **Create** dan tunggu IP Public (External IP) di-assign.

> **PENTING**: Jadikan External IP tersebut berstatus **Static** (bukan Ephemeral) di menu **VPC Network** > **IP Addresses**, agar IP tidak berubah saat server di-restart.

---

## 3. Pointing Domain ke Server GCP

Lakukan ini lebih awal agar propagasi DNS punya waktu untuk berjalan.
1. Login ke panel manajemen DNS domain Anda (atau Cloudflare jika menggunakannya).
2. Buat **A Record**:
   - Name: `@` (atau biarkan kosong)
   - IPv4 Address: **[External IP GCP Anda]**
3. Buat **CNAME Record** (Opsional untuk www):
   - Name: `www`
   - Target: `domain-anda.com`

---

## 4. Konfigurasi Server (LEMP Stack)

Login ke instance GCP menggunakan SSH (via GCP Console atau terminal: `ssh username@ip-address`).

### A. Update System & Install Dependencies
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install software-properties-common curl git unzip -y
```

### B. Install PHP 8.x
*(Sesuaikan versi PHP dengan requirement Laravel 13, umumnya PHP 8.2 / 8.3 / 8.4)*
```bash
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-bcmath php8.3-curl php8.3-zip php8.3-intl php8.3-gd -y
```

### C. Install Nginx & MySQL/MariaDB
```bash
sudo apt install nginx mariadb-server -y
```

### D. Keamanan & Pembuatan Database MySQL
Jalankan pengamanan awal MySQL:
```bash
sudo mysql_secure_installation
```
Buat database untuk VIYGO:
```bash
sudo mysql -u root -p
```
```sql
CREATE DATABASE viygo_prod;
CREATE USER 'viygo_user'@'localhost' IDENTIFIED BY 'PasswordKuatAnda123!';
GRANT ALL PRIVILEGES ON viygo_prod.* TO 'viygo_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### E. Install Composer & Node.js
```bash
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js (Vite build)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 5. Deployment Code VIYGO

### A. Clone Repository
```bash
cd /var/www/
sudo git clone https://github.com/username/VIYGO-FINAL.git viygo
# Berikan hak akses kepada user saat ini agar mudah mengedit
sudo chown -R $USER:$USER /var/www/viygo
cd viygo
```

### B. Install Dependencies & Build Frontend
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### C. Setup Environment (`.env`)
```bash
cp .env.example .env
nano .env
```
Sesuaikan konfigurasi kritis berikut:
```env
APP_NAME=VIYGO
APP_ENV=production
APP_KEY= # (Nanti di-generate di bawah)
APP_DEBUG=false
APP_URL=https://domain-anda.com

DB_DATABASE=viygo_prod
DB_USERNAME=viygo_user
DB_PASSWORD=PasswordKuatAnda123!

# SEC-01: GANTI KREDENSIAL INI DENGAN KEYS MIDTRANS PRODUCTION
MIDTRANS_SERVER_KEY=Mid-server-xxxxxxxxxxxx
MIDTRANS_CLIENT_KEY=Mid-client-xxxxxxxxxxxx
MIDTRANS_IS_PRODUCTION=true
```

Generate APP_KEY dan jalankan instalasi Laravel:
```bash
php artisan key:generate
php artisan storage:link
php artisan migrate --force
```

> **CATATAN**: JANGAN JALANKAN `php artisan db:seed` di production jika tidak ingin data dummy atau wipe data. Jika butuh data initial (seperti kategori), buatkan spesifik seeder yang aman.

### D. File Permissions
Beri hak akses kepada Nginx (`www-data`) untuk memanipulasi storage dan cache:
```bash
sudo chown -R www-data:www-data /var/www/viygo/storage
sudo chown -R www-data:www-data /var/www/viygo/bootstrap/cache
sudo chmod -R 775 /var/www/viygo/storage
sudo chmod -R 775 /var/www/viygo/bootstrap/cache
```

---

## 6. Konfigurasi Nginx & SSL

### A. Setup Server Block (Virtual Host) Nginx
```bash
sudo nano /etc/nginx/sites-available/viygo
```
Isi dengan konfigurasi berikut:
```nginx
server {
    listen 80;
    server_name domain-anda.com www.domain-anda.com;
    root /var/www/viygo/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; # Sesuaikan versi PHP
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable konfigurasi dan restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/viygo /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### B. Install SSL (HTTPS) menggunakan Certbot
Pastikan propagasi DNS (Poin 3) sudah selesai.
```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d domain-anda.com -d www.domain-anda.com
```
Certbot akan otomatis memperbarui file Nginx Anda untuk menggunakan port 443 dan sertifikat Let's Encrypt.

---

## 7. Optimasi Aplikasi Laravel (Wajib di Production)

Jalankan perintah ini setiap kali Anda deploy kode baru ke production:
```bash
cd /var/www/viygo
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

---

## 8. Konfigurasi Scheduler & Queue Worker

### A. Cron Job (Scheduler)
VIYGO memiliki task `bookings:complete` yang harus berjalan setiap jam 01:00 UK Time (berdasarkan ANOM-14).
```bash
crontab -e
```
Tambahkan line berikut di paling bawah:
```bash
* * * * * cd /var/www/viygo && php artisan schedule:run >> /dev/null 2>&1
```

### B. Supervisor (Jika Menggunakan Queue)
Jika Anda menggunakan email sending (seperti ke MitraController) yang dipindahkan ke queue di masa depan, gunakan Supervisor:
```bash
sudo apt install supervisor -y
sudo nano /etc/supervisor/conf.d/viygo-worker.conf
```
Isi konfigurasi:
```ini
[program:viygo-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/viygo/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/viygo/storage/logs/worker.log
stopwaitsecs=3600
```
Start Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start viygo-worker:*
```

---

## 9. CHECKLIST KRITIS PRA-RILIS (WAJIB PERHATIKAN)

🚨 **Periksa hal-hal berikut sebelum mengumumkan website ke publik:**

1. [ ] **`APP_DEBUG=false`**: Pastikan tidak `true`. Jika error terjadi, periksa di `storage/logs/laravel.log`.
2. [ ] **Rahasia Midtrans (SEC-01)**: Pastikan Anda menggunakan *Production Key* Midtrans. Rotasi key lama sandbox yang pernah bocor. Jangan pernah meng-commit `.env`!
3. [ ] **Midtrans Webhook Config**: Buka dashboard Midtrans Production, masuk ke Settings > Configuration. Masukkan URL webhook ke: `https://domain-anda.com/midtrans/webhook`.
4. [ ] **CORS / Asset URL**: Pastikan `APP_URL` sudah diawali dengan `https://` agar asset dan API (seperti getSlots) tidak diblokir oleh browser karena Mixed Content.
5. [ ] **Test Transaksi Nyata**: Buat transaksi testing menggunakan kartu kredit aktif atau e-wallet (Rp 10.000) untuk memastikan end-to-end flow berhasil dan webhook Midtrans mencapai server dengan baik.
6. [ ] **Timezone Database**: Pastikan server dan PHP timezone sesuai jika menggunakan fungsi tanggal mentah.

---

Dengan mengikuti panduan ini, aplikasi VIYGO akan berjalan dengan aman, stabil, dan optimal di production. Jika terdapat update kode, cukup jalankan `git pull`, `composer install --no-dev`, `npm run build`, dan bersihkan cache artisan (Poin 7).
