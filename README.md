# HTKDTM – Hệ thống quản lý chi tiêu cá nhân (Laravel)

Dự án môn **Hệ thống Kinh doanh Thông minh**: xây dựng hệ thống quản lý chi tiêu cá nhân cho sinh viên.  
Hỗ trợ quản lý ví/tài khoản, ghi nhận giao dịch thu–chi, phân loại danh mục, báo cáo thống kê và phân quyền **Admin/User**.

---

## Mục lục

- [1. Công nghệ sử dụng](#1-công-nghệ-sử-dụng)
- [2. Yêu cầu môi trường](#2-yêu-cầu-môi-trường)
- [3. Cài đặt và chạy project (Local)](#3-cài-đặt-và-chạy-project-local)
- [4. Cấu hình Database](#4-cấu-hình-database)
- [5. Migrate + Seed database](#5-migrate--seed-database)
- [6. Cài frontend (Breeze UI) và chạy dự án](#6-cài-frontend-breeze-ui-và-chạy-dự-án)
- [7. Tài khoản demo Admin](#7-tài-khoản-demo-admin)
- [8. RBAC: Phân quyền Admin/User](#8-rbac-phân-quyền-adminuser)
- [9. Routes quan trọng](#9-routes-quan-trọng)
- [10. Cấu trúc thư mục](#10-cấu-trúc-thư-mục)
- [11. Lưu ý khi push GitHub](#11-lưu-ý-khi-push-github)
- [12. Troubleshooting](#12-troubleshooting)
- [13. Roadmap](#13-roadmap)
- [14. Thông tin dự án](#14-thông-tin-dự-án)

---

## 1. Công nghệ sử dụng

- Backend: **Laravel 11**, PHP (CLI)
- Auth UI: **Laravel Breeze (Blade)**
- Database (dev): **SQLite** (`database/database.sqlite`)
- Frontend bundler: **Vite** (cần Node.js + npm để build assets)

---

## 2. Yêu cầu môi trường

### Bắt buộc

- PHP >= 8.2 (khuyến nghị 8.2+)
- Composer
- Git

### Để chạy giao diện login/dashboard đầy đủ

- Node.js + npm (khuyến nghị Node 18+)

### Database

- Mặc định: SQLite (dễ chạy local)
- Tuỳ chọn: MySQL

---

## 3. Cài đặt và chạy project (Local)

### 3.1 Clone repo

```bash
git clone https://github.com/dinhno12313/HTKDTM.git
cd HTKDTM
```

### 3.2 Cài dependencies PHP

```bash
composer install
```

### 3.3 Tạo file môi trường `.env`

```bash
cp .env.example .env
php artisan key:generate
```

---

## 4. Cấu hình Database

### 4.1 Dùng SQLite (khuyến nghị)

1) Tạo file SQLite:

```bash
touch database/database.sqlite
```

2) Mở `.env` và chỉnh:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/HTKDTM/database/database.sqlite
```

> Gợi ý: lấy đường dẫn tuyệt đối bằng `pwd` rồi thay vào `DB_DATABASE`.

---

### 4.2 (Tuỳ chọn) Dùng MySQL

Chỉnh `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=htkdtm
DB_USERNAME=root
DB_PASSWORD=
```

Tạo database `htkdtm` trước trong MySQL.

> Lưu ý: SQLite không hỗ trợ FULLTEXT như MySQL, migration đã có điều kiện để tránh lỗi khi chạy SQLite.

---

## 5. Migrate + Seed database

Chạy:

```bash
php artisan migrate:fresh --seed
```

Lệnh này sẽ:

- Tạo toàn bộ bảng theo schema (migrations)
- Seed dữ liệu RBAC:
  - Roles: `admin`, `user`
  - Permissions: 6 quyền cơ bản
- Tạo user admin mẫu và gán role `admin`

---

## 6. Cài frontend (Breeze UI) và chạy dự án

### 6.1 Cài Node.js (nếu máy chưa có)

Mac (Homebrew):

```bash
brew install node
node -v
npm -v
```

### 6.2 Cài node modules + chạy Vite

```bash
npm install
npm run dev
```

### 6.3 Chạy server Laravel (terminal khác)

```bash
php artisan serve
```

Các đường dẫn thường dùng:

- Trang chủ: `http://127.0.0.1:8000`
- Login: `http://127.0.0.1:8000/login`
- Dashboard: `http://127.0.0.1:8000/dashboard`
- Admin: `http://127.0.0.1:8000/admin` (chỉ Admin truy cập)

---

## 7. Tài khoản demo Admin

Sau khi seed:

- Email: `admin@example.com`
- Password: `Admin@12345` *(hoặc mật khẩu bạn set trong seeder)*

### Reset mật khẩu admin (nếu quên)

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Hash;

DB::table('users')
  ->where('email', 'admin@example.com')
  ->update(['password' => Hash::make('Admin@12345')]);

exit;
```

---

## 8. RBAC: Phân quyền Admin/User

### 8.1 Các bảng RBAC

- `roles`
- `permissions`
- `role_has_permissions`
- `model_has_roles`

### 8.2 Seed quyền

Seeder tự tạo role `admin` và gán các permission:

- `manage_users`
- `manage_transactions`
- `view_reports_all`
- `approve_student_verification`
- `manage_plans`
- `manage_system_categories`

### 8.3 Middleware Admin

Route admin được bảo vệ bởi middleware:

- `auth` (phải đăng nhập)
- `admin` (phải có role admin)

Ví dụ:

```php
Route::middleware(['auth', 'admin'])->get('/admin', function () {
    return 'Admin OK';
});
```

---

## 9. Routes quan trọng

- `/` : welcome
- `/login`, `/register`, `/logout` : auth (Breeze)
- `/dashboard` : dashboard (Breeze)
- `/profile` : profile (Breeze)
- `/admin` : trang admin (role admin)

Kiểm tra danh sách route:

```bash
php artisan route:list
```

---

## 10. Cấu trúc thư mục

- `database/migrations/` : migrations tạo schema
- `database/seeders/` : seed roles/admin
  - `DatabaseSeeder.php`
  - `RolesAndPermissionsSeeder.php`
  - `AdminUserSeeder.php`
- `app/Http/Middleware/IsAdmin.php` : kiểm tra role admin
- `routes/web.php` : routes web
- `routes/auth.php` : routes auth (Breeze)

---

## 11. Lưu ý khi push GitHub

- KHÔNG commit `.env`
- KHÔNG commit database local `database.sqlite` (đã ignore)

Kiểm tra file sqlite có bị track không:

```bash
git ls-files | grep database.sqlite
```

Nếu có:

```bash
git rm --cached database/database.sqlite
git commit -m "Remove local sqlite database"
git push
```

---

## 12. Troubleshooting

### 12.1 `Could not open input file: artisan`

Bạn đang đứng sai thư mục. Hãy `cd` vào thư mục project có file `artisan`.

### 12.2 `npm: command not found`

Bạn chưa cài Node.js:

```bash
brew install node
```

### 12.3 `/dashboard` lỗi `Route [profile.edit] not defined`

Đảm bảo `routes/web.php` có route profile và có dòng:

```php
require __DIR__.'/auth.php';
```

### 12.4 `/admin` trả về 401/403

- 401: chưa login
- 403: login rồi nhưng không có role `admin`

---

## 13. Roadmap

- Tích hợp tự động ghi nhận giao dịch từ email (`email_sources`, `email_messages`)
- Báo cáo thống kê chi tiêu theo thời gian/danh mục
- Tích hợp thanh toán (PayOS), xác minh sinh viên (giảm giá)
- Tích hợp chatbot tư vấn tài chính

---

## 14. Thông tin dự án

- Môn: Hệ thống Kinh doanh Thông minh
- Đề tài: Hệ thống quản lý chi tiêu cá nhân
- Nhóm: Nhóm 10_Cụm3 – 64HTTT4
