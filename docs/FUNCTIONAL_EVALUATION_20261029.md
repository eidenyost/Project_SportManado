# Evaluasi fungsional SportManado

Tanggal: 29 Oktober 2026. Dasar: pemeriksaan source, regresi otomatis serta browser lokal terisolasi.

Alur inti mencakup akun/peran, verifikasi EO, pengelolaan kegiatan, pendaftaran gratis otomatis/persetujuan dan berbayar, kuota, tiket/QR, kehadiran sekali pakai, koreksi peserta, pembatalan gratis, ekspor serta keuangan Sandbox. Kepesertaan berbayar tidak dapat dibatalkan manual karena refund belum tersedia.

Empat prioritas evaluasi telah ditangani:

| Prioritas sebelumnya | Hasil penyesuaian |
|---|---|
| Siklus selesai dan akses arsip | Waktu selesai opsional dengan fallback akhir hari; scanner ditutup setelah selesai; arsip peserta, ekspor dan keuangan tetap tersedia. |
| Reservasi pending menahan kuota | Batas 30 menit untuk reservasi baru, expiry absolut Snap dan rekonsiliasi sebelum pelepasan. Status ambigu mempertahankan kuota. |
| Penghapusan akun menghapus riwayat | Pemeriksaan penghapusan sendiri/Admin/model dan foreign key RESTRICT melindungi riwayat. |
| Pembatalan seluruh event berbayar | Pembatalan/penarikan publikasi EO diblokir jika ada kepesertaan atau pembayaran berbayar; penghapusan event berisi peserta oleh Admin dibatasi. |

Penjelasan kebijakan, migrasi dan operasional tersedia pada [FUNCTIONAL_SAFEGUARDS_UPDATE.md](FUNCTIONAL_SAFEGUARDS_UPDATE.md).

Riwayat nilai sebelum/sesudah koreksi EO tidak dikerjakan sesuai instruksi pengguna. Kebijakan daftar ulang mandiri setelah cancelled juga tidak diubah: satu akun tetap satu pendaftaran per kegiatan; reaktivasi melalui EO mengikuti syarat status, pembayaran dan kuota.

Verifikasi terbaru: **353 tes / 1.417 assertion lulus pada masing-masing SQLite dan MySQL**, 245 pemeriksaan browser regresi umum dan 42 pemeriksaan tambahan form/arsip tanpa overflow halaman atau kesalahan JavaScript. Uji konkurensi MySQL dan 13 pengujian umpan balik scanner lulus.

Project siap dilanjutkan ke staging hosting. Keberhasilan lokal belum membuktikan layanan hosting tujuan: webhook Midtrans Sandbox publik, SMTP, cron, HTTPS/session, permission/storage, pemulihan backup dan scanner USB/perangkat nyata tetap harus diuji sebelum akses publik. Ikuti [HOSTING_RUNBOOK.md](HOSTING_RUNBOOK.md).
