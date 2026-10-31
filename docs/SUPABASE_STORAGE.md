# Penyimpanan berkas Supabase untuk SportManado

Laravel dan MySQL tetap berjalan di DockHosting. Supabase hanya menyimpan berkas melalui API S3. Konfigurasi bawaan tetap memakai disk lokal sehingga instalasi yang sudah mempunyai penyimpanan permanen tidak perlu berpindah.

## Bucket dan akses

- `sportmanado-public`: publik, hanya tipe `image/*`, maksimal 10 MB. Berisi banner, logo, foto galeri, gambar formulir dan QR SVG yang dibuat aplikasi.
- `sportmanado-private`: privat, maksimal 10 MB. Berisi dokumen identitas, proposal, izin dan bukti transfer. Jangan membuat kebijakan baca anonim atau unggah anonim pada bucket ini.
- Kunci S3 hanya berada pada environment server dan berkas konfigurasi privat di luar repository. Kunci S3 Supabase mempunyai akses semua bucket dan melewati RLS. Jangan mengirimnya ke frontend, mencetaknya dalam log, atau memasukkannya ke Git.
- Unduhan privat melalui Laravel memeriksa pemilik/Admin sebelum membaca berkas. Path traversal ditolak dan respons menggunakan `private, no-store` serta `nosniff`.

## Konfigurasi server

Isi variabel berikut setelah kedua bucket dan kunci server tersedia:

```dotenv
FILESYSTEM_DISK=local
MEDIA_STORAGE_DRIVER=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-northeast-1
AWS_ENDPOINT=https://PROJECT_REF.storage.supabase.co/storage/v1/s3
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_PUBLIC_BUCKET=sportmanado-public
AWS_PRIVATE_BUCKET=sportmanado-private
AWS_PUBLIC_URL=https://PROJECT_REF.supabase.co/storage/v1/object/public/sportmanado-public
```

Nama disk `local` tetap dipakai sebagai nama disk dokumen privat, tetapi drivernya menjadi S3 saat `MEDIA_STORAGE_DRIVER=s3`. Nama/path berkas dalam MySQL tidak berubah. URL media dibuat melalui disk `public`, bukan melalui symlink server.

Konfigurasi remote yang tidak lengkap, bucket publik/privat yang sama, atau endpoint HTTP akan ditolak. Aplikasi tidak diam-diam kembali menulis ke disk sementara. Aktifkan health check `/` pada hosting agar konfigurasi yang gagal tidak menerima trafik.

## Pemindahan berkas

Gunakan pasangan snapshot SQL dan `storage-app` yang sudah diverifikasi. Pastikan aplikasi belum menulis ke bucket tujuan selama pemindahan awal. Jalankan perintah pada salinan deployment dengan konfigurasi penyimpanan yang benar, bukan pada database utama lokal.

```text
php artisan storage:migrate /absolute/path/snapshot/storage-app
php artisan storage:migrate /absolute/path/snapshot/storage-app --apply --report=/private/path/migration-result.json
```

Perintah pertama hanya memeriksa tujuan. `--apply` mengunggah berkas yang belum ada dan membaca ulang untuk membandingkan SHA-256. Berkas yang sudah sama dilewati. Jika berkas tujuan berbeda, proses berhenti dan tidak menimpanya. Database dan sumber snapshot tidak diubah. Berkas yang sudah hilang dari sumber harus dilaporkan; dokumen identitas tidak boleh dibuat ulang secara fiktif.

## Verifikasi sebelum dipakai

1. Jalankan pengujian otomatis pada database terisolasi, termasuk `RemoteStorageCompatibilityTest` dan `StorageMigrationTest`.
2. Uji unggah/baca aktual ke kedua bucket dan cocokkan hash berkas.
3. Pastikan URL publik berfungsi tanpa login, sementara dokumen privat ditolak dari URL anonim dan akun lain.
4. Uji pembentukan QR baru, unduhan tiket, unggahan EO/Admin, dan bukti transfer tanpa melakukan transfer bank nyata.
5. Redeploy aplikasi dan pastikan hash berkas tetap sama.
6. Simpan backup berkas sendiri. Paket Free mempunyai kuota dan dapat dijeda karena tidak aktif; ini bukan jaminan ketersediaan untuk penggunaan operasional.

Migrasi berkas belum dianggap selesai sampai bukti unggah/baca dan akses privat pada layanan nyata tersedia. Email, webhook Midtrans dan scheduler membutuhkan pengujian terpisah.
