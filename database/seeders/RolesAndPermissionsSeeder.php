<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // Roles
        $adminRoleId = DB::table('roles')->updateOrInsert(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['updated_at' => $now, 'created_at' => $now]
        );

        $userRoleId = DB::table('roles')->updateOrInsert(
            ['name' => 'user', 'guard_name' => 'web'],
            ['updated_at' => $now, 'created_at' => $now]
        );

        // Lấy id thật (vì updateOrInsert trả bool)
        $adminRole = DB::table('roles')->where(['name' => 'admin','guard_name' => 'web'])->first();
        $userRole  = DB::table('roles')->where(['name' => 'user','guard_name' => 'web'])->first();

        $permissions = [
            'manage_users',
            'manage_transactions',
            'view_reports_all',
            'approve_student_verification',
            'manage_plans',
            'manage_system_categories',
        ];

        $permissionIds = [];
        foreach ($permissions as $p) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $p, 'guard_name' => 'web'],
                ['updated_at' => $now, 'created_at' => $now]
            );
            $permissionIds[] = DB::table('permissions')->where(['name' => $p, 'guard_name' => 'web'])->value('id');
        }

        // Gán permissions cho admin
        foreach ($permissionIds as $pid) {
            DB::table('role_has_permissions')->updateOrInsert(
                ['permission_id' => $pid, 'role_id' => $adminRole->id],
                []
            );
        }

        // user role: thường không cần gán permission (tùy nhóm). Nếu muốn, thêm ở đây.
        // ví dụ: view_own_reports...
    }
}
