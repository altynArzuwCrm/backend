<?php

namespace Database\Seeders;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        Project::create([
            'title' => 'Проект визиток',
            'deadline' => Carbon::now()->addDays(7),
            'total_price' => 5000,
            'payment_amount' => 2000,
        ]);

        Project::create([
            'title' => 'Печать буклетов',
            'deadline' => Carbon::now()->addDays(14),
            'total_price' => 15000,
            'payment_amount' => 5000,
        ]);

        Project::create([
            'title' => 'Изготовление баннеров',
            'deadline' => Carbon::now()->addDays(10),
            'total_price' => 25000,
            'payment_amount' => 10000,
        ]);
    }
} 