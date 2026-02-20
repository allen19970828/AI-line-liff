<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 重置快取
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 建立基礎角色
        Role::updateOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::updateOrCreate(['name' => 'school_admin', 'guard_name' => 'web']);
        Role::updateOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        Role::updateOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $this->command->info('✅ 基礎角色與權限初始化完畢！');
    }
}
