<?php

require_once 'vendor/autoload.php';

use App\Models\Product;
use App\Models\User;
use App\Models\ProductAssignment;

// Загружаем Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Тест реальных API эндпоинтов ===\n\n";

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

// Тест 1: GET /api/products/{id}
echo "=== Тест 1: GET /api/products/{$product->id} ===\n";

// Создаем экземпляр контроллера
$controller = new \App\Http\Controllers\Api\ProductController();

// Создаем фейковый запрос
$request = new \Illuminate\Http\Request();
$request->setMethod('GET');

// Вызываем метод show
try {
    $response = $controller->show($product);
    $data = $response->getData(true);

    echo "✓ API ответ получен\n";
    echo "Структура ответа:\n";

    if (isset($data['data']['assignments'])) {
        echo "✓ assignments присутствуют\n";

        if (isset($data['data']['assignments']['designers'])) {
            echo "✓ designers: " . count($data['data']['assignments']['designers']) . " шт.\n";
            foreach ($data['data']['assignments']['designers'] as $designer) {
                echo "  - {$designer['user']['name']} (ID: {$designer['user']['id']})\n";
            }
        }

        if (isset($data['data']['assignments']['print_operators'])) {
            echo "✓ print_operators: " . count($data['data']['assignments']['print_operators']) . " шт.\n";
            foreach ($data['data']['assignments']['print_operators'] as $operator) {
                echo "  - {$operator['user']['name']} (ID: {$operator['user']['id']})\n";
            }
        }

        if (isset($data['data']['assignments']['engraving_operators'])) {
            echo "✓ engraving_operators: " . count($data['data']['assignments']['engraving_operators']) . " шт.\n";
            foreach ($data['data']['assignments']['engraving_operators'] as $operator) {
                echo "  - {$operator['user']['name']} (ID: {$operator['user']['id']})\n";
            }
        }

        if (isset($data['data']['assignments']['workshop_workers'])) {
            echo "✓ workshop_workers: " . count($data['data']['assignments']['workshop_workers']) . " шт.\n";
            foreach ($data['data']['assignments']['workshop_workers'] as $worker) {
                echo "  - {$worker['user']['name']} (ID: {$worker['user']['id']})\n";
            }
        }
    } else {
        echo "✗ assignments отсутствуют\n";
    }
} catch (Exception $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n=== Тест 2: GET /api/products/{$product->id}/assignments ===\n";

// Тестируем эндпоинт назначений
$assignmentController = new \App\Http\Controllers\Api\ProductAssignmentController();

try {
    $response = $assignmentController->index($request, $product);
    $data = $response->getData(true);

    echo "✓ API ответ получен\n";
    echo "product_id: {$data['product_id']}\n";

    if (isset($data['assignments']['data'])) {
        echo "✓ assignments найдены: " . count($data['assignments']['data']) . " шт.\n";
        foreach ($data['assignments']['data'] as $assignment) {
            echo "  - {$assignment['user']['name']} ({$assignment['role_type']})\n";
        }
    } else {
        echo "✗ assignments не найдены\n";
    }
} catch (Exception $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n=== Тест 3: GET /api/products/all ===\n";

// Тестируем эндпоинт всех продуктов
try {
    $response = $controller->allProducts();
    $data = $response->getData(true);

    echo "✓ API ответ получен\n";
    echo "Всего продуктов: " . count($data['data']) . "\n";

    // Проверяем первый продукт на наличие назначений
    if (isset($data['data'][0]['assignments'])) {
        echo "✓ assignments присутствуют в первом продукте\n";

        $firstProduct = $data['data'][0];
        $totalAssignments = 0;

        if (isset($firstProduct['assignments']['designers'])) {
            $totalAssignments += count($firstProduct['assignments']['designers']);
        }
        if (isset($firstProduct['assignments']['print_operators'])) {
            $totalAssignments += count($firstProduct['assignments']['print_operators']);
        }
        if (isset($firstProduct['assignments']['engraving_operators'])) {
            $totalAssignments += count($firstProduct['assignments']['engraving_operators']);
        }
        if (isset($firstProduct['assignments']['workshop_workers'])) {
            $totalAssignments += count($firstProduct['assignments']['workshop_workers']);
        }

        echo "Всего назначений в первом продукте: {$totalAssignments}\n";
    } else {
        echo "✗ assignments отсутствуют в первом продукте\n";
    }
} catch (Exception $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n=== Тест завершен ===\n";
