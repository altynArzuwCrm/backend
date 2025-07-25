<?php

require_once 'vendor/autoload.php';

use App\Models\Product;
use App\Models\User;
use App\Models\ProductAssignment;

// Загружаем Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Тест API с назначениями ===\n\n";

// Получаем продукт с назначениями
$product = Product::with([
    'designerAssignments.user',
    'printOperatorAssignments.user',
    'engravingOperatorAssignments.user',
    'workshopWorkerAssignments.user'
])->first();

if (!$product) {
    echo "Продукт не найден!\n";
    exit;
}

echo "Продукт: {$product->name} (ID: {$product->id})\n\n";

// Проверяем назначения
echo "=== Назначения ===\n";

// Дизайнеры
if ($product->designerAssignments->count() > 0) {
    echo "Дизайнеры:\n";
    foreach ($product->designerAssignments as $assignment) {
        echo "  - {$assignment->user->name} (ID: {$assignment->user->id})\n";
    }
} else {
    echo "Дизайнеры: не назначены\n";
}

// Печатники
if ($product->printOperatorAssignments->count() > 0) {
    echo "Печатники:\n";
    foreach ($product->printOperatorAssignments as $assignment) {
        echo "  - {$assignment->user->name} (ID: {$assignment->user->id})\n";
    }
} else {
    echo "Печатники: не назначены\n";
}

// Гравировщики
if ($product->engravingOperatorAssignments->count() > 0) {
    echo "Гравировщики:\n";
    foreach ($product->engravingOperatorAssignments as $assignment) {
        echo "  - {$assignment->user->name} (ID: {$assignment->user->id})\n";
    }
} else {
    echo "Гравировщики: не назначены\n";
}

// Работники цеха
if ($product->workshopWorkerAssignments->count() > 0) {
    echo "Работники цеха:\n";
    foreach ($product->workshopWorkerAssignments as $assignment) {
        echo "  - {$assignment->user->name} (ID: {$assignment->user->id})\n";
    }
} else {
    echo "Работники цеха: не назначены\n";
}

echo "\n=== Тест API эндпоинтов ===\n";

// Симулируем API запрос для получения продукта
echo "GET /api/products/{$product->id}:\n";
$productData = $product->load([
    'designerAssignments.user',
    'printOperatorAssignments.user',
    'engravingOperatorAssignments.user',
    'workshopWorkerAssignments.user'
]);

// Проверяем структуру данных
if (isset($productData->designerAssignments)) {
    echo "✓ designerAssignments загружены\n";
    foreach ($productData->designerAssignments as $assignment) {
        echo "  - {$assignment->user->name} (роль: {$assignment->role_type})\n";
    }
} else {
    echo "✗ designerAssignments не загружены\n";
}

if (isset($productData->printOperatorAssignments)) {
    echo "✓ printOperatorAssignments загружены\n";
    foreach ($productData->printOperatorAssignments as $assignment) {
        echo "  - {$assignment->user->name} (роль: {$assignment->role_type})\n";
    }
} else {
    echo "✗ printOperatorAssignments не загружены\n";
}

if (isset($productData->engravingOperatorAssignments)) {
    echo "✓ engravingOperatorAssignments загружены\n";
    foreach ($productData->engravingOperatorAssignments as $assignment) {
        echo "  - {$assignment->user->name} (роль: {$assignment->role_type})\n";
    }
} else {
    echo "✗ engravingOperatorAssignments не загружены\n";
}

if (isset($productData->workshopWorkerAssignments)) {
    echo "✓ workshopWorkerAssignments загружены\n";
    foreach ($productData->workshopWorkerAssignments as $assignment) {
        echo "  - {$assignment->user->name} (роль: {$assignment->role_type})\n";
    }
} else {
    echo "✗ workshopWorkerAssignments не загружены\n";
}

echo "\n=== Тест API Resource ===\n";

// Создаем экземпляр ProductResource
$resource = new \App\Http\Resources\ProductResource($productData);
$resourceData = $resource->toArray(request());

echo "Структура API Resource:\n";
if (isset($resourceData['assignments'])) {
    echo "✓ assignments присутствуют\n";

    if (isset($resourceData['assignments']['designers'])) {
        echo "✓ designers в assignments\n";
        foreach ($resourceData['assignments']['designers'] as $designer) {
            echo "  - {$designer['user']['name']} (ID: {$designer['user']['id']})\n";
        }
    }

    if (isset($resourceData['assignments']['print_operators'])) {
        echo "✓ print_operators в assignments\n";
        foreach ($resourceData['assignments']['print_operators'] as $operator) {
            echo "  - {$operator['user']['name']} (ID: {$operator['user']['id']})\n";
        }
    }

    if (isset($resourceData['assignments']['engraving_operators'])) {
        echo "✓ engraving_operators в assignments\n";
        foreach ($resourceData['assignments']['engraving_operators'] as $operator) {
            echo "  - {$operator['user']['name']} (ID: {$operator['user']['id']})\n";
        }
    }

    if (isset($resourceData['assignments']['workshop_workers'])) {
        echo "✓ workshop_workers в assignments\n";
        foreach ($resourceData['assignments']['workshop_workers'] as $worker) {
            echo "  - {$worker['user']['name']} (ID: {$worker['user']['id']})\n";
        }
    }
} else {
    echo "✗ assignments отсутствуют\n";
}

echo "\n=== Тест назначений продукта ===\n";

// Тестируем эндпоинт назначений
$assignments = $product->assignments()->with('user')->get();
echo "GET /api/products/{$product->id}/assignments:\n";

if ($assignments->count() > 0) {
    echo "✓ Назначения найдены ({$assignments->count()} шт.)\n";
    foreach ($assignments as $assignment) {
        echo "  - {$assignment->user->name} ({$assignment->role_type})\n";
    }
} else {
    echo "✗ Назначения не найдены\n";
}

echo "\n=== Тест завершен ===\n";
