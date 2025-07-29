# 🚨 Срочные исправления для завершения миграции

## Основные файлы требующие немедленного исправления:

### 1. app/Models/Product.php

```php
// УДАЛИТЬ эти строки из $fillable:
'has_design_stage',
'has_print_stage',
'has_engraving_stage',
'has_workshop_stage',

// УДАЛИТЬ эти строки из $casts:
'has_design_stage' => 'boolean',
'has_print_stage' => 'boolean',
'has_engraving_stage' => 'boolean',
'has_workshop_stage' => 'boolean',
```

### 2. app/Models/OrderAssignment.php

```php
// УДАЛИТЬ эти строки из $fillable:
'has_design_stage',
'has_print_stage',
'has_engraving_stage',
'has_workshop_stage',
```

### 3. app/Http/Resources/ProductResource.php

```php
// ЗАМЕНИТЬ:
'has_design_stage' => $this->has_design_stage,
'has_print_stage' => $this->has_print_stage,
'has_engraving_stage' => $this->has_engraving_stage,
'has_workshop_stage' => $this->has_workshop_stage,

// НА:
'available_stages' => $this->availableStages->map(function($stage) {
    return [
        'id' => $stage->id,
        'name' => $stage->name,
        'display_name' => $stage->display_name,
        'color' => $stage->color,
        'is_default' => $stage->pivot->is_default ?? false,
    ];
}),
```

### 4. app/Http/Controllers/Api/OrderAssignmentController.php

**Добавить импорты:**

```php
use Illuminate\Support\Facades\Auth;
use App\Models\Stage;
use App\Models\OrderStageAssignment;
```

**Заменить все auth()->user() на Auth::user()**

**Обновить логику множественного назначения (строки 140-170):**

```php
// ВМЕСТО проверки has_*_stage полей
// Использовать новую логику со стадиями:

foreach ($assignments as $assignmentData) {
    $user = User::find($assignmentData['user_id']);
    if (!$user) continue;

    $assignment = OrderAssignment::create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'assigned_by' => Auth::user()->id,
        'role_type' => $assignmentData['role_type'] ?? $user->roles()->first()?->name,
    ]);

    // Назначить на стадии продукта
    if (isset($assignmentData['assigned_stages'])) {
        foreach ($assignmentData['assigned_stages'] as $stageName) {
            $assignment->assignToStage($stageName);
        }
    } else {
        // По умолчанию назначить на все доступные стадии продукта
        $productStages = $product->getAvailableStages();
        foreach ($productStages as $stage) {
            $assignment->assignToStage($stage->name);
        }
    }

    $user->notify(new OrderAssigned($order, Auth::user()));
}
```

### 5. app/Http/Controllers/Api/ProjectController.php

```php
// В валидации ЗАМЕНИТЬ:
'orders.*.has_design_stage' => 'sometimes|boolean',
'orders.*.has_print_stage' => 'sometimes|boolean',
'orders.*.has_workshop_stage' => 'sometimes|boolean',

// НА:
'orders.*.stages' => 'sometimes|array',
'orders.*.stages.*' => 'string|exists:stages,name',

// В создании заказа ЗАМЕНИТЬ:
'has_design_stage' => $orderData['has_design_stage'] ?? false,
'has_print_stage' => $orderData['has_print_stage'] ?? false,
'has_workshop_stage' => $orderData['has_workshop_stage'] ?? false,

// НА логику с ProductStage:
// После создания заказа добавить стадии из orderData['stages']
```

## Команды для запуска:

```bash
# 1. Запустить сидер связей стадий-ролей
php artisan db:seed --class=StageRoleSeeder

# 2. Проверить что миграции применились
php artisan migrate:status

# 3. Проверить новые API endpoints
php artisan route:list --path=api/stages

# 4. Запустить тесты (после исправлений)
php artisan test --filter=Multiple

# 5. Проверить данные
php artisan tinker --execute="echo 'ProductStages: ' . App\Models\ProductStage::count();"
```

## Быстрая проверка работоспособности:

```bash
# Проверить API создания стадии
curl -X POST http://localhost/api/stages \
  -H "Content-Type: application/json" \
  -d '{"name": "test_stage", "display_name": "Тест", "order": 10}'

# Проверить стадии продукта
curl -X GET http://localhost/api/products/1/stages
```

---

**🎯 Приоритет: Исправить ProductResource и убрать has\_\*\_stage поля из моделей - это сломает API ответы!**
