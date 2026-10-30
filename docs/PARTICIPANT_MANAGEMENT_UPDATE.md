# Pembaruan manajemen Peserta - 29 Oktober 2026

Kolom Aksi sebelumnya hanya menampilkan Setujui/Tolak pada tiket pending; peserta lain tidak memiliki tindakan edit. Tombol Edit Peserta kini tersedia pada setiap baris untuk EO pemilik kegiatan yang telah disetujui.

EO dapat memperbaiki nama/email pendaftaran, jawaban formulir kegiatan, catatan dan status tiket. Nama/email disimpan khusus pada tiket melalui kolom nullable participant_name dan participant_email; akun login, password, peran dan pendaftaran di kegiatan lain tidak diubah. Data lama tetap menggunakan nama/email akun sebagai nilai cadangan.

Status yang tersedia mengikuti keadaan tiket dan pembayaran. Tiket berbayar tidak dapat diaktifkan atau dicatat hadir tanpa pembayaran confirmed yang sah. Pengaktifan kembali tiket cancelled memeriksa kuota dengan transaksi dan lock database. Status used serta used_at tidak boleh diputar balik; data peserta tetap dapat dikoreksi. Kehadiran manual oleh EO hanya dapat dicatat dari tiket aktif, disertai konfirmasi pada formulir, waktu saat pencatatan dan check_in_method=manual.

Scanner menyimpan check_in_method=qr bersama used_at. Daftar peserta menampilkan tanggal dan jam hingga detik (WITA) di kolom Status dan detail peserta; hasil QR dibedakan dari kehadiran manual. Riwayat lama tanpa metode diberi label Waktu Validasi, dan tanpa timestamp ditampilkan sebagai belum tercatat. Waktu lama tidak dibuat-buat atau diubah saat edit.

Identitas pendaftaran yang diperbaiki juga dipakai pada tampilan tiket, dashboard EO, respons scanner dan ekspor peserta. Ekspor menambahkan waktu validasi dan sumber kehadiran. Filter status pada daftar peserta kini benar-benar menyaring data.

Migration tambahan: database/migrations/2026_10_29_000002_add_participant_details_to_tickets_table.php. Tiga kolom nullable ditambahkan; tidak ada data lama yang dihapus. Untuk hosting, jalankan migration sebelum mengaktifkan source baru. Rollback kode dapat membiarkan kolom ini tetap ada agar koreksi peserta dan sumber kehadiran tidak hilang.

Pengujian pada salinan terpisah: 322 tes / 1.256 assertion lulus pada SQLite dan MySQL. Sepuluh tes baru mencakup edit tanpa mengubah akun, hak akses/kepemilikan, validasi isian, pembayaran, kapasitas, pemakaian sekali, sumber kehadiran dan timestamp QR. Pengeditan lewat browser lulus pada lebar 320, 360, 390, 768 dan 1440 px, tanpa overflow halaman dan error JavaScript pada fixture.

## Pembatalan kepesertaan

Tindakan Batalkan Kepesertaan tersedia hanya untuk kegiatan gratis yang belum dicatat hadir. Alasan, pelaku dan waktu disimpan; tiket cancelled ditolak scanner dan kuotanya dilepas. Tiket berbayar diblokir dari pembatalan, perubahan ke cancelled dan penolakan manual EO sesuai keputusan tanpa refund. Expire/gagal dari Midtrans tetap mengikuti status provider. Migration tambahan 2026_10_29_000003 menyimpan metadata pembatalan. Regresi terbaru: 331 tes / 1.306 assertion lulus pada SQLite dan MySQL.
