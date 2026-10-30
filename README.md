# SportManado

Source deployment aplikasi tiket dan pengelolaan event olahraga berbasis Laravel 12, PHP >= 8.2, dan MySQL.

Repositori ini berisi source aplikasi, migration, lock file dependency, dan aset frontend. Database, file `.env`, kredensial, serta unggahan pengguna dipindahkan langsung ke lingkungan hosting melalui jalur privat.

## Instalasi

1. Pasang dependency PHP dengan `composer install --no-dev --prefer-dist --optimize-autoloader`.
2. Untuk membangun ulang aset, jalankan `npm ci` dan `npm run build`. Folder `public/assets` juga tersedia di source deployment.
3. Isi environment hosting berdasarkan `.env.hosting.example`. Gunakan `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS, dan zona waktu `Asia/Makassar`.
4. Arahkan document root ke folder `public`. Folder project, database dump, dan dokumen privat tidak boleh menjadi document root.
5. Hubungkan database MySQL tujuan. Untuk migrasi data yang sudah ada, pulihkan database terlebih dahulu lalu periksa `php artisan migrate:status`. Jalankan `php artisan migrate --force` untuk migration yang belum diterapkan. Jangan menjalankan `migrate:fresh` atau seeder pengembangan pada data yang dipindahkan.
6. Pertahankan APP_KEY yang sesuai untuk data lama. Gunakan key baru hanya untuk instalasi baru tanpa data terenkripsi sebelumnya.
7. Sediakan persistent storage untuk `storage/app`, dengan `private` tetap privat dan `public` untuk file yang memang boleh diakses publik. Pulihkan unggahan ke direktori masing-masing.
8. Jalankan `php artisan storage:link`, `php artisan config:cache`, `php artisan route:cache`, dan `php artisan view:cache` setelah environment final.
9. Jalankan `php artisan schedule:run` setiap menit melalui scheduler hosting. Paket yang menghentikan container saat idle perlu diverifikasi untuk kebutuhan scheduler ini.
10. Jalankan `php artisan hosting:check --database` dan uji alur aplikasi melalui URL hosting.

## Pemeriksaan sebelum penggunaan publik

- Pendaftaran dan login Peserta, persetujuan Penyelenggara/EO, event, tiket, dan scan QR.
- Konsistensi jumlah peserta, saldo, pengajuan penarikan manual, bukti transfer privat, dan notifikasi.
- Webhook Midtrans Sandbox pada `/payments/notification`, termasuk signature salah, notifikasi berulang, dan perubahan status pembayaran.
- Reset password melalui SMTP sampai email benar-benar diterima.
- Penyimpanan file setelah redeploy, akses dokumen privat, HTTPS/cookie/proxy, scheduler, dan backup/restore.

Keberhasilan tes lokal tidak otomatis membuktikan seluruh integrasi hosting bekerja. Penarikan dana dilakukan manual oleh Admin melalui bank; aplikasi mencatat persetujuan dan bukti transfer, bukan mengirim transfer bank melalui API.

## File yang tidak disimpan dalam Git

File `.env`, dump SQL, database SQLite, `vendor`, `node_modules`, cache runtime, log, serta unggahan pengguna dikecualikan. Berkas tersebut bukan bagian dari proses push source ini.
