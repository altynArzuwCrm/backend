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
        Schema::create('order_stage_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_assigned')->default(true);
            $table->timestamps();

            $table->unique(['order_assignment_id', 'stage_id']);
        });

        // Migrate existing has_*_stage data from order_assignments
        $this->migrateExistingAssignmentStageData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_stage_assignments');
    }

    private function migrateExistingAssignmentStageData()
    {
        $stageMap = [
            'design' => 'has_design_stage',
            'print' => 'has_print_stage',
            'engraving' => 'has_engraving_stage',
            'workshop' => 'has_workshop_stage'
        ];

        $assignments = DB::table('order_assignments')->get();
        $stages = DB::table('stages')->whereIn('name', array_keys($stageMap))->get()->keyBy('name');

        foreach ($assignments as $assignment) {
            foreach ($stageMap as $stageName => $hasField) {
                $stage = $stages->get($stageName);
                if ($stage && $assignment->$hasField) {
                    DB::table('order_stage_assignments')->insert([
                        'order_assignment_id' => $assignment->id,
                        'stage_id' => $stage->id,
                        'is_assigned' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
};
