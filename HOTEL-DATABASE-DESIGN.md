# Rancangan Database Hotel Senang Hati

## 1. Deskripsi Sistem
Hotel Senang Hati adalah hotel berkembang dengan 100 kamar, 4 restoran, dan berbagai fasilitas pendukung. Proses reservasi dapat dilakukan melalui telepon, email, atau langsung di front-desk. Setiap tamu wajib melakukan pemesanan, namun tamu walk-in tetap dilayani jika kamar tersedia. Proses check-in melibatkan verifikasi kode pesanan, pengecekan ketersediaan kamar, dan pencatatan data tamu serta metode pembayaran. Pembayaran dapat dilakukan dengan tunai, debit, kredit, atau voucher.

## 2. Analisis Kebutuhan Data
### Entitas Utama:
- **Tamu**: Data identitas tamu yang menginap.
- **Kamar**: 100 kamar dengan tipe, harga, dan status.
- **Tipe Kamar**: Kategori kamar (misal: Standard, Deluxe, Suite) beserta kapasitas dan harga.
- **Reservasi**: Data pemesanan kamar (kode pesanan, tanggal, status, dsb).
- **Pembayaran**: Data transaksi pembayaran (metode, jumlah, status).
- **Restoran**: 4 restoran milik hotel.
- **Fasilitas**: Fasilitas hotel (kolam renang, gym, spa, dsb).
- **Petugas**: Data front-desk/admin hotel.

### Relasi Utama:
- Satu tamu bisa memiliki banyak reservasi.
- Satu reservasi bisa memesan lebih dari satu kamar.
- Satu kamar hanya bisa ditempati satu reservasi pada satu waktu.
- Satu reservasi memiliki satu pembayaran.

## 3. Rancangan Tabel (ERD Sederhana)

- **TAMU** (`id_tamu`, nama, no_identitas, telepon, email, alamat)
- **PETUGAS** (`id_petugas`, nama, username, password, kontak)
- **TIPE_KAMAR** (`id_tipe`, nama_tipe, kapasitas, harga)
- **KAMAR** (`no_kamar`, id_tipe, status)
- **RESERVASI** (`id_reservasi`, id_tamu, tgl_pesan, tgl_checkin, tgl_checkout, status, kode_reservasi, metode_pesan, id_petugas)
- **RESERVASI_DETAIL** (`id_reservasi_detail`, id_reservasi, no_kamar)
- **PEMBAYARAN** (`id_pembayaran`, id_reservasi, metode, jumlah, status, tgl_bayar)
- **RESTORAN** (`id_resto`, nama_resto, lokasi, jam_buka, dsb)
- **FASILITAS** (`id_fasilitas`, nama_fasilitas, deskripsi, lokasi)

## 4. Penjelasan Proses Bisnis
- **Pemesanan**: Tamu melakukan reservasi (telepon/email/langsung), data dicatat di tabel RESERVASI.
- **Check-in**: Petugas cek kode reservasi, cek ketersediaan kamar, update status kamar & reservasi.
- **Walk-in**: Jika tanpa reservasi, petugas cek kamar kosong, buat reservasi baru jika tersedia.
- **Pembayaran**: Dicatat di tabel PEMBAYARAN, metode bisa tunai, debit, kredit, voucher.
- **Check-out**: Update status kamar menjadi kosong, reservasi selesai.
- **Jika kamar tidak tersedia**: Petugas rekomendasikan hotel lain.
- **Jika tamu datang lebih awal**: Petugas cek kamar, jika tidak tersedia carikan kamar setara.

## 5. Tahapan Merancang Basis Data
1. **Analisis kebutuhan data** (sudah dijelaskan di atas).
2. **Identifikasi entitas dan atribut** (TAMU, KAMAR, RESERVASI, dsb).
3. **Buat relasi antar entitas** (ERD).
4. **Normalisasi** untuk menghindari duplikasi data.
5. **Implementasi skema ke SQL** (lihat file `database/schema.sql`).
6. **Uji coba transaksi** (insert, update, delete, query join).
7. **Optimasi & backup**.

## 6. Contoh Skema SQL (Sederhana)
Lihat file: `database/schema.sql` untuk implementasi detail.

---

> Rancangan ini menjamin transaksi hotel berjalan lancar, data konsisten, dan mudah dikembangkan untuk kebutuhan operasional Hotel Senang Hati.
