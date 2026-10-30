# Penarikan manual, 30 Oktober 2026

EO tetap mengisi formulir nominal, bank, nomor rekening dan nama pemilik rekening. Pengajuan menahan saldo selama menunggu keputusan. Penolakan mengembalikan saldo tersedia.

Admin melakukan transfer melalui layanan bank, kemudian memilih Unggah Bukti. Bukti JPG, PNG, WebP atau PDF maksimal 5 MB wajib diunggah sebelum persetujuan. Nomor referensi dan catatan bersifat opsional. Persetujuan menyimpan waktu konfirmasi WITA, identitas Admin dan bukti privat, lalu mengirim notifikasi kepada EO. Saldo tidak dipotong dua kali saat persetujuan. Aplikasi mencatat konfirmasi Admin; tidak mengirim transfer melalui API bank.

Bukti hanya dapat diakses Admin dan EO pemilik pengajuan. Riwayat lama tetap dipertahankan dan tidak dibuatkan bukti fiktif. Pengajuan lama yang masih mempunyai proses provider tertunda harus diperiksa sebelum transfer manual.

Jalankan migration 2026_10_30_000002_add_manual_transfer_proof_to_withdrawal_requests setelah backup database. Kolom baru nullable. Pertahankan storage/app/private secara permanen dan jangan publikasikan direktori tersebut. Konfigurasi pembayaran tiket Midtrans tetap berlaku; penarikan tidak membutuhkan kredensial Iris.

Notifikasi Admin memakai layout dan sidebar Admin. Label panel menjadi Admin. Banner simulasi penarikan dihapus dari tampilan.

Validasi pada salinan terisolasi: 378 tes dengan 1.548 assertion lulus pada masing-masing SQLite dan MySQL; 35 pemeriksaan browser alur manual dan 245 pemeriksaan regresi browser lulus pada lebar 320 sampai 1440 px. Tiga belas tes scanner dan konkurensi MySQL lulus. Pengujian mencakup unggahan wajib, PDF, akses bukti, penyimpanan gagal, rollback notifikasi, persetujuan ganda, saldo dan formulir EO.
