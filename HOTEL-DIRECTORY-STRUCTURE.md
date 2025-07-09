# Hotel Senang Hati - Directory Structure

Berikut adalah struktur direktori utama dan penjelasan fungsinya untuk website Hotel Senang Hati:

```
hotel-app/
├── api/                  # Backend PHP API (jika diaktifkan, untuk integrasi data)
│   ├── index.php         # Entry point API
│   ├── config/           # Konfigurasi database
│   ├── controllers/      # Logic controller (Auth, Room, Reservation, dsb)
│   └── models/           # Model data (Room, Guest, Reservation, Payment)
├── assets/               # Asset statis (ikon, font, gambar, js, css, scss)
│   ├── css/              # CSS library (nucleo, soft-ui, dsb)
│   ├── fonts/            # Font custom
│   ├── img/              # Gambar asset (logo, icon, dsb)
│   ├── js/               # JS library
│   └── scss/             # SASS source (jika ada)
├── css/                  # CSS utama custom (premium, responsive, dsb)
├── database/             # File SQL skema database (jika backend diaktifkan)
├── img/                  # Gambar utama kamar, fasilitas, dsb
├── js/                   # JS utama custom (premium-clean.js, dsb)
├── lib/                  # Library eksternal (easing, lightbox, owlcarousel, waypoints)
├── index.html            # Landing page utama
├── room-detail.html      # Halaman detail kamar
├── reservation-form.html # Form reservasi (menuju WhatsApp)
├── reservation.html      # Riwayat reservasi
├── booking-confirmation.html / booking-status.html # Status booking
├── cart.html, menu.html, staff-login.html, manage-rooms.html, manage-guests.html, manage-reservations.html, reports.html # Halaman admin/staff
├── package.json          # Konfigurasi npm (jika ada)
├── LICENSE.txt           # Lisensi
├── *.md                  # Dokumentasi (logic, report, guide, dsb)
```

## Penjelasan Folder & File Penting
- **api/**: Untuk backend PHP (opsional, bisa diabaikan jika hanya frontend/WhatsApp).
- **assets/**: Semua asset library, font, gambar, dan style eksternal.
- **css/**: CSS custom utama untuk tampilan premium, responsive, dsb.
- **img/**: Gambar utama hotel, kamar, fasilitas.
- **js/**: Script utama custom (premium-clean.js = logic interaktif utama).
- **lib/**: Library eksternal (carousel, lightbox, dsb).
- **database/**: Skema SQL jika ingin backend.
- **index.html**: Landing page utama.
- **room-detail.html**: Halaman detail kamar (dinamis via parameter).
- **reservation-form.html**: Form booking, redirect ke WhatsApp.
- **reservation.html**: Riwayat booking.
- **booking-status.html**: Status booking.
- **Dokumentasi .md**: Semua dokumentasi logic, report, guide, dsb.

---

> Struktur ini memudahkan pengembangan, pemeliharaan, dan kolaborasi tim, serta memisahkan logic, asset, dan dokumentasi secara rapi.
