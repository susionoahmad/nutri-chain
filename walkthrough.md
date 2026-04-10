# Deployment Guide - Vercel, Railway, & Cloudinary

Kode telah dimodifikasi agar siap di-deploy (menggunakan arsitektur "stateless" modern multi-cloud).

## 1. Persiapan Cloudinary (Penyimpanan Foto Permanen)
Karena Railway menghapus file lokal setiap kali ada pembaruan kode, kami telah mengintegrasikan paket resmi `cloudinary-labs/cloudinary-laravel`.
- Buat akun gratis di **[Cloudinary](https://cloudinary.com/users/register/free)**.
- Di Dashboard Console Cloudinary, temukan tab _"API Keys"_ atau _"Dashboard"_. Anda akan melihat `API Environment variable` dengan format seperti:
  `CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME`
- Simpan URL ini untuk dipakai di Railway nanti.

## 2. Deployment Backend (Railway + MySQL)
1. Buka **[Railway.app](https://railway.app/)** dan buat _New Project_.
2. Pilih **Deploy from GitHub repo** dan pilih repository *Nutri-Chain* Anda.
3. Railway secara otomatis mencari kode `backend` dan menemukan file `Procfile` yang telah saya buatkan:
   `web: php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT`
   *(Aturan ini mengeksekusi migrasi database secara otomatis saat booting).*
4. Dalam project Railway yang sama, klik **New > Database > Add MySQL** agar otomatis terinstall.
5. Klik komponen Backend Anda -> Buka tab **Variables** dan masukkan:
    - `APP_ENV=production`
    - `APP_KEY` (copy dari file .env lokal Anda)
    - `APP_DEBUG=false`
    - `DB_CONNECTION=mysql`
    - _Isi DB_HOST, DB_PASSWORD, dll menggunakan fitur Reference Variable ke plugin MySQL yang baru dibuat._
    - `CLOUDINARY_URL=cloudinary://[paste-key-Anda-disini]`
6. **(CORS):** Setelah frontend selesai (Langkah 3), ubah `SANCTUM_STATEFUL_DOMAINS` dan masukan domain Vercel Anda di sini.

## 3. Deployment Frontend (Vercel)
1. Buka **[Vercel.com](https://vercel.com/)** dan *Add New Project*.
2. Pilih repository GitHub Anda.
3. Di dalam bagian _"Framework Preset"_, Vercel akan otomatis mengenali **Vite/Vue**.
4. Di bagian **Root Directory**, klik Edit dan pilih `frontend`.
5. Buka tab **Environment Variables** lalu tambahkan:
    - Key: `VITE_API_URL`
    - Value: `https://[nama-domain-railway-anda].up.railway.app/api`
6. Klik **Deploy**!

> [!TIP]
> **Penting Saat Testing:** 
> Pastikan Cloudinary URL telah disetel _sebelum_ mencoba upload bukti transfer. Jika kosong, sistem otomatis mengembalikan status 500 error sebagai perlindungan *fail-safe* agar transaksi tidak terkatung-katung.

Platform Anda sekarang memiliki keandalan file yang permanen dan arsitektur SaaS berskala modern!
