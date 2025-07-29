<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stage_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false); // required for stage completion
            $table->boolean('auto_assign')->default(true); // auto-assign users with this role
            $table->timestamps();

            $table->unique(['stage_id', 'role_id']);
        });

        // Set up default stage-role mappings that match current system
        $stageRoleMappings = [
            'design' => ['designer'],
            'print' => ['print_operator'],
            'engraving' => ['engraving_operator'],
            'workshop' => ['workshop_worker'],
        ];

        foreach ($stageRoleMappings as $stageName => $roleNames) {
            $stage = DB::table('stages')->where('name', $stageName)->first();
            if ($stage) {
                foreach ($roleNames as $roleName) {
                    $role = DB::table('roles')->where('name', $roleName)->first();
                    if ($role) {
                        DB::table('stage_roles')->insert([
                            'stage_id' => $stage->id,
                            'role_id' => $role->id,
                            'is_required' => false,
                            'auto_assign' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_roles');
    }
};
