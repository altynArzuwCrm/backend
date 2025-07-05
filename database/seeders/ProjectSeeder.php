<?php

namespace Database\Seeders;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            [
                'title' => 'Project #1',
                'client_id' => 1,
                'deadline' => Carbon::now()->addDays(5),
                'total_price' => 1500.50,
                'payment_amount' => 0,
            ],
            [
                'title' => 'Project #2',
                'client_id' => 3,
                'deadline' => Carbon::now()->addDays(10),
                'total_price' => 2500.00,
                'payment_amount' => 1200.00,
            ],
            [
                'title' => 'Project #3',
                'client_id' => 2,
                'deadline' => Carbon::now()->subDays(2),
                'total_price' => 3500.75,
                'payment_amount' => 3500.75,
            ],
        ];

        foreach ($projects as $project) {
            Project::firstOrCreate(
                ['title' => $project['title']],
                $project
            );
        }
    }
} 