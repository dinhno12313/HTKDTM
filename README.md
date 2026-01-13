# HTKDTM – Hệ thống quản lý chi tiêu cá nhân (Laravel)

Dự án môn **Hệ thống Kinh doanh Thông minh**: xây dựng hệ thống quản lý chi tiêu cá nhân cho sinh viên.
Hệ thống hỗ trợ quản lý ví/tài khoản, ghi nhận giao dịch thu–chi, phân loại danh mục, báo cáo thống kê; đồng thời có phân quyền **Admin/User**.

---

## 1. Công nghệ sử dụng

- Backend: **Laravel 11**, PHP (CLI)
- Auth UI: **Laravel Breeze (Blade)**
- Database (dev): **SQLite** (`database/database.sqlite`)
- Frontend bundler: **Vite** (cần Node.js + npm)

---

## 2. Yêu cầu môi trường

### Bắt buộc
- PHP >= 8.2 (khuyến nghị 8.2+)
- Composer
- Git

### Nếu muốn chạy giao diện login/dashboard đầy đủ
- Node.js + npm (khuyến nghị Node 18+)

### Database
- Mặc định: SQLite (dễ chạy local)
- Có thể chuyển MySQL (tùy chọn)

---

## 3. Cài đặt và chạy project (Local)

### 3.1 Clone repo
```bash
git clone https://github.com/dinhno12313/HTKDTM.git
cd HTKDTM
3.2 Cài dependencies PHP
composer install
3.3 Tạo file môi trường .env
Laravel thường có sẵn .env.example. Tạo .env:
cp .env.example .env
php artisan key:generate
4. Cấu hình Database
4.1 Dùng SQLite (khuyến nghị cho local)
Tạo file SQLite:
touch database/database.sqlite
Mở .env và chỉnh:
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/HTKDTM/database/database.sqlite
Gợi ý: bạn có thể lấy đường dẫn tuyệt đối bằng pwd rồi ghép vào.
4.2 (Tùy chọn) Dùng MySQL
Trong .env:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=htkdtm
DB_USERNAME=root
DB_PASSWORD=
Tạo database htkdtm trước trong MySQL.
Lưu ý: SQLite không hỗ trợ FULLTEXT như MySQL, nên migration đã có đoạn điều kiện để tránh lỗi khi chạy SQLite.
5. Migrate + Seed database (tạo bảng + tạo admin/roles)
Chạy lệnh:
php artisan migrate:fresh --seed
Lệnh này sẽ:
Tạo toàn bộ bảng theo schema (migrations)
Seed dữ liệu RBAC:
Roles: admin, user
Permissions: 6 quyền cơ bản
Tạo sẵn user admin và gán role admin
6. Cài frontend (Breeze UI) và chạy project
6.1 Cài Node.js (nếu máy chưa có)
Mac (Homebrew):
brew install node
node -v
npm -v
6.2 Cài node modules + chạy Vite
npm install
npm run dev
6.3 Chạy server Laravel (terminal khác)
php artisan serve
Mở trình duyệt:
Trang chủ: http://127.0.0.1:8000
Login: http://127.0.0.1:8000/login
Dashboard: http://127.0.0.1:8000/dashboard
Admin: http://127.0.0.1:8000/admin (chỉ Admin truy cập)
7. Tài khoản demo (Admin)
Sau khi seed, dùng tài khoản:
Email: admin@example.com
Password: Admin@12345 (hoặc mật khẩu bạn set trong seeder)
Reset mật khẩu admin (nếu quên)
php artisan tinker
use Illuminate\Support\Facades\Hash;

DB::table('users')
  ->where('email', 'admin@example.com')
  ->update(['password' => Hash::make('Admin@12345')]);

exit;
8. Phân quyền Admin/User (RBAC)
8.1 Các bảng RBAC
roles
permissions
role_has_permissions
model_has_roles
8.2 Seed quyền
Seeder tự tạo:
Role admin có permissions:
manage_users
manage_transactions
view_reports_all
approve_student_verification
manage_plans
manage_system_categories
8.3 Middleware Admin
Route admin được bảo vệ bằng middleware:
auth (phải đăng nhập)
admin (phải có role admin)
Ví dụ:
Route::middleware(['auth', 'admin'])->get('/admin', function () {
    return 'Admin OK';
});
9. Các route quan trọng
/ : welcome
/login, /register, /logout : auth (Breeze)
/dashboard : trang dashboard (Breeze)
/profile : chỉnh sửa profile (Breeze)
/admin : trang admin (role admin)
Kiểm tra danh sách route:
php artisan route:list
10. Cấu trúc thư mục quan trọng
database/migrations/ : các migration tạo schema
database/seeders/ : seed roles/admin
DatabaseSeeder.php
RolesAndPermissionsSeeder.php
AdminUserSeeder.php
app/Http/Middleware/IsAdmin.php : kiểm tra role admin
routes/web.php : định nghĩa route web
routes/auth.php : route auth (Breeze)
11. Lưu ý khi push GitHub
KHÔNG commit .env
KHÔNG commit database local database.sqlite (đã có ignore)
Kiểm tra file sqlite có bị track không:
git ls-files | grep database.sqlite
Nếu có:
git rm --cached database/database.sqlite
git commit -m "Remove local sqlite database"
git push
12. Troubleshooting (các lỗi thường gặp)
12.1 zsh: command not found: laravel
Bạn chưa cài Laravel Installer. Có thể dùng composer:
composer create-project laravel/laravel example-app
12.2 Could not open input file: artisan
Bạn đang đứng sai thư mục. Hãy cd vào thư mục project có file artisan.
12.3 npm: command not found
Bạn chưa cài Node.js:
brew install node
12.4 /dashboard lỗi route profile
Thiếu route profile (Breeze). Đảm bảo routes/web.php có khai báo group profile và có:
require __DIR__.'/auth.php';
12.5 /admin bị 401
Bạn chưa đăng nhập. Hãy login trước, hoặc đảm bảo route admin có auth.
13. Ghi chú phát triển (Roadmap)
Tích hợp tự động ghi nhận giao dịch từ email (email_sources, email_messages)
Báo cáo thống kê chi tiêu theo thời gian/danh mục
Tích hợp thanh toán (PayOS), xác minh sinh viên (giảm giá)
Tích hợp chatbot tư vấn tài chính
14. Thông tin dự án
Môn: Hệ thống Kinh doanh Thông minh
Đề tài: Hệ thống quản lý chi tiêu cá nhân
Nhóm: Nhóm 10_Cụm3 – 64HTTT4
