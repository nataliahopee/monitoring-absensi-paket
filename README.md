# Monitoring Absensi Piket (RFID)

Aplikasi **Monitoring Absensi Piket** berbasis web untuk mencatat **check-in** dan **check-out** pegawai menggunakan **RFID tap card**.  
Project ini dibuat sebagai **proof of concept** sistem absensi piket dengan alur tap sederhana dan monitoring real-time.

---

## ✨ Fitur Utama

-   📋 Monitoring absensi piket (check-in & check-out)
-   🪪 Identifikasi pegawai berdasarkan **RFID UID**
-   🔁 Satu route API untuk tap kartu (auto check-in / check-out)
-   🔍 Pencarian data absensi (nama pegawai / RFID / tanggal)
-   📄 Pagination & pengaturan jumlah data per halaman
-   🎨 Tampilan sederhana dengan **Blade + Tailwind CSS**
-   🧪 Testing API menggunakan **Postman**

---

## 🧠 Konsep Sistem

### Alur Tap RFID

1. **Tap pertama (hari yang sama)** → dicatat sebagai **check-in**
2. **Tap kedua (hari yang sama)** → dicatat sebagai **check-out**
3. **Tap lebih dari 2 kali di hari yang sama** → ❌ ditolak oleh sistem
4. **Tap di hari berikutnya** → sistem otomatis membuat absensi baru

> Sistem dirancang **harian**, setiap pegawai hanya memiliki **1 record absensi per hari**.

---

## 🗂️ Struktur Database

### Tabel `pegawai`

| Kolom        | Tipe            |
| ------------ | --------------- |
| id           | bigint          |
| rfid_uid     | string (unique) |
| nama_pegawai | string          |
| created_at   | timestamp       |
| updated_at   | timestamp       |

### Tabel `absensi_piket`

| Kolom      | Tipe                |
| ---------- | ------------------- |
| id         | bigint              |
| pegawai_id | foreign key         |
| tanggal    | date                |
| check_in   | datetime (nullable) |
| check_out  | datetime (nullable) |
| created_at | timestamp           |
| updated_at | timestamp           |

---

## ⚙️ Tech Stack

-   **Laravel 11**
-   **Blade Template**
-   **Tailwind CSS**
-   **MySQL**
-   **Postman** (API testing)
-   **RFID Simulator** (sementara via API)

---

## 🚀 Instalasi & Menjalankan Project

### 1️⃣ Clone Repository

```bash
git clone https://github.com/nataliahopee/monitoring-absensi-paket.git
cd monitoring-absensi-piket
```

---

### 2️⃣ Install Dependency

```bash
composer install
npm install
```

---

### 3️⃣ Konfigurasi Environment

```bash
cp .env.example .env
DB_CONNECTION=mysql
DB_DATABASE=absensi-piket-db
php artisan key:generate
```

---

### 4️⃣ Migrasi & Seeder

```bash
php artisan migrate --seed
```

---

### 5️⃣ Jalankan Server

```bash
php artisan serve
npm run dev
```

#### Akses Aplikasi

```bash
http://127.0.0.1:8000
```

---
