# SI-TAMU - Sistem Informasi Buku Tamu

aplikasi buku tamu digital untuk mencatat pengunjung yang membutuhkan informasi produk hukum di provinsi bali.

## Requirement

- php 7.4 atau lebih tinggi
- web server (apache, nginx, atau php built-in server)
- browser modern (chrome, firefox, edge, safari)

## Cara menggunakan

### 1. memulai aplikasi

buka terminal/command prompt di folder proyek, jalankan:

```bash
php -S localhost:8000
```

buka browser, akses: `http://localhost:8000`

### 2. pertama kali menggunakan

**setup database:**
- buka browser, akses: `http://localhost:8000/init_db.php`
- tunggu sampai muncul pesan "database initialized successfully"
- database otomatis dibuat

**buat akun admin:**
- klik tombol "daftar admin" di halaman utama
- isi form pendaftaran (nip, nama, password)
- login menggunakan akun yang sudah dibuat

### 3. mengisi buku tamu (untuk pengunjung)

- buka halaman utama
- isi form buku tamu:
  - nama pengunjung
  - nomor ktp
  - instansi
  - pekerjaan
  - informasi yang dibutuhkan
  - tujuan memperoleh informasi
- klik "simpan buku tamu"

### 4. melihat data (untuk admin)

- login dengan akun admin
- klik "dashboard admin"
- di dashboard bisa:
  - lihat statistik pengunjung
  - tambah/edit/hapus data kunjungan
  - export data ke csv atau excel

### 5. export data

- login sebagai admin
- klik tombol "csv" atau "excel" di bagian kanan atas
- file akan otomatis terdownload

## Catatan penting

- data disimpan di file `database.sqlite`
- jangan hapus file `database.sqlite` kalau tidak mau kehilangan data
- akses admin hanya untuk user yang terdaftar
- backup file `database.sqlite` secara berkala untuk keamanan data