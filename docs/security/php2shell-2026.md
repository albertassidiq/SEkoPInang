# Tindak lanjut php2shell — 23 Oktober 2026

Referensi: [advisori PHP 21 Oktober 2026](https://www.php.net/archive/2026.php#2026-10-21-5), CVE-2026-109618 dan CVE-2026-109619; surat T/KK.17/DO-21/03300/IF.130/2026.

## Status dan batas perbaikan

Perbaikan kedua CVE ada di **runtime PHP**, bukan di Laravel. Commit/push repository, Composer, middleware, `.htaccess`, WAF, atau `ini_set()` tidak memperbaiki PHP yang rentan. Pada mod_php, jalur eksploitasi dapat terjadi sebelum kode aplikasi dijalankan.

Sesuai permintaan memakai rilis stabil terbaru, project ini menargetkan **PHP 8.5.12 atau patch stabil 8.5 yang lebih baru**, Laravel 13, Jetstream 5 dan Livewire 4. PHP 8.4.27, 8.3.36 dan 8.2.35 juga memperbaiki kedua CVE upstream, tetapi cabang tersebut bukan target aplikasi setelah upgrade ini. Paket distributor dengan backport harus dibuktikan melalui advisori vendor; preflight konservatif project hanya menerima versi upstream pada cabang target.

Baseline repository: Laravel 9.52.20, Jetstream 3.0.2, Livewire 2.12.8; Composer audit mendeteksi 47 advisori pada 14 paket. PHP CLI komputer pengembangan sebelumnya 8.2.12. **Versi, SAPI, lokasi dan status patch server produksi belum diverifikasi.** Hasil pengujian lokal/CI bukan bukti bahwa server produksi sudah ditambal.

Audit setelah pembaruan: seluruh dependency Composer tidak memiliki advisori yang terdeteksi; `npm audit --omit=dev` juga bersih. Audit npm penuh masih melaporkan 8 advisori pada tooling build Tailwind 3/transitif (braces dan postcss-selector-parser). Tooling tersebut tidak diperlukan untuk melayani aplikasi; bangun aset dari source tepercaya di lingkungan build terpisah. Migrasi Tailwind 4 memerlukan pemeriksaan visual tersendiri dan berada di luar perbaikan runtime ini. CI memeriksa dependency npm produksi; jangan menyatakan seluruh tooling build bebas advisori.

Pemeriksaan baseline juga menemukan template CRUD `kedai-kopi.index` belum ada di repository sehingga halaman daftar CRUD setelah login sudah gagal sebelum upgrade. Form publik, monitor, dashboard, peta dan profil diuji terpisah. Perbaikan ini tidak menambahkan ulang template CRUD yang hilang.

## Verifikasi pengembangan

Pada 23 Oktober 2026, sumber dan lockfile divalidasi dengan PHP 8.5.12, Composer 2.10.3 dan Node 24.11.0: Laravel 13.38.0, Jetstream 5.5.3, Sanctum 4.3.3 dan Livewire 4.4.8. Suite SQLite memiliki 37 tes / 87 assertion: **29 lulus dan 8 dilewati** karena fitur nonaktif; tidak ada warning/deprecation pada suite. Pengujian memakai database sintetis, bukan dump produksi.

Preflight menerima PHP 8.5.12 dan menolak PHP 8.2.12; pengujian batas versi mencakup patch rentan, RC/beta, versi tidak valid dan cabang di luar target. Build Vite berhasil; config/route/view cache berhasil dibuat. Browser mengonfirmasi login, penyimpanan profil setelah reload, modal konfirmasi password dan setup/batal 2FA tanpa error JavaScript. Review kode independen tidak menemukan blocker pada perubahan ini.

Workflow GitHub Actions memeriksa SQLite dan MySQL 8.4 pada PHP 8.5.12. [Run 39935279822](https://github.com/albertassidiq/SEkoPInang/actions/runs/39935279822) pada commit `cb1ba45` berhasil di kedua database: masing-masing 37 tes / 87 assertion / 8 skip, audit, instalasi dependency dengan autoloader teroptimasi, build dan cache produksi. Hasil CI dan bukti produksi tetap merupakan pemeriksaan terpisah; hasil tersebut tidak membuktikan versi PHP VPS.

## Sebelum pemeliharaan

1. Inventarisasikan domain, host, image/container, PHP CLI, setiap handler web (FPM/mod_php/CGI/LiteSpeed), pool, queue, cron, dan node di belakang load balancer. Catat versi serta advisori paket vendor masing-masing.
2. Buat backup database, file unggahan, konfigurasi server dan `.env` di penyimpanan privat. Pertahankan `APP_KEY` yang ada; **jangan** menjalankan `key:generate` di produksi atau mengunggah backup/dump ke Git.
3. Uji salinan aplikasi dan database di staging. Jangan membuka `phpinfo()` atau endpoint diagnostik publik. Ambil bukti versi web melalui panel hosting atau diagnostik yang dibatasi akses dan hapus sesudahnya.

## Hosting/cPanel

1. Minta penyedia memasang PHP 8.5.12+ dengan extension yang diperlukan aplikasi (termasuk mbstring, PDO MySQL, XML, cURL, fileinfo, OpenSSL). Memilih label "8.5" saja belum membuktikan patch-nya.
2. Pilih runtime yang bertambal untuk **setiap domain/subdomain dan pool** di MultiPHP Manager/PHP Selector. Pastikan cron/SSH menggunakan binary bertambal yang sesuai.
3. Minta penyedia me-restart semua worker PHP dan memastikan versi aktual handler web. Bila versi bertambal belum tersedia, eskalasi ke penyedia; jangan menyatakan remediasi selesai.

## VPS/VM

1. Gunakan paket resmi distribusi/vendor PHP 8.5.12+ yang menerbitkan perbaikan kedua CVE, atau runtime upstream bertambal. Periksa changelog/advisori vendor bila nomor versi paket memakai backport. OS dan web server VPS belum diketahui; jangan menebak perintah `apt`/`dnf`, nama layanan atau menambahkan repositori paket tanpa mengidentifikasi OS terlebih dahulu.
2. Upgrade extension dan seluruh versi PHP yang benar-benar dipakai. Verifikasi `php -v`, `php --ini` serta versi handler web; CLI dan FPM/mod_php dapat berbeda.
3. Restart layanan PHP-FPM untuk seluruh pool, Apache bila memakai mod_php, dan semua proses lama (queue, supervisor, cron persisten, Octane bila digunakan). Jangan mengandalkan OPcache reset untuk mengganti binary PHP di memori.

## Container

1. Ganti setiap base image PHP dengan patch upstream yang sesuai, misalnya `php:8.5.12-fpm-bookworm` (atau patch stabil 8.5 lebih baru). Sesuaikan varian dengan stack sebenarnya; Composer/Node builder bukan image runtime PHP.
2. Pull base baru, build ulang tanpa memakai layer PHP lama, jalankan suite pada image hasil build, lalu redeploy **seluruh replica**. Periksa versi di dalam container yang sedang melayani trafik, bukan hanya image lokal.
3. Hentikan replica/worker lama dan simpan digest image beserta bukti versi. Jangan rollback ke image PHP yang rentan.

## Deploy aplikasi setelah PHP diperbarui

Jalankan dari root project menggunakan binary PHP bertambal. Gunakan Composer 2 dan Node.js 22.12+ atau 24 LTS untuk build Vite 7.

```sh
php scripts/check-php-runtime.php
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
composer check-platform-reqs --no-dev
composer audit --locked --no-dev
npm ci
npm run build
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

Gunakan pola release atomik/maintenance sesuai hosting agar trafik tidak dilayani dari folder dengan vendor/aset setengah terpasang. Jalankan Composer dari CLI bertambal yang sama dan jangan melewati preflight dengan `--no-scripts` atau persyaratan platform dengan `--ignore-platform-reqs`. Jangan menyalin `config.platform.php` palsu untuk menyamarkan runtime lama.

`scripts/check-php-runtime.php` hanya memverifikasi **proses CLI yang menjalankannya**. Hook Composer mencegah instalasi biasa memakai PHP rentan, tetapi tidak melindungi worker web sebelum parsing request. Periksa web SAPI secara terpisah.

Tetapkan `APP_ENV=production` dan `APP_DEBUG=false`; document root harus menunjuk ke `public/`. Pertahankan kredensial database dan `APP_KEY`. Node hanya diperlukan saat build; aset dapat dibangun di CI lalu disertakan dalam release. Lakukan smoke test `/form`, `/mitra`, `/monitor`, login, dashboard, profil dan 2FA.

## Konfigurasi tambahan

Set `session.upload_progress.enabled=0` dan, pada PHP 8.5, `fatal_error_backtraces=0` di php.ini/pool/server lalu restart worker sebagai pengurangan permukaan serangan. Batas request di proxy/web server harus berlaku juga untuk request chunked/tanpa Content-Length. Pengaturan ini bukan pengganti upgrade.

Jangan otomatis mengubah `memory_limit` menjadi `-1` tanpa batas sumber daya pada container/cgroup. Mitigasi sementara pada advisori upstream memiliki batas dan risiko ketersediaan; hanya upgrade yang merupakan perbaikan lengkap. Hentikan akses publik atau lakukan pemeliharaan terkontrol bila penyedia belum dapat menambal runtime.

## Bukti dan pelaporan

Catat per aset: domain/host, penanggung jawab, versi dan SAPI sebelum/sesudah, advisori vendor atau digest image, waktu upgrade/restart dalam WIB, commit aplikasi, hasil preflight/Composer audit/smoke test, serta status setiap node/worker. Jangan menyertakan token, `.env`, data pemilik kedai, atau dump database.

Status awal yang jujur: **perbaikan repository disiapkan; remediasi produksi menunggu bukti upgrade seluruh handler PHP**. Isi template pada `s.bps.go.id/formkeamanan` setelah bukti lengkap dan kirim ke `csirt@bps.go.id` melalui petugas berwenang. Dokumen ini tidak mengirim email atau laporan secara otomatis.

Jika rollback aplikasi diperlukan, pertahankan runtime PHP yang sudah ditambal dan gunakan release aplikasi yang kompatibel. Perubahan dependency ini tidak memerlukan penghapusan database atau pembuatan APP_KEY baru.
