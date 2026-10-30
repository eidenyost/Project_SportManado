# Pembaruan filter, pencarian, saldo dan notifikasi

Tanggal: 30 Oktober 2026. Perubahan dipersiapkan dan diuji pada salinan terisolasi sebelum diterapkan pada project utama.

## Filter dan pencarian

- Halaman Peserta menyediakan pencarian nama pendaftaran, email dan kode tiket. Identitas yang dikoreksi EO menjadi acuan; data akun dipakai apabila identitas pendaftaran belum diisi.
- Filter Semua, Pending, Aktif, Hadir dan Dibatalkan dapat digabungkan dengan pencarian. Pagination mempertahankan query; mengganti status mengembalikan ke halaman pertama. Angka tiap chip berasal dari seluruh peserta event, sementara jumlah hasil menunjukkan pencarian/filter saat ini.
- Pencarian juga tersedia pada keuangan event, Tiket Saya, notifikasi, verifikasi EO, permohonan event dan pengajuan penarikan Admin. Pencarian event, pengguna dan monitor pembayaran yang sudah ada tetap digunakan; monitor pembayaran kini memproses filter statusnya.
- Kolom pencarian menggunakan jeda 450 ms. Saat dikosongkan, parameter search dan page dihapus otomatis tanpa harus menekan Cari. Filter lain seperti kategori/status tetap dipertahankan. Tombol Cari dan pengiriman formulir biasa tetap tersedia.
- Ekspor peserta dan keuangan mengikuti pencarian serta filter yang dipilih, tanpa dibatasi halaman pagination.
- Filter peserta, transaksi dan kategori mobile menggunakan satu baris yang dapat digeser. Tabel peserta memiliki lebar minimum dan kode tidak dipotong menjadi beberapa baris. Header tablet mengurangi teks agar badge notifikasi dan profil tidak saling mendesak.

## Angka peserta, kuota dan saldo

Kuota memakai status pending, active dan used sebagai tempat yang teralokasi. Cancelled melepas tempat. Kartu event pada beranda, dashboard peserta, daftar event dan Admin mengikuti definisi yang sama. Angka historis seluruh pendaftaran, peserta disetujui dan kehadiran memiliki label berbeda karena mengukur hal berbeda.

Pemasukan historis tetap dihitung dari pembayaran confirmed. Saldo tersedia dihitung dari pemasukan dikurangi penarikan approved dan pengajuan pending. Dashboard EO menampilkan saldo tersedia, menunggu pencairan dan sudah dicairkan menggunakan perhitungan yang sama dengan halaman keuangan.

| Skenario uji | Pemasukan | Menunggu | Dicairkan | Tersedia |
|---|---:|---:|---:|---:|
| Awal | 100.000 | 0 | 0 | 100.000 |
| Ajukan 25.000 | 100.000 | 25.000 | 0 | 75.000 |
| Pengajuan disetujui | 100.000 | 0 | 25.000 | 75.000 |
| Cabang lain: pengajuan pending ditolak | 100.000 | 0 | 0 | 100.000 |

Persetujuan tidak memotong dana dua kali karena dana telah ditahan saat pengajuan. Penolakan hanya boleh pada pengajuan pending yang belum dikirim ke provider. Jumlah penarikan baru harus berupa rupiah utuh. Pengajuan, persetujuan dan penolakan memakai urutan lock organizer, event dan withdrawal. Persetujuan saldo tidak mencukupi dan keputusan berulang ditolak.

## Pencairan produksi dan simulator

Mode Sandbox menampilkan keterangan simulasi dan notifikasi yang tidak mengklaim transfer bank nyata. Pada produksi, HTTP sukses saat membuat payout tidak langsung mengubahnya menjadi approved.

Kolom nullable baru `withdrawal_requests.payout_requested_at` mencatat klaim permintaan sebelum panggilan jaringan dan dikomit terpisah dari panggilan tersebut. Timeout atau respons tanpa referensi tidak mengirim permintaan ulang otomatis dan tidak melepas dana. Referensi provider disimpan tanpa membuat nomor referensi pengganti. Admin memakai Periksa Status pada referensi yang ada; pencairan hanya diselesaikan jika respons detail memiliki referensi yang sama, nominal sesuai dan status completed. Pengajuan yang sudah dikirim tidak dapat ditolak manual untuk melepas dana yang mungkin sedang diproses provider. Permintaan produksi tidak dapat diselesaikan dengan simulator setelah pergantian konfigurasi.

Konfigurasi opsional `MIDTRANS_IRIS_API_KEY` memisahkan kredensial Iris dari kredensial pembayaran. Integrasi produksi tetap harus divalidasi menggunakan akun/kontrak API merchant yang sebenarnya. Pemeriksaan otomatis memakai respons HTTP tiruan, tidak mengirim uang. Respons ambigu tanpa referensi memerlukan rekonsiliasi melalui panel provider; tidak dipaksa dianggap berhasil atau gagal.

Migration: `2026_10_30_000001_add_payout_requested_at_to_withdrawal_requests_table`. Tidak ada penghapusan atau perubahan nilai historis yang diperlukan.

## Notifikasi dalam aplikasi

- Inbox umum `/notifications` tersedia untuk peserta, EO dan Admin. Kepemilikan notifikasi tetap diperiksa pada endpoint baca dan tandai dibaca. EO yang menunggu/ditolak dapat membaca pemberitahuan tanpa membuka fitur pengelolaan yang dibatasi.
- Pendaftaran menghasilkan pemberitahuan untuk peserta dan EO. Perubahan status tiket, kehadiran beserta waktu WITA, pembatalan dan keputusan permohonan event memberi pemberitahuan kepada pemiliknya. Pengajuan EO/event dan penarikan menginformasikan Admin terkait. Pengajuan penarikan juga memberi tanda terima bagi EO.
- Penyelesaian event melalui scheduler dan Admin memakai pemberitahuan yang sama kepada EO/peserta. Keputusan persetujuan pengajuan yang sudah diproses ditolak agar tidak menggandakan event atau notifikasi.
- Jumlah belum dibaca dihitung dari seluruh halaman, tidak hanya 15 baris yang terlihat. Respons tandai dibaca mengembalikan jumlah aktual server dan langsung memperbarui badge. Polling 30 detik memperbarui perubahan dari sesi lain, berhenti saat halaman tidak terlihat dan melanjutkan saat aktif.
- Pemberitahuan mengikuti transaksi perubahan data: rollback pendaftaran tidak meninggalkan pemberitahuan palsu. Koreksi catatan tanpa perubahan status tidak mengirim ulang pemberitahuan status. Tidak ditambahkan riwayat nilai sebelum/sesudah edit EO.
- Notifikasi lama dipertahankan; tidak dibuat backfill pemberitahuan historis. Pengiriman email SMTP nyata merupakan pemeriksaan terpisah pada hosting tujuan.

## Verifikasi

- Regresi backend: **371 tes / 1.499 assertion** lulus pada masing-masing SQLite dan MySQL terisolasi.
- Browser khusus: **47 pemeriksaan ukuran/halaman**, ditambah alur gabungan filter/search, pengosongan search, penarikan/persetujuan tanpa pemotongan ganda dan pembaruan jumlah notifikasi.
- Regresi browser umum mencakup halaman lintas peran pada 320, 360, 390, 430, 768, 1024 dan 1440 px. Bukti lengkap berada di folder pekerjaan di luar web root.
- Konkurensi MySQL: dua pengajuan Rp75.000 terhadap saldo Rp100.000 hanya menghasilkan satu reservasi; persetujuan dan penolakan bersamaan hanya menghasilkan satu keputusan dan satu notifikasi keputusan. Konkurensi slot terakhir serta scan QR sekali pakai juga lulus.
- Seluruh 13 tes umpan balik scanner lulus. Validasi template Blade dan build aset lokal berhasil.

Backup source, SQL, fingerprint data, hasil tes dan screenshot disimpan pada `C:\Users\LENOVO\.codex\sportmanado-search-finance-notifications-20261030`. Pekerjaan ini tidak melakukan setup/deployment alwaysdata, transfer bank nyata atau pengiriman email nyata.

Pembaruan terbaru: alur pencairan diganti menjadi transfer manual dengan bukti wajib. Bagian pencairan otomatis sebelumnya digantikan oleh [MANUAL_WITHDRAWALS_20261030.md](MANUAL_WITHDRAWALS_20261030.md). Validasi terbaru: 378 tes / 1.548 assertion pada SQLite dan MySQL, serta 280 pemeriksaan browser lulus.
