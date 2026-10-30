# Hasil persiapan hosting SportManado

Tanggal: 29 Oktober 2026 (WITA). Status: persiapan dan verifikasi lokal selesai; penerimaan pada hosting publik masih diperlukan.

## Pengamanan

Snapshot source, backup SQL, vendor sebelum pembaruan, salinan aplikasi dan bukti pengujian berada di C:/Users/LENOVO/.codex/sportmanado-readiness-20261029, di luar document root. Salinan mencakup source yang belum terlacak Git. Tidak ada reset maupun seeder pada database utama; tidak ada push atau deployment. Kredensial .env utama dipertahankan.

## Perubahan

- Aset UI dibangun lokal: Tailwind 3, Alpine, Lucide, Flatpickr, font Inter/Archivo/Material Symbols. UI inti dapat diuji dengan koneksi pihak ketiga diblokir. Identitas visual dipertahankan.
- Tiket dan ringkasan pendaftaran menangani layar kecil serta teks panjang. QR menyesuaikan lebar kartu. Sidebar menangani fokus, Escape, backdrop, scroll dan perpindahan breakpoint. Animasi tidak lagi membatasi cakupan modal desktop.
- Interaksi catatan penarikan Organizer kini memperoleh Alpine. Pesan dan tombol pendaftaran membedakan gratis otomatis, persetujuan dan pembayaran sesuai controller. Form yang dipulihkan browser tidak terus terkunci setelah submit.
- Kegagalan tulis QR menghentikan transaksi, bukan menyimpan path palsu. QR aktif yang filenya hilang dapat diterbitkan kembali pada halaman tiket. Tiket used tetap terlindungi.
- Penggantian banner/dokumen mempertahankan file lama sampai penyimpanan dan perubahan database berhasil. Registrasi Organizer memakai transaksi database agar kegagalan upload tidak meninggalkan akun tanpa profil.
- Catatan Peserta yang sebelumnya diabaikan kini divalidasi (maksimal 2.000 karakter), disimpan, ditampilkan pada tiket/daftar peserta dan disertakan dalam CSV. Migration menambah satu kolom nullable tickets.notes tanpa menghapus kolom atau data lama.
- Ekspor CSV melindungi nilai pengguna yang dapat dianggap formula spreadsheet.
- Zona waktu dikonfigurasi melalui APP_TIMEZONE, default Asia/Makassar sesuai label WITA; data waktu lama tidak dikonversi massal.
- Template konfigurasi hosting, dukungan proxy yang dibatasi konfigurasi, halaman 419/500/503, perintah hosting:check, pembuatan Admin interaktif, pemblokiran seeder pengembangan di production dan paket rilis yang mengecualikan data privat ditambahkan.
- Migration/factory/seeder tidak lagi tertutup aturan ignore seluruh folder database. File SQL/SQLite dan konfigurasi MySQL lokal tetap diabaikan. Tidak ada commit atau staging Git otomatis.
- Pengujian dipaksa memakai lingkungan testing dan database terisolasi. Guard menolak database lain serta config cache.

## Pembaruan keamanan

Audit awal Composer menemukan 24 advisory pada enam paket; keterjangkauan setiap advisory melalui SportManado tidak diasumsikan. Pembaruan terarah tetap dalam Laravel 12: framework 12.63.0 → 12.70.2; Guzzle 7.13.2 → 7.16.1; PSR7 2.12.3 → 2.13.2; Promises 2.5.0 → 2.6.0; CommonMark 2.8.2 → 2.10.4; Flysystem 3.35.2 → 3.36.0; Symfony HTTP Foundation 7.4.14 → 7.4.22; Mime 7.4.13 → 7.4.22. Audit sesudah pembaruan: tidak ada advisory produksi yang dilaporkan. Npm audit: 0 advisory pada dependency terkunci setelah pembaruan transitif shell-quote.

Sumber primer: [Laravel advisory](https://github.com/laravel/framework/security/advisories/GHSA-f9x3-7vq9-cph6), [Symfony HTTP Foundation](https://symfony.com/cve-2026-108082). Audit dependency adalah hasil pada tanggal pemeriksaan, bukan jaminan bebas semua kerentanan.

## Bukti pengujian

| Pemeriksaan | Hasil |
|---|---|
| Baseline sebelum perubahan | 303 tes, 1.173 assertion lulus |
| Regresi sesudah pembaruan keamanan, SQLite memory | 312 tes, 1.208 assertion lulus |
| Regresi sesudah pembaruan keamanan, MySQL terpisah | 312 tes, 1.208 assertion lulus |
| Feedback scanner JavaScript | 13 tes lulus |
| Responsif Chromium | 245 kombinasi halaman/viewport lulus; tidak ada overflow halaman atau error JavaScript pada fixture |
| Lebar viewport | 320, 360, 390, 430, 768, 1024, 1440 px |
| Alur browser dengan session database dan CSRF aktif | 6 pemeriksaan lulus |
| Dua proses PHP mendaftar slot terakhir di MySQL | 302 dan 422; tepat satu tiket |
| Dua proses PHP memindai tiket yang sama di MySQL | 200 dan 422; tiket used |
| Route cache dan kompilasi Blade | Berhasil pada salinan aplikasi dan instalasi dependency produksi tanpa dev |
| Pemeriksaan ulang kartu Admin | 14 kombinasi tambahan lulus |
| Backup/restore lokal | Dump dipulihkan ke database terpisah; hash data inti cocok |
| Data inti utama setelah penerapan | Jumlah dan hash sembilan tabel inti tetap sama (kolom notes baru dikecualikan dari perbandingan) |
| HTTP aplikasi utama | Beranda, login, form daftar dan aset lokal merespons 200 di port 8000 |
| Platform dependency produksi | Lulus pada PHP CLI 8.2.4 lokal |

Enam pemeriksaan browser mencakup registrasi/login, gratis otomatis beserta QR/catatan, persetujuan manual, input scanner keyboard JSON dan penolakan scan ulang, CSV catatan, serta penolakan akses scanner dari Peserta. Scanner USB fisik belum diuji; input keyboard disimulasikan melalui browser.

Bukti tersimpan di direktori evidence dalam lokasi snapshot, termasuk JUnit, JSON responsif, hasil konkurensi, audit dependency dan screenshot mobile/desktop. Kasus kosong/penuh dan variasi data di luar fixture tetap membutuhkan pemeriksaan tambahan jika data operasional berbeda.

## Batas sebelum GO publik

Belum dilakukan deployment atau beta testing manusia. Uji di hosting tujuan tetap wajib: HTTPS/proxy/cookie, document root, permission dan storage link, Midtrans Sandbox dengan webhook publik, penerimaan email SMTP, scheduler, scanner USB nyata, perangkat Android/iOS nyata, landscape/zoom/keyboard serta pengukuran kapasitas dan pemulihan backup. Pembayaran dalam tes otomatis memakai simulasi provider; keberhasilan transaksi Sandbox publik belum diklaim.

Penarikan nonproduksi tetap simulator implementasi yang sudah ada. Verifikasi email tidak diaktifkan secara paksa agar alur registrasi tidak berubah. Pembaruan terbaru menutup scan untuk kegiatan completed dan setelah waktu selesai. Main .env tetap lokal; gunakan .env.hosting.example saat menyiapkan server.

Keputusan: GO untuk menyiapkan staging hosting; CONDITIONAL GO untuk publik setelah pemeriksaan penerimaan di atas lulus. Ikuti [HOSTING_RUNBOOK.md](HOSTING_RUNBOOK.md).

## Artefak operasional

Kolom notes sudah diterapkan secara terarah pada database lokal utama. Tidak ada migration lain yang dijalankan pada penerapan ini. Vendor utama disesuaikan dengan lock file terverifikasi, npm ci/build berhasil, dan .env utama memiliki hash yang sama dengan snapshot. Backup dan bukti berada di luar web root.

Git masih berisi perubahan pengguna sebelum pekerjaan ini. Aturan ignore tidak menghapus file runtime yang sudah terlanjur terlacak sebelumnya; tinjau file storage/cache yang terlacak sebelum push di masa depan. Paket rilis menggunakan daftar direktori eksplisit dan mengecualikan upload/cache/database sehingga tidak bergantung pada kebersihan index Git.

Pemeriksaan akhir file upload: 87 file publik/privat dari snapshot memiliki hash yang sama sesudah penerapan.

## Pembaruan manajemen Peserta

EO kini dapat mengedit data pendaftaran dan status, serta melihat waktu validasi hingga detik dalam WITA. Pengujian terbaru: 322 tes / 1.256 assertion lulus pada SQLite dan MySQL. Lihat [PARTICIPANT_MANAGEMENT_UPDATE.md](PARTICIPANT_MANAGEMENT_UPDATE.md). Paket hosting versi peserta menyertakan migration tambahan 2026_10_29_000002.

## Pembaruan pembatalan dan evaluasi fungsional

Pembatalan gratis tersedia dengan alasan/waktu/pelaku; kepesertaan berbayar tidak dapat dibatalkan manual. Regresi terbaru lulus 331 tes / 1.306 assertion pada SQLite dan MySQL. Pemeriksaan browser mengonfirmasi larangan pembatalan berbayar dan responsivitas form. Evaluasi menyeluruh berikutnya tersedia di [FUNCTIONAL_EVALUATION_20261029.md](FUNCTIONAL_EVALUATION_20261029.md).


## Pembaruan empat perlindungan fungsional

Siklus selesai/arsip, batas reservasi berbayar, perlindungan penghapusan akun dan larangan pembatalan seluruh event berbayar telah diterapkan. Riwayat nilai sebelum/sesudah koreksi EO tidak ditambahkan sesuai keputusan pengguna. Detail: [FUNCTIONAL_SAFEGUARDS_UPDATE.md](FUNCTIONAL_SAFEGUARDS_UPDATE.md).

Hasil terbaru: **353 tes / 1.417 assertion lulus pada masing-masing SQLite dan MySQL**. Browser: 245 pemeriksaan umum dan 42 pemeriksaan form/arsip pada 320–1440 px, tanpa overflow halaman atau kesalahan JavaScript. Penghapusan akun dengan riwayat diblokir melalui UI. Konkurensi MySQL dan 13 pengujian scanner lulus.

Migration 000004 dan 000005 telah diterapkan secara terarah pada database lokal utama setelah backup SQL. Cron hosting harus menjalankan schedule:run setiap menit agar penyelesaian otomatis dan rekonsiliasi reservasi bekerja. Uji layanan publik pada hosting tujuan masih diperlukan.


## Filter, pencarian, saldo dan notifikasi: 30 Oktober 2026

Pencarian/filter/pagination peserta, keuangan dan daftar terkait telah diselaraskan. Menghapus kata pencarian mengembalikan daftar otomatis. Kuota di beranda/dashboard/Admin mengikuti definisi reservasi yang sama. Dashboard EO dan keuangan memakai perhitungan saldo yang sama, dengan penarikan pending menahan dana dan persetujuan tidak memotong ulang.

Notifikasi peserta, EO dan Admin tersedia melalui inbox umum; badge dan jumlah belum dibaca dihitung dari seluruh halaman dan diperbarui setelah tindakan baca. Pendaftaran, transisi tiket, pengajuan dan keputusan terkait mengirim pemberitahuan dalam aplikasi. Penyelesaian otomatis kegiatan juga memberi pemberitahuan.

Pencairan produksi tidak lagi diselesaikan hanya karena HTTP sukses atau memakai referensi buatan. Klaim permintaan dikomit sebelum jaringan; status/reference/nominal diverifikasi sebelum status approved. Transfer bank nyata dan kontrak API merchant masih harus diuji tersendiri. Simulator memiliki keterangan eksplisit.

Verifikasi: **371 tes / 1.499 assertion pada masing-masing SQLite dan MySQL**, 245 pemeriksaan browser regresi dan 47 pemeriksaan tambahan (292 total), tanpa overflow halaman atau error JavaScript pada skenario yang diuji. Konkurensi saldo/keputusan penarikan, pendaftaran dan scanner lulus; 13 tes umpan balik scanner lulus. Migration baru telah diterapkan secara terarah setelah backup. Lihat [SEARCH_FINANCE_NOTIFICATIONS_20261030.md](SEARCH_FINANCE_NOTIFICATIONS_20261030.md).

Pembaruan terbaru: alur pencairan diganti menjadi transfer manual dengan bukti wajib. Bagian pencairan otomatis sebelumnya digantikan oleh [MANUAL_WITHDRAWALS_20261030.md](MANUAL_WITHDRAWALS_20261030.md). Validasi terbaru: 378 tes / 1.548 assertion pada SQLite dan MySQL, serta 280 pemeriksaan browser lulus.
