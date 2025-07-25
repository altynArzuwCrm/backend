<?php

require_once 'vendor/autoload.php';

use App\Models\Product;
use App\Models\User;
use App\Models\ProductAssignment;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductAssignmentResource;

// Загружаем Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Прямой тест API функциональности ===\n\n";

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

// Тест 1: ProductResource напрямую
echo "=== Тест 1: ProductResource ===\n";

$resource = new ProductResource($product);
$resourceData = $resource->toArray(request());

echo "Структура ProductResource:\n";
if (isset($resourceData['assignments'])) {
    echo "✓ assignments присутствуют\n";

    if (isset($resourceData['assignments']['designers'])) {
        echo "✓ designers: " . count($resourceData['assignments']['designers']) . " шт.\n";
        foreach ($resourceData['assignments']['designers'] as $designer) {
            echo "  - {$designer['user']['name']} (ID: {$designer['user']['id']})\n";
        }
    }

    if (isset($resourceData['assignments']['print_operators'])) {
        echo "✓ print_operators: " . count($resourceData['assignments']['print_operators']) . " шт.\n";
        foreach ($resourceData['assignments']['print_operators'] as $operator) {
            echo "  - {$operator['user']['name']} (ID: {$operator['user']['id']})\n";
        }
    }

    if (isset($resourceData['assignments']['engraving_operators'])) {
        echo "✓ engraving_operators: " . count($resourceData['assignments']['engraving_operators']) . " шт.\n";
        foreach ($resourceData['assignments']['engraving_operators'] as $operator) {
            echo "  - {$operator['user']['name']} (ID: {$operator['user']['id']})\n";
        }
    }

    if (isset($resourceData['assignments']['workshop_workers'])) {
        echo "✓ workshop_workers: " . count($resourceData['assignments']['workshop_workers']) . " шт.\n";
        foreach ($resourceData['assignments']['workshop_workers'] as $worker) {
            echo "  - {$worker['user']['name']} (ID: {$worker['user']['id']})\n";
        }
    }
} else {
    echo "✗ assignments отсутствуют\n";
}

// Тест 2: ProductAssignmentResource
echo "\n=== Тест 2: ProductAssignmentResource ===\n";

$assignments = $product->assignments()->with('user')->get();
echo "Всего назначений: {$assignments->count()}\n";

$assignmentResources = ProductAssignmentResource::collection($assignments);
$assignmentData = $assignmentResources->toArray(request());

echo "Структура ProductAssignmentResource:\n";
foreach ($assignmentData as $assignment) {
    echo "  - {$assignment['user']['name']} ({$assignment['role_type']}) - ID: {$assignment['id']}\n";
}

// Тест 3: Симуляция API ответа
echo "\n=== Тест 3: Симуляция API ответа ===\n";

// Симулируем ответ ProductController::show()
$product->load([
    'designerAssignments.user',
    'printOperatorAssignments.user',
    'engravingOperatorAssignments.user',
    'workshopWorkerAssignments.user'
]);

$apiResponse = new ProductResource($product);
$responseData = $apiResponse->toArray(request());

echo "API Response структура:\n";
echo "- id: {$responseData['id']}\n";
echo "- name: {$responseData['name']}\n";
echo "- has_design_stage: " . ($responseData['has_design_stage'] ? 'true' : 'false') . "\n";
echo "- has_print_stage: " . ($responseData['has_print_stage'] ? 'true' : 'false') . "\n";
echo "- has_engraving_stage: " . ($responseData['has_engraving_stage'] ? 'true' : 'false') . "\n";
echo "- has_workshop_stage: " . ($responseData['has_workshop_stage'] ? 'true' : 'false') . "\n";

if (isset($responseData['assignments'])) {
    echo "- assignments: присутствуют\n";

    $totalAssignments = 0;
    if (isset($responseData['assignments']['designers'])) {
        $totalAssignments += count($responseData['assignments']['designers']);
    }
    if (isset($responseData['assignments']['print_operators'])) {
        $totalAssignments += count($responseData['assignments']['print_operators']);
    }
    if (isset($responseData['assignments']['engraving_operators'])) {
        $totalAssignments += count($responseData['assignments']['engraving_operators']);
    }
    if (isset($responseData['assignments']['workshop_workers'])) {
        $totalAssignments += count($responseData['assignments']['workshop_workers']);
    }

    echo "- total_assignments: {$totalAssignments}\n";
} else {
    echo "- assignments: отсутствуют\n";
}

// Тест 4: Проверка JSON сериализации
echo "\n=== Тест 4: JSON сериализация ===\n";

$jsonResponse = json_encode($responseData, JSON_PRETTY_PRINT);
if ($jsonResponse !== false) {
    echo "✓ JSON сериализация успешна\n";
    echo "Размер JSON: " . strlen($jsonResponse) . " байт\n";

    // Показываем структуру JSON
    $decoded = json_decode($jsonResponse, true);
    if (isset($decoded['assignments'])) {
        echo "✓ assignments в JSON присутствуют\n";

        foreach (['designers', 'print_operators', 'engraving_operators', 'workshop_workers'] as $role) {
            if (isset($decoded['assignments'][$role])) {
                echo "  - {$role}: " . count($decoded['assignments'][$role]) . " шт.\n";
            }
        }
    }
} else {
    echo "✗ Ошибка JSON сериализации\n";
}

echo "\n=== Тест завершен ===\n";
