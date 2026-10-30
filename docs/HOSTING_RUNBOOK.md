# SportManado: panduan hosting dan pemulihan

## Batas rilis

Paket ini mendukung Laravel 12/PHP >=8.2, MySQL atau MariaDB dengan InnoDB, disk lokal permanen, HTTPS, session/cache database dan queue sync. Aset UI, font, ikon, Alpine dan Flatpickr tersedia lokal; Snap tetap membutuhkan Midtrans. Jangan menggunakan storage sementara untuk QR dan dokumen.

Pengujian lokal tidak membuktikan layanan hosting sudah berhasil. Sebelum rilis tetap wajib uji webhook Sandbox publik, pengiriman SMTP, scanner USB nyata, scheduler, HTTPS dan backup/restore. Penarikan dilakukan manual oleh Admin melalui bank setelah EO mengisi formulir pengajuan. Bukti transfer wajib disimpan saat persetujuan. Verifikasi email tidak diwajibkan oleh model pengguna saat ini. Pemindaian ditutup untuk kegiatan selesai atau yang melewati waktu selesai.

## Menyiapkan paket

1. Simpan backup source lengkap, database dan `storage/app` di luar document root. Source yang belum masuk Git harus ikut diamankan.
2. Jalankan `npm ci`, `npm run build`. Node hanya diperlukan untuk build; hosting cukup memakai hasil `public/assets`.
3. Dari PowerShell jalankan `./scripts/New-Release.ps1 -Destination C:\path-di-luar-project\sportmanado-release-baru`. Script menolak menimpa direktori, menyertakan migration, dan mengecualikan .env, vendor, database, upload, junction serta cache lokal. Jangan mengunggah snapshot backup atau database ke web.
4. Pasang paket di `/home/AKUN/sportmanado`; document root harus `/home/AKUN/sportmanado/public`.

## Konfigurasi server

- Pilih PHP web/CLI >=8.2; aktifkan ctype, curl, dom, fileinfo, filter, hash, iconv, json, libxml, mbstring, openssl, pcre, PDO, pdo_mysql, session, tokenizer dan xmlwriter. Jangan memakai `--ignore-platform-reqs`.
- Gunakan `composer install --no-dev --prefer-dist --optimize-autoloader`, kemudian `composer check-platform-reqs --no-dev`. Jangan menjalankan composer update/setup pada server.
- Buat database MySQL/MariaDB dan pengguna aplikasi. Semua tabel aplikasi harus memakai InnoDB. Arahkan DB_HOST ke alamat dari panel hosting.
- Salin `.env.hosting.example` ke `.env`, isi nilai dari panel sendiri. APP_ENV harus production, APP_DEBUG false, APP_URL HTTPS publik. Key, kredensial dan database lokal tidak disalin. Untuk instalasi BARU jalankan `php artisan key:generate --force`; jangan mengganti APP_KEY pada aplikasi yang sudah memiliki data terenkripsi.
- APP_TIMEZONE=Asia/Makassar membuat input jadwal dan label tiket konsisten dengan WITA. Tidak ada konversi massal data lama dalam perbaikan ini. Pastikan jam kegiatan lama benar sebelum memindahkan database.
- TRUSTED_PROXIES hanya diisi IP/CIDR reverse proxy yang benar dari penyedia. Jangan memakai wildcard tanpa memastikan origin server hanya dapat dijangkau melalui proxy tersebut.
- Pastikan storage dan bootstrap/cache dapat ditulis pengguna aplikasi; jangan menggunakan chmod 777. Sesuaikan upload_max_filesize >=10M dan post_max_size berdasarkan TOTAL lampiran yang diizinkan. Form kegiatan dapat menyertakan banyak gambar, sehingga batas request bukan sekadar batas satu dokumen.

## Database, akun, dan file

1. Pada database tujuan baru: `php artisan migrate --force`. Periksa `php artisan migrate:status`.
2. Buat akun awal melalui `php artisan app:create-admin`. Password dimasukkan tersembunyi, minimal 12 karakter. Seeder pengembangan diblokir di production.
3. Untuk pemindahan DATA LAMA, pulihkan database dan storage dari pasangan backup yang sama. Jangan menjalankan migrate:fresh atau db:wipe.
4. Jalankan `php artisan storage:link`. Hanya storage/app/public yang boleh ditautkan; storage/app/private harus tetap privat. Windows junction lokal tidak dibawa paket.
5. Jangan menyalin bootstrap/cache atau compiled views dari komputer lokal. Setelah .env final, jalankan `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache` pada server.
6. Jalankan `php artisan hosting:check --database`. Perintah hanya membaca konfigurasi, permission dan database; tidak mencetak kredensial. FAIL harus ditangani. PASS tetap membutuhkan pengujian penerimaan di bawah.

## Midtrans, email dan scheduler

- Gunakan key SANDBOX: MIDTRANS_IS_PRODUCTION=false, MIDTRANS_DEMO=false. Notification URL: `https://ALAMAT-PUBLIK/payments/notification`. Endpoint harus menerima POST langsung tanpa login, redirect atau challenge browser. Jangan membuka pengecualian CSRF lain.
- Uji settlement, capture accept, gagal/expire, signature salah, nominal salah, notifikasi ganda dan notifikasi setelah tiket used. Snap frontend dan Server Key harus berasal dari lingkungan yang sama.
- Konfigurasikan SMTP sesuai dokumentasi penyedia dan uji reset password sampai email benar-benar diterima. MAIL_MAILER=log tidak mengirim email.
- Scheduled task: `php /home/AKUN/sportmanado/artisan schedule:run` setiap menit. Periksa zona waktu dan log eksekusi. Queue sync tidak membutuhkan worker permanen.
- Jadwal berisi `events:auto-complete` dan `payments:expire-reservations` setiap menit dengan pencegahan overlap. Reservasi berbayar baru berlaku 30 menit. Kuota dipertahankan jika status provider belum dapat diverifikasi. Uji command manual pada Sandbox untuk memastikan cron dan koneksi provider berjalan.
- Migration 2026_10_29_000004 menambah waktu selesai event dan metadata reservasi; 000005 mengubah relasi pemilik riwayat menjadi RESTRICT. Backup dahulu dan migrasikan sebelum deploy kode baru. Isi waktu selesai untuk event beberapa hari; tanpa isian dipakai akhir hari tanggal mulai. Lihat [FUNCTIONAL_SAFEGUARDS_UPDATE.md](FUNCTIONAL_SAFEGUARDS_UPDATE.md).

## Pengujian sebelum link dibagikan

Pembaruan 30 Oktober: jalankan seluruh migration yang belum diterapkan, termasuk `2026_10_30_000002_add_manual_transfer_proof_to_withdrawal_requests`. Penarikan menggunakan transfer bank manual, dengan bukti privat wajib, waktu konfirmasi dan Admin penyetuju. Kredensial Iris tidak diperlukan. Uji pengajuan EO, unggah bukti Admin, penolakan, akses bukti dan konsistensi saldo. Lihat [MANUAL_WITHDRAWALS_20261030.md](MANUAL_WITHDRAWALS_20261030.md).


- Buat Organizer demo yang sudah approved dan tiga kegiatan aktif: gratis otomatis, gratis persetujuan, berbayar Sandbox. Deadline masih berlaku dan kuota mencukupi.
- Uji register, login/logout, profil, pencarian/filter kegiatan, isian dinamis/validasi, semua jalur tiket, QR, daftar peserta, approval/rejection, notifikasi, ekspor, keuangan dan tindakan Admin.
- Uji scanner pada komputer dengan USB: QR JSON dan kode manual, tiket salah kegiatan, pending/cancelled, pemindaian pertama dan pemindaian ulang. Status used dan used_at harus tetap konsisten.
- Uji dua pendaftaran merebut slot terakhir dan dua pemindaian bersamaan pada database hosting. Hanya satu operasi yang berhak boleh berhasil.
- Uji mobile 320/360/390/430, tablet 768, desktop 1024/1440, landscape, keyboard terbuka dan zoom 200%. Menu, tabel, modal, QR dan pesan kesalahan tidak boleh terpotong.
- Periksa akses akun/peran lain, dokumen privat, secure cookie, .env tidak dapat dibaca, response 419/500, console dan log error.
- Ukur respons dan penggunaan sumber daya dengan kelompok kecil sebelum memperluas beta. Upgrade/ganti hosting jika kuota tidak cukup; jangan menonaktifkan fitur diam-diam.

## Pengujian pengembangan yang terisolasi

Gunakan checkout/salinan terpisah. `php vendor/phpunit/phpunit/phpunit --do-not-cache-result` memakai SQLite memory dan guard pada tests/bootstrap.php. Jangan menjalankan tes pada database utama.

Untuk MySQL, salin phpunit.mysql.xml.example menjadi phpunit.mysql.local.xml, buat database bernama sportmanado_test_* dengan pengguna yang hanya berhak pada database itu, isi konfigurasi lokal, kemudian jalankan PHPUnit dengan `--configuration phpunit.mysql.local.xml`. Guard menolak nama database lain dan config cache. File konfigurasi lokal diabaikan Git.

`npm run test:scanner` menjalankan tes perilaku feedback scanner. `npm run test:browser` membutuhkan Chromium (`npx playwright install chromium`) dan fixture terpisah sesuai tests/browser/readiness.cjs. Isi SPORTMANADO_TEST_URL localhost, SPORTMANADO_TEST_PASSWORD dan SPORTMANADO_EVIDENCE_DIR di luar web root. Tes browser memblokir koneksi pihak ketiga untuk memeriksa kemandirian UI. Jangan memakai akun/data pengguna asli.

## Backup dan rollback

Backup mencakup versi source/dependency, .env/APP_KEY tersimpan privat, dump database konsisten dan seluruh storage/app. Backup harus di luar document root dan dapat dipulihkan ke lingkungan terpisah.

Sebelum pembaruan: aktifkan maintenance jika diperlukan, ambil backup konsisten, lalu ganti source dan cache server. Perbaikan ini menambah satu kolom nullable tickets.notes melalui migration 2026_10_29_000001. Kolom lama dan data waktu tidak dikonversi. Jalankan migration tambahan ini sebelum source baru diaktifkan. Untuk rollback source gunakan snapshot sebelumnya beserta asetnya; biarkan kolom notes tetap ada agar catatan baru tidak hilang (jangan jalankan down migration hanya untuk rollback kode); jangan menimpa .env atau upload baru. Pemulihan database setelah pengguna bertransaksi harus mempertimbangkan data baru agar tidak hilang. Jangan menjalankan rollback database otomatis tanpa meninjau dampaknya.

### Menyiapkan fixture browser

Pada salinan terpisah, gunakan database SQLite BARU `database/browser.sqlite`, APP_ENV=local, APP_URL=http://127.0.0.1:8765, APP_DEBUG=false, MAIL_MAILER=log, QUEUE_CONNECTION=sync, SESSION_DRIVER=database, CACHE_STORE=database dan APP_KEY khusus pengujian. Jangan salin key Midtrans asli. Jalankan migration pada database baru itu dan storage:link, set SPORTMANADO_TEST_PASSWORD (minimal 12 karakter), kemudian `php tests/browser/seed.php`. Seeder ini menolak database utama dan database yang sudah memiliki pengguna. Mulai server loopback dari direktori public: `php -S 127.0.0.1:8765 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`. Sesudah itu jalankan readiness.cjs dan flows.cjs dengan environment pengujian yang sama.

`php tests/mysql-concurrency.php phpunit.mysql.local.xml` menjalankan dua proses PHP untuk memperebutkan slot terakhir dan memindai tiket yang sama. Perintah ini hanya menerima database MySQL khusus pengujian dengan nama yang lolos guard. Jangan menjalankan bersamaan dengan PHPUnit yang memakai database pengujian yang sama.

## Migration pembaruan menu Peserta

Pembaruan Edit Peserta menambah kolom nullable participant_name, participant_email dan check_in_method pada tickets melalui migration 2026_10_29_000002. Jalankan migration sebelum mengaktifkan source pembaruan di hosting. Akun login dan data lama tidak diubah; kolom koreksi berlaku per pendaftaran. Gunakan paket source terbaru yang menyertakan migration ini.

## Kebijakan pembatalan terbaru

Pembatalan/penolakan manual EO hanya untuk kepesertaan gratis yang belum hadir. Tiket berbayar tidak dapat dibatalkan karena refund belum tersedia; kegagalan/expire provider tetap diproses otomatis. Sertakan migration 2026_10_29_000003_add_cancellation_details_to_tickets_table.php sebelum mengaktifkan source pembaruan. Lihat FUNCTIONAL_EVALUATION_20261029.md untuk prioritas penyesuaian fungsional selanjutnya.
