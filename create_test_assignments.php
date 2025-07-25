<?php

require_once 'vendor/autoload.php';

use App\Models\Product;
use App\Models\User;
use App\Models\ProductAssignment;

// Загружаем Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Создание тестовых назначений ===\n\n";

// Получаем продукт
$product = Product::first();
if (!$product) {
    echo "Продукт не найден!\n";
    exit;
}

echo "Продукт: {$product->name} (ID: {$product->id})\n\n";

// Получаем пользователей с ролями
$designers = User::whereHas('roles', function ($q) {
    $q->where('name', 'designer');
})->where('is_active', true)->get();

$printOperators = User::whereHas('roles', function ($q) {
    $q->where('name', 'print_operator');
})->where('is_active', true)->get();

$engravingOperators = User::whereHas('roles', function ($q) {
    $q->where('name', 'engraving_operator');
})->where('is_active', true)->get();

$workshopWorkers = User::whereHas('roles', function ($q) {
    $q->where('name', 'workshop_worker');
})->where('is_active', true)->get();

echo "Найдено пользователей:\n";
echo "- Дизайнеры: {$designers->count()}\n";
echo "- Печатники: {$printOperators->count()}\n";
echo "- Гравировщики: {$engravingOperators->count()}\n";
echo "- Работники цеха: {$workshopWorkers->count()}\n\n";

// Создаем назначения
$assignmentsCreated = 0;

// Назначаем дизайнеров
foreach ($designers->take(2) as $designer) {
    $existing = ProductAssignment::where('product_id', $product->id)
        ->where('user_id', $designer->id)
        ->where('role_type', 'designer')
        ->first();

    if (!$existing) {
        ProductAssignment::create([
            'product_id' => $product->id,
            'user_id' => $designer->id,
            'role_type' => 'designer',
            'is_active' => true
        ]);
        echo "✓ Назначен дизайнер: {$designer->name}\n";
        $assignmentsCreated++;
    } else {
        echo "- Дизайнер уже назначен: {$designer->name}\n";
    }
}

// Назначаем печатников
foreach ($printOperators->take(2) as $operator) {
    $existing = ProductAssignment::where('product_id', $product->id)
        ->where('user_id', $operator->id)
        ->where('role_type', 'print_operator')
        ->first();

    if (!$existing) {
        ProductAssignment::create([
            'product_id' => $product->id,
            'user_id' => $operator->id,
            'role_type' => 'print_operator',
            'is_active' => true
        ]);
        echo "✓ Назначен печатник: {$operator->name}\n";
        $assignmentsCreated++;
    } else {
        echo "- Печатник уже назначен: {$operator->name}\n";
    }
}

// Назначаем гравировщиков
foreach ($engravingOperators->take(1) as $operator) {
    $existing = ProductAssignment::where('product_id', $product->id)
        ->where('user_id', $operator->id)
        ->where('role_type', 'engraving_operator')
        ->first();

    if (!$existing) {
        ProductAssignment::create([
            'product_id' => $product->id,
            'user_id' => $operator->id,
            'role_type' => 'engraving_operator',
            'is_active' => true
        ]);
        echo "✓ Назначен гравировщик: {$operator->name}\n";
        $assignmentsCreated++;
    } else {
        echo "- Гравировщик уже назначен: {$operator->name}\n";
    }
}

// Назначаем работников цеха
foreach ($workshopWorkers->take(1) as $worker) {
    $existing = ProductAssignment::where('product_id', $product->id)
        ->where('user_id', $worker->id)
        ->where('role_type', 'workshop_worker')
        ->first();

    if (!$existing) {
        ProductAssignment::create([
            'product_id' => $product->id,
            'user_id' => $worker->id,
            'role_type' => 'workshop_worker',
            'is_active' => true
        ]);
        echo "✓ Назначен работник цеха: {$worker->name}\n";
        $assignmentsCreated++;
    } else {
        echo "- Работник цеха уже назначен: {$worker->name}\n";
    }
}

echo "\n=== Результат ===\n";
echo "Создано назначений: {$assignmentsCreated}\n";

// Проверяем итоговое количество назначений
$totalAssignments = ProductAssignment::where('product_id', $product->id)->count();
echo "Всего назначений для продукта: {$totalAssignments}\n";

echo "\n=== Тест завершен ===\n";
