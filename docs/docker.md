# Deploy SEkoPInang dengan Docker

Image produksi memakai **PHP 8.5.12 + Apache**, berjalan sebagai `www-data` (UID 33) pada port 8080. PHP, Composer dan Node memakai image resmi dengan digest terkunci. Composer dan Node hanya dipakai pada tahap build; image akhir berisi dependency PHP produksi dan aset Vite hasil build.

Butuh Docker Engine yang masih didukung beserta Docker Compose v2.20+ (`--env-file` berulang dan `up --wait`). Docker tidak perlu dipasang pada komputer pengembang jika image dibangun oleh pipeline; job Docker pada GitHub Actions membangun dan menjalankan image yang sama. Dockerfile ditujukan untuk Linux containers.

`compose.yaml` menjalankan aplikasi dengan **database yang sudah ada**. `compose.mysql.yaml` adalah tambahan opsional untuk database baru/terisolasi. Jangan mengganti database produksi dengan database kosong dari contoh tambahan tersebut.

## Deployment yang sudah ada

1. Backup database, konfigurasi, dan volume storage lama. Catat nama image/digest lama, seluruh replica, worker dan scheduler yang menggunakan PHP. Pertahankan `APP_KEY`, kredensial database dan data unggahan yang ada.
2. Salin `.env.docker.example` ke `.env.docker`, lalu isi menggunakan konfigurasi produksi saat ini. File ini dibaca saat container dijalankan, tidak disalin ke image. Jangan memasukkannya ke Git atau menggunakan build argument untuk rahasia.

```sh
cp .env.docker.example .env.docker
chmod 600 .env.docker
```

Isi `APP_KEY` dengan **key produksi yang sama**. Sesuaikan `APP_URL`, koneksi database, SMTP, session dan queue yang sudah digunakan. `DB_HOST=127.0.0.1` di dalam container menunjuk container aplikasi sendiri. Untuk database pada host, tersedia alias `host.docker.internal`; database harus menerima koneksi dari jaringan Docker. Untuk database pada container lain, sambungkan aplikasi ke network database yang sama melalui override Compose dan gunakan nama layanan database tersebut.

Set `APP_STORAGE_VOLUME` ke nama volume storage yang benar-benar sudah ada; periksa dengan `docker volume inspect NAMA_VOLUME` sebelum deploy. Default `sekopinang-storage` membuat volume baru jika belum ada. Bila deployment lama menggunakan bind mount atau lokasi storage berbeda, sesuaikan mount Compose atau salin data dari backup sebelum memindahkan trafik. Mengganti nama volume tidak memigrasikan data secara otomatis.

Bangun image baru:

```sh
docker compose --env-file .env.docker build --pull app
```

Jika ownership volume lama berbeda, sesudah memastikan volume benar dan backup tersedia, jalankan satu kali:

```sh
docker compose --env-file .env.docker run --rm --user root app \
  chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
```

Periksa runtime dan status migrasi menggunakan image baru:

```sh
docker compose --env-file .env.docker run --rm app php scripts/check-php-runtime.php
docker compose --env-file .env.docker run --rm app php artisan migrate:status
```

Upgrade runtime ini tidak menambahkan migrasi database baru. Bila ada migrasi aplikasi yang memang belum diterapkan, jalankan secara terencana setelah backup:

```sh
docker compose --env-file .env.docker run --rm app php artisan migrate --force
```

Jangan memakai `migrate:fresh`, mengganti `APP_KEY`, atau menghapus volume untuk memperbarui aplikasi. Entrypoint tidak menjalankan migrasi maupun membuat key secara otomatis.

Ganti container yang melayani trafik dengan image baru:

```sh
docker compose --env-file .env.docker up -d --force-recreate --wait --wait-timeout 120 app
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker exec -T app php -v
docker compose --env-file .env.docker logs --tail=100 app
```

Startup web menolak key kosong dan debug aktif, lalu membuat cache config/route/view menggunakan environment saat runtime. `storage` dipersistenkan; `bootstrap/cache` milik container release itu sendiri. Source tidak di-bind-mount dari host, sehingga hasil build dan versi dependency tetap sesuai image.

Perbarui juga definisi image **semua** queue worker, scheduler dan replica. Image yang sama mendukung perintah CLI seperti `php artisan queue:work`; pengelolaan worker/scheduler mengikuti deployment yang sudah ada. `docker compose restart` saja tidak mengganti image lama. Simpan bukti digest image dan versi PHP di setiap container yang sedang berjalan.

## Reverse proxy dan HTTPS

Port host default hanya terikat ke `127.0.0.1:8080`, untuk reverse proxy pada VPS yang sama. Reverse proxy harus mengirim `Host`, `X-Forwarded-Proto`, `X-Forwarded-For` dan port yang benar. Bila proxy berada pada container lain, hubungkan melalui network Docker dan port internal 8080; ubah bind hanya bila diperlukan.

Pada `.env.docker`, isi:

```dotenv
APP_URL=https://domain-aplikasi-anda
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=IP_PROXY_YANG_DILIHAT_CONTAINER
```

`TRUSTED_PROXIES` menerima IP/CIDR dipisahkan koma melalui middleware Laravel yang sudah ada. Biarkan kosong jika tanpa proxy. Gunakan alamat proxy yang benar; jangan mempercayai seluruh pengirim dengan `*`. Untuk proxy pada host, alamat yang terlihat container biasanya gateway network Docker; verifikasi melalui konfigurasi network dan access log, jangan menebaknya. Setelah mengubah environment, recreate container agar cache config menggunakan nilai baru.

## Instalasi baru dengan MySQL opsional

Bagian ini untuk database baru, bukan pemindahan data produksi. Bangun image terlebih dahulu tanpa key/credential build:

```sh
docker build --pull -t sekopinang:local .
cp .env.docker.example .env.docker
docker run --rm --entrypoint php sekopinang:local artisan key:generate --show
```

Salin key yang dihasilkan ke `.env.docker` dan tetapkan password kuat pada `DB_PASSWORD`. **Hanya instalasi baru** yang membuat key baru. Buat file root password MySQL terpisah agar credential root tidak masuk ke environment aplikasi:

```sh
umask 077
printf 'MYSQL_ROOT_PASSWORD=%s\n' "$(openssl rand -base64 36)" > .env.mysql
chmod 600 .env.docker .env.mysql
```

Jalankan dari root project:

```sh
docker compose --env-file .env.docker --env-file .env.mysql \
  -f compose.yaml -f compose.mysql.yaml up -d --wait --wait-timeout 120 db
docker compose --env-file .env.docker --env-file .env.mysql \
  -f compose.yaml -f compose.mysql.yaml run --rm app php artisan migrate --force
docker compose --env-file .env.docker --env-file .env.mysql \
  -f compose.yaml -f compose.mysql.yaml up -d --wait --wait-timeout 120 app
```

Buka `http://localhost:8080` dari host yang menjalankan Docker. Untuk akses dari komputer lain, gunakan domain melalui reverse proxy atau SSH tunnel. Akun admin dibuat melalui prosedur aplikasi yang sudah ada; registrasi publik tetap dinonaktifkan.

Database opsional tidak memublikasikan port MySQL. User aplikasi bukan root, password root disimpan pada `.env.mysql`, dan data database tersimpan pada named volume. Gunakan `down` tanpa `--volumes` bila hanya ingin menghentikan stack. `down --volumes` menghapus data dan hanya digunakan untuk database sementara CI.

## Verifikasi dan pembaruan berikutnya

Healthcheck memanggil `/form` melalui Apache/PHP, memakai cookie jar untuk menggunakan kembali session pemeriksaan. Status sehat membutuhkan `APP_KEY`, koneksi database dan tabel session yang benar. Smoke test memeriksa form/mitra/monitor/login, akses admin, aset, permission non-root, startup guard dan larangan eksekusi PHP pada storage publik.

GitHub Actions menjalankan build serta smoke test container Linux dengan database MySQL baru; pengujian PHP SQLite/MySQL tetap berjalan pada job terpisah. `.dockerignore` mengecualikan `.env`, dump, archive, storage host, vendor/node_modules dan artefak lokal. CI menyisipkan file canary dan memastikan tidak ikut ke image.

Untuk patch PHP berikutnya, perbarui **tag dan digest** base PHP, batas versi pada `composer.json`/`PhpRuntime`, serta `.php-version` dan workflow bila diperlukan; kemudian perbarui lockfile dan uji. Jangan mengandalkan `--pull` untuk memutakhirkan digest yang sudah dikunci. Node, Composer dan MySQL yang dikunci juga perlu diperbarui secara terencana. Hindari rollback ke image PHP rentan.

Build/push repository dan CI tidak mengubah VPS. Tindak lanjut php2shell baru selesai setelah semua container lama diganti dan versi runtime aktif diverifikasi. Lihat juga [catatan advisori dan pelaporan](security/php2shell-2026.md).
