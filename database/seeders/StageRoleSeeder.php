<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Stage;
use App\Models\Role;
use App\Models\StageRole;

class StageRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapping of stages to roles (from previous hardcoded system)
        $stageRoleMappings = [
            'design' => ['designer'],
            'print' => ['print_operator'],
            'engraving' => ['engraving_operator'],
            'workshop' => ['workshop_worker'],
        ];

        foreach ($stageRoleMappings as $stageName => $roleNames) {
            $stage = Stage::where('name', $stageName)->first();

            if ($stage) {
                foreach ($roleNames as $roleName) {
                    $role = Role::where('name', $roleName)->first();

                    if ($role) {
                        // Check if relation already exists
                        $existingRelation = StageRole::where('stage_id', $stage->id)
                            ->where('role_id', $role->id)
                            ->first();

                        if (!$existingRelation) {
                            StageRole::create([
                                'stage_id' => $stage->id,
                                'role_id' => $role->id,
                                'is_required' => false, // Not required by default
                                'auto_assign' => true,  // Auto-assign like before
                            ]);

                            $this->command->info("Created stage-role relation: {$stageName} -> {$roleName}");
                        } else {
                            $this->command->info("Stage-role relation already exists: {$stageName} -> {$roleName}");
                        }
                    } else {
                        $this->command->warn("Role '{$roleName}' not found");
                    }
                }
            } else {
                $this->command->warn("Stage '{$stageName}' not found");
            }
        }

        $this->command->info('StageRole seeding completed!');
    }
}
