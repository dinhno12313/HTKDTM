<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $adminEmail = 'admin@example.com';
        $adminPassword = 'Admin@12345'; // đổi trước khi deploy

        // tạo admin user nếu chưa có
        $adminId = DB::table('users')->where('email', $adminEmail)->value('id');

        if (!$adminId) {
            $adminId = DB::table('users')->insertGetId([
                'name' => 'System Admin',
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'status' => 'active',
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $adminRoleId = DB::table('roles')->where(['name' => 'admin','guard_name' => 'web'])->value('id');

        // gán role admin (model_has_roles)
        DB::table('model_has_roles')->updateOrInsert(
            [
                'role_id' => $adminRoleId,
                'model_type' => 'App\\Models\\User',
                'model_id' => $adminId,
            ],
            []
        );
    }
}
