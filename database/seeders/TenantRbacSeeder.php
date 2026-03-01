<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class TenantRbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $staff = Role::firstOrCreate(['name' => 'staff']);

        $featurePermissions = [
            'user' => ['create', 'read', 'update', 'delete'],
            'role' => ['create', 'read', 'update', 'delete'],
            'module' => ['read', 'request', 'install', 'uninstall'],
        ];

        foreach ($featurePermissions as $featureName => $permissions) {
            $feature = Feature::firstOrCreate(['name' => $featureName]);

            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'feature_id' => $feature->id,
                ]);
            }
        }

        $allPermissionIds = Permission::pluck('id')->all();
        $admin->permissions()->syncWithoutDetaching($allPermissionIds);

        $readPermissionIds = Permission::where('name', 'read')->pluck('id')->all();
        $staff->permissions()->syncWithoutDetaching($readPermissionIds);
    }
}
