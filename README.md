# 8BallBooking — Backend API

Backend API-first untuk platform booking meja biliard multi-tenant.
Stack: **Laravel 13** (PHP 8.3), Sanctum, Spatie Permission, Redis, Reverb, Horizon, Scramble (OpenAPI), Pest.

> Lihat `../Development.md` untuk spesifikasi produk & arsitektur lengkap.

## Kebutuhan

- PHP 8.3+
- Composer 2
- MySQL 8 (via Laragon)
- Redis (via Laragon, untuk cache/queue/session)

## Setup

```bash
composer install
cp .env.example .env      # Windows: Copy-Item .env.example .env
php artisan key:generate
```

Buat database lalu migrasi + seed:

```bash
php artisan migrate --seed
```

## Menjalankan (development)

Pastikan MySQL dan Redis berjalan (Laragon). Lalu:

```bash
php artisan serve
# opsional untuk queue & real-time:
php artisan queue:work
php artisan reverb:start
```

## Konvensi API

- Prefix: `/api/v1`
- Envelope respons konsisten:

```json
{ "success": true, "message": "Success", "data": {}, "meta": {} }
```

- Auth header: `Authorization: Bearer <token>`
- Endpoint auth tersedia:
  - `POST /api/v1/auth/register`
  - `POST /api/v1/auth/login`
  - `GET  /api/v1/auth/me`      (butuh token)
  - `POST /api/v1/auth/logout`  (butuh token)

## Roles

Guard `api` (Sanctum). Role bawaan di-seed oleh `RoleSeeder`:
`super_admin`, `store_owner`, `store_staff`, `user`.

## Dokumentasi API

OpenAPI otomatis (Scramble) tersedia di `/docs/api` dan `/docs/api.json`.
Akses dibatasi untuk environment `local` atau via Gate `viewApiDocs`.

## Testing & Linting

```bash
php artisan test          # Pest
./vendor/bin/pint         # formatter (tambah --test untuk cek saja)
./vendor/bin/pint --test
```

## Catatan

- **Horizon** hanya berjalan di Linux/macOS (butuh `ext-pcntl`/`ext-posix`).
  Di Windows/Laragon paket ini terpasang untuk kesiapan deploy (Docker/Linux),
  namun perintah `php artisan horizon` tidak dapat dijalankan secara lokal.
  Untuk dependency resolution di Windows, `composer.json` memakai `config.platform`
  untuk `ext-pcntl`/`ext-posix`.
- **Redis client**: memakai `predis/predis` (tanpa ekstensi PHP `redis`).
- Docker dev env & GitHub Actions CI sengaja ditunda ke tahap berikutnya.
