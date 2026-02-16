<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StageRoleSeeder extends Seeder
{
    public function run(): void
    {
        $stageRoleMappings = [
            'design' => ['designer'],
            'print' => ['print_operator'],
            'engraving' => ['engraving_operator'],
            'workshop' => ['workshop_worker'],
            'final' => ['workshop_worker'],
            'completed' => ['admin', 'manager'],
            'cancelled' => ['admin', 'manager'],
        ];

        foreach ($stageRoleMappings as $stageName => $roleNames) {
            $stage = DB::table('stages')->where('name', $stageName)->first();
            if ($stage) {
                foreach ($roleNames as $roleName) {
                    $role = DB::table('roles')->where('name', $roleName)->first();
                    if ($role) {
                        DB::table('stage_roles')->updateOrInsert(
                            ['stage_id' => $stage->id, 'role_id' => $role->id],
                            [
                                'is_required' => false,
                                'auto_assign' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            }
        }
    }
}
