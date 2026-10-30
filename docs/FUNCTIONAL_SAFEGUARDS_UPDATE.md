# Penyesuaian fungsional SportManado - 29 Oktober 2026

Empat penyesuaian yang disetujui telah diterapkan. Riwayat nilai sebelum/sesudah koreksi EO tidak ditambahkan, sesuai instruksi pengguna.

## Siklus kegiatan dan arsip

- Form buat/edit menyediakan **Selesai Event (WITA)** yang harus sesudah waktu mulai. Jika kosong, dipakai akhir hari tanggal mulai. Untuk kegiatan beberapa hari, isi waktu selesai secara eksplisit.
- `events:auto-complete` memakai waktu selesai tersebut. Kegiatan lama tanpa isian menggunakan akhir hari. Status completed didukung MySQL dan SQLite.
- Pemindaian QR, koreksi peserta serta persetujuan/penolakan manual ditutup setelah waktu selesai, termasuk ketika scheduler belum sempat berjalan.
- EO tetap dapat membuka arsip peserta dan CSV. Keuangan, ekspor keuangan serta pengajuan penarikan saldo tetap tersedia setelah kegiatan selesai. Scanner disembunyikan dari tab/sidebar arsip.
- Event selesai tidak dapat dibuka ulang atau dibatalkan melalui edit EO. Penyelesaian manual Admin tetap menjadi tindakan administratif yang disengaja.

## Reservasi pembayaran

- Reservasi berbayar baru berlaku **30 menit**. Antrean persetujuan gratis tidak memakai batas ini. Batas tampil pada tiket dan dikirim ke Snap sebagai expiry absolut dengan zona waktu Jakarta. Membuka halaman kembali tidak memperpanjang batas.
- Penanda permintaan Snap disimpan sebelum panggilan jaringan: timeout pembuatan token tidak dianggap bukti bahwa transaksi tidak pernah dibuat.
- `payments:expire-reservations` melepas reservasi baru yang tidak pernah meminta Snap. Transaksi provider diperiksa dahulu: pembayaran berhasil diaktifkan; pending diminta expire dan diperiksa kembali.
- Timeout, autentikasi gagal, nominal tidak cocok dan status ambigu mempertahankan kuota untuk dicoba kembali. Provider 404 tidak langsung dianggap pembayaran gagal.
- Snap baru dengan expiry absolut dapat dilepas atas 404 setelah batas ditambah tenggang 15 menit. Transaksi lama tanpa kontrak expiry baru dipertahankan; pelepasan atas 404 hanya setelah umur maksimum token tujuh hari sejak perubahan terakhir. Status akhir provider yang sah tetap diproses lebih awal.
- Pelepasan memakai transaksi database dengan lock event → tiket → pembayaran. Pembayaran sukses yang terlambat untuk tiket cancelled dicatat confirmed untuk rekonsiliasi, tanpa menghidupkan tiket atau mengambil kuota kembali.

Rujukan: [Midtrans Snap JSON Objects](https://docs.midtrans.com/reference/json-objects), [Expire a Snap Session](https://docs.midtrans.com/reference/expire-a-snap-session). Integrasi publik tetap perlu diuji pada Sandbox hosting tujuan.

## Riwayat akun dan pembatalan event

- Penghapusan sendiri/Admin ditolak jika akun memiliki tiket, pembayaran, profil EO atau permohonan kegiatan. Akun tanpa riwayat tersebut masih dapat dihapus. Penolakan penghapusan sendiri mempertahankan sesi dan menampilkan alasan pada dialog.
- Model memiliki pemeriksaan tambahan. Foreign key pemilik tickets, payments, organizers dan event_registration_requests menjadi RESTRICT sehingga penghapusan yang melewati model juga tidak menghapus riwayat berantai.
- Admin tidak dapat menghapus/menolak event dengan riwayat peserta. Pemeriksaan ulang di bawah lock event melindungi terhadap pendaftaran bersamaan; berkas kegiatan dengan riwayat dipertahankan.
- Pembatalan event melalui tombol atau edit status EO ditolak jika ada kepesertaan berbayar yang masih teralokasi atau pembayaran positif pending/confirmed. Perubahan ke draft/pending ikut diperiksa untuk mencegah penarikan publikasi melewati larangan.
- Pemeriksaan mencakup pembayaran historis meski harga event berubah. Event berbayar tanpa peserta/transaksi masih boleh dibatalkan. Larangan pembatalan kepesertaan berbayar tetap berlaku; refund tidak ditambahkan.

## Penerapan dan verifikasi

Migration baru: `2026_10_29_000004_add_event_end_and_payment_reservation_deadlines` dan `2026_10_29_000005_protect_user_history_foreign_keys`. Tidak ada penghapusan baris atau konversi tanggal historis. Kolom baru nullable.

Pengujian penuh dilakukan pada salinan SQLite dan MySQL terisolasi, mencakup arsip, penarikan setelah selesai, expiry, respons ambigu, perlombaan expiry/settlement, larangan penghapusan/pembatalan dan validasi waktu selesai. Browser mencakup 245 pemeriksaan regresi dan 42 pemeriksaan tambahan form/arsip pada 320–1440 px; tidak ada overflow halaman atau kesalahan JavaScript dalam skenario tersebut. Penghapusan akun dengan riwayat diuji lewat dialog browser.

Konkurensi MySQL: satu pendaftaran merebut slot terakhir, satu ditolak; satu scan berhasil, duplikat ditolak. Seluruh 13 pengujian umpan balik scanner lulus. Backup source/SQL, bukti, screenshot dan paket rilis berada di luar web root pada direktori pekerjaan `sportmanado-lifecycle-20261029`.

## Operasional hosting

Jalankan `php artisan schedule:run` setiap menit. Penyelesaian kegiatan dan rekonsiliasi reservasi dijadwalkan setiap menit tanpa overlap. Kegagalan per pembayaran dilaporkan ke log; exit code command gagal jika ada rekonsiliasi gagal, untuk dicoba kembali.

Tanpa cron, pelepasan reservasi dan status otomatis tidak berlangsung. Penutupan scan berdasarkan waktu selesai tetap diperiksa langsung. Sebelum publik, uji scheduler, HTTPS/session, webhook Midtrans Sandbox publik, SMTP, backup/restore serta scanner USB/perangkat nyata sesuai runbook. Tidak dilakukan deployment, refund, transaksi uang nyata atau pengiriman email nyata.
