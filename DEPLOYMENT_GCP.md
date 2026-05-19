# Panduan Deployment VIYGO ke Google Cloud Platform (GCP) dengan Docker

Dokumen ini berisi panduan langkah demi langkah untuk melakukan *deployment* aplikasi Laravel VIYGO Anda ke instance Google Cloud Platform (GCP) menggunakan Docker.

## File Persiapan yang Telah Dibuat
1. **`Dockerfile`**: Berisi instruksi *multi-stage build* untuk mem-build *image* Docker aplikasi (Node.js untuk *build* Vite asset, PHP 8.3 Apache untuk aplikasi backend Laravel).
2. **`.dockerignore`**: Mencegah file/folder yang tidak diperlukan (seperti `vendor`, `node_modules`, `.env`) masuk ke dalam *image* Docker untuk mempercepat *build*.
3. **`.github/workflows/gcp-deploy.yml`**: *Template* GitHub Actions opsional untuk otomatisasi deployment (CI/CD) ke Google Compute Engine (GCE).

---

## Langkah-langkah Deployment ke GCP

Pilih salah satu dari opsi *deployment* di bawah ini.

### Opsi 1: Menggunakan Google Compute Engine (VM Instance)
*Opsi ini sangat fleksibel dan memberi Anda kontrol penuh atas server.*

1. **Buat VM Instance di GCP**
   - Masuk ke menu **Compute Engine** di Google Cloud Console.
   - Buat instance baru (Disarankan menggunakan OS Ubuntu 22.04 LTS atau Debian).
   - Pastikan Anda mencentang **"Allow HTTP traffic"** di bagian Firewall untuk membuka *port* web.

2. **Install Docker di Instance GCP**
   - Akses VM Anda melalui SSH (klik tombol "SSH" langsung dari *console* GCP).
   - Jalankan perintah berikut untuk menginstal Docker:
     ```bash
     sudo apt update
     sudo apt install docker.io -y
     sudo usermod -aG docker $USER
     ```

3. **Clone Repository Anda**
   ```bash
   git clone <URL_REPO_GITHUB_ANDA> viygo
   cd viygo
   ```

4. **Persiapkan File Lingkungan (`.env`)**
   - Salin file *template* `.env` untuk mengatur konfigurasi Anda:
     ```bash
     cp .env.example .env
     nano .env
     ```
   - Ubah `APP_ENV=production` dan `APP_DEBUG=false`.
   - Isi kredensial Database (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`). Arahkan ke IP server database Anda (seperti Cloud SQL) atau database lokal jika Anda menginstal MySQL di VM yang sama.
   - Sesuaikan konfigurasi Midtrans (`MIDTRANS_SERVER_KEY`, dll) dan `APP_URL`.

5. **Build dan Jalankan Container Docker**
   - Lakukan *build image* Docker:
     ```bash
     sudo docker build -t viygo-app .
     ```
   - Jalankan *container*:
     ```bash
     sudo docker run -d \
       --name viygo-server \
       -p 80:80 \
       --env-file .env \
       viygo-app
     ```
   - Aplikasi Anda sekarang dapat diakses menggunakan Alamat IP Publik (Public IP) dari VM tersebut.

---

### Opsi 2: Menggunakan Google Cloud Run (Serverless)
*Opsi ini sangat praktis karena Anda tidak perlu mengelola VM. Cloud Run dapat memperbesar atau memperkecil skala container secara otomatis.*

1. **Persiapan GCP**
   - Pastikan Anda telah mengaktifkan **Cloud Build API** dan **Cloud Run API** di GCP Console Anda.

2. **Jalankan Deployment dari Cloud Shell**
   - Buka **Cloud Shell** (ikon terminal `>_` di bagian kanan atas GCP Console).
   - Lakukan *clone repository* Anda ke dalam Cloud Shell:
     ```bash
     git clone <URL_REPO_GITHUB_ANDA> viygo
     cd viygo
     ```
   - Jalankan perintah *deploy* secara langsung (Google Cloud otomatis membaca file Dockerfile):
     ```bash
     gcloud run deploy viygo-app \
       --source . \
       --region=asia-southeast2 \
       --allow-unauthenticated
     ```

3. **Atur Environment Variables (.env)**
   - Karena file `.env` diabaikan oleh `.dockerignore` untuk keamanan, Anda harus mendaftarkannya di pengaturan Cloud Run.
   - Buka menu **Cloud Run** di GCP Console, pilih *service* `viygo-app` Anda.
   - Klik **Edit & Deploy New Revision**.
   - Pergi ke tab **Variables & Secrets** (atau **Container**).
   - Masukkan konfigurasi yang dibutuhkan (seperti kredensial Database, Midtrans, `APP_KEY`) sebagai *Environment Variables*.
   - Klik **Deploy** untuk menerapkan perubahan.

### Catatan Tambahan (Database)
Karena container Docker bersifat *stateless* (tidak menyimpan data secara permanen), pastikan Database Anda dipisahkan. Anda sangat disarankan untuk menggunakan **Google Cloud SQL** sebagai database backend yang aman dan stabil untuk produksi.
