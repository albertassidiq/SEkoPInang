# ☕ SEkoPInang

**Sistem Pendataan Kedai Kopi Kota Tanjungpinang**

Aplikasi web berbasis Laravel untuk mendata, memantau, dan memvisualisasikan data kedai kopi di Kota Tanjungpinang dalam rangka mendukung kegiatan **Sensus Ekonomi (SE)** oleh Badan Pusat Statistik Kota Tanjungpinang.

**Update keamanan 23 Oktober 2026:** aplikasi menargetkan PHP **8.5.12+ (cabang 8.5)**. Rebuild dan ganti container PHP di VPS tetap wajib; push kode tidak menambal container yang sedang berjalan. Ikuti [panduan Docker](docs/docker.md) dan [penanganan php2shell](docs/security/php2shell-2026.md).

---

## 📋 Tentang Proyek

SEkoPInang (*Sensus Ekonomi Kedai Kopi Tanjungpinang*) adalah sistem pendataan dua jalur:

- **Input Mandiri** — Pemilik kedai kopi mengisi data secara langsung melalui form publik
- **Input Mitra** — Petugas lapangan BPS mengisi data atas nama pemilik kedai

Semua data terkumpul dalam satu **dashboard monitor publik** yang dapat diakses tanpa login, menampilkan statistik agregat dan persebaran wilayah secara real-time.

---

## ✨ Fitur Utama

| Fitur | Deskripsi |
|-------|-----------|
| 📝 **Form Input Mandiri** | Pemilik kedai mengisi data sendiri via `/form` |
| 🤝 **Form Input Mitra** | Petugas BPS mengisi data via `/mitra` |
| 📊 **Dashboard Monitor Publik** | Statistik & tabel data semua kedai via `/monitor` |
| 🗺️ **Peta Interaktif** | Visualisasi lokasi kedai di peta (akses login) |
| 🔐 **Dashboard Admin** | CRUD data + manajemen (akses login) |
| 🔍 **Filter & Pencarian** | Filter by kecamatan, kelurahan, sumber, status GPS |
| 📄 **Soft Delete** | Data terhapus bisa dipulihkan |
| 📍 **Geolocation** | Koordinat GPS dengan link Google Maps |

---

## 🛠️ Tech Stack

- **Framework:** Laravel 13 + PHP 8.5.12+ pada cabang 8.5
- **Frontend:** Blade + Tailwind CSS v3 (via Vite)
- **Auth:** Laravel Jetstream + Sanctum + Livewire
- **Database:** MySQL
- **Map:** Leaflet.js / Google Maps embed
- **Build:** Vite + PostCSS

---

## 🚀 Instalasi

Untuk deployment Docker, gunakan [Dockerfile, Compose dan panduan produksi](docs/docker.md). Image sudah mencakup PHP/Apache, dependency produksi dan aset Vite; Composer/Node tidak diperlukan pada host VPS.

### Prerequisites

- PHP 8.5.12 atau patch stabil 8.5 yang lebih baru
- Composer
- Node.js 22.12+ atau 24 LTS + npm (untuk Vite 7)
- MySQL

### Langkah-langkah

```bash
# 1. Clone repository
git clone https://github.com/albertassidiq/SEkoPInang.git
cd SEkoPInang

# 2. Install dependencies PHP
php scripts/check-php-runtime.php
composer install

# 3. Install dependencies Node
npm ci

# 4. Copy file environment
cp .env.example .env

# 5. Generate application key
php artisan key:generate
```

### Konfigurasi Database

Edit file `.env` dan sesuaikan koneksi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sekopinang
DB_USERNAME=root
DB_PASSWORD=
```

```bash
# 6. Jalankan migrasi
php artisan migrate

# 7. (Opsional) Import data dari SQL dump
mysql -u root -p sekopinang < database/2026-04-27\ SEKOPINANG.sql
```

### Menjalankan Aplikasi

```bash
# Terminal 1 - Laravel server
php artisan serve

# Terminal 2 - Vite asset compiler
npm run dev
```

Buka browser: **http://127.0.0.1:8000**

---

## 🗺️ Struktur Halaman

| URL | Akses | Deskripsi |
|-----|-------|-----------|
| `/` atau `/form` | Publik | Form input mandiri pemilik kedai |
| `/mitra` | Publik | Form input mitra (petugas lapangan) |
| `/monitor` | Publik | Dashboard monitor & statistik publik |
| `/dashboard` | Login | Dashboard admin |
| `/peta` | Login | Peta interaktif kedai kopi |
| `/kedai-kopi` | Login | CRUD data kedai |

---

## 🗄️ Skema Database

Tabel utama: `kedai_kopi`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | Primary key |
| `kode_kota` | string(4) | Default `2172` (Tanjungpinang) |
| `kode_kecamatan` | string(3) | Kode kecamatan |
| `kode_kelurahan` | string(3) | Kode kelurahan |
| `rt` / `rw` | string(3) | RT/RW, nullable |
| `alamat` | text | Alamat lengkap |
| `latitude` / `longitude` | decimal | Koordinat GPS, nullable |
| `nama_kedai` | string | Nama kedai kopi |
| `nama_pemilik` | string | Nama pemilik, nullable |
| `jenis_kelamin` | enum(L/P) | Jenis kelamin pemilik |
| `handphone` | string | Nomor HP, nullable |
| `omzet` | bigint | Omzet per bulan (Rupiah) |
| `jumlah_pekerja` | integer | Jumlah pekerja |
| `stan_sewa` | integer | Jumlah stan yang disewa |
| `tren_pekerja` | enum | naik / turun / tetap |
| `catatan` | text | Catatan tambahan, nullable |
| `sumber` | enum(mandiri/mitra) | Sumber data input |
| `created_at` / `updated_at` | timestamp | Timestamps |
| `deleted_at` | timestamp | Soft delete |

### Wilayah yang Dicakup

| Kode | Kecamatan |
|------|-----------|
| 010 | Bukit Bestari |
| 020 | Tanjungpinang Timur |
| 030 | Tanjungpinang Kota |
| 040 | Tanjungpinang Barat |

---

## 📊 Dashboard Monitor

Halaman `/monitor` bersifat **publik** dan menampilkan:

- **Statistik ringkas** — Total kedai, total pekerja, rata-rata omzet, kedai dengan GPS
- **Distribusi per kecamatan** — Breakdown jumlah kedai tiap kecamatan dan kelurahan
- **Tabel data** — Dapat difilter dan diurutkan berdasarkan:
  - Nama kedai
  - Kecamatan / Kelurahan
  - Status GPS (ada/tidak)
  - Sumber data (Mandiri / Mitra)

> ⚠️ Data sensitif (nama pemilik, nomor HP) **tidak ditampilkan** di halaman publik.

---

## 🔐 Akun Admin

Buat akun admin melalui halaman registrasi atau via Tinker:

```bash
php artisan tinker
```

```php
App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@sekopinang.id',
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
]);
```

---

## 🏗️ Build Production

```bash
# Compile assets untuk production
npm run build

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📁 Struktur Direktori Penting

```
SEkoPInang/
├── app/
│   ├── Http/Controllers/
│   │   └── KedaiKopiController.php   # Controller utama
│   └── Models/
│       └── KedaiKopi.php             # Model utama
├── database/
│   └── migrations/                   # Schema database
├── resources/
│   └── views/
│       ├── form.blade.php            # Form input mandiri
│       ├── mitra.blade.php           # Form input mitra
│       ├── monitor.blade.php         # Dashboard publik
│       └── dashboard.blade.php       # Dashboard admin
└── routes/
    └── web.php                       # Definisi route
```

---

## 👥 Kontribusi

Proyek ini dikembangkan untuk kebutuhan internal **BPS Kota Tanjungpinang**.

---

## 📄 Lisensi

© 2025 Badan Pusat Statistik Kota Tanjungpinang. All rights reserved.
