# ✅ Чек-лист миграции от has\_\*\_stage к динамической системе

## Статус: 🟡 В процессе

### ✅ Завершено:

-   [x] Создание новых моделей: `Stage`, `StageRole`, `ProductStage`, `OrderStageAssignment`
-   [x] Миграция данных: 187 продуктов → 1,308 связей стадий
-   [x] Миграция назначений: → 601 связей стадий заказов
-   [x] Создание API контроллеров и роутов
-   [x] Обновление модели Order для использования динамических стадий
-   [x] Создание сидера StageRoleSeeder

### 🟡 Нужно исправить:

#### 1. Контроллеры с has\_\*\_stage логикой:

**app/Http/Controllers/Api/OrderAssignmentController.php**

```php
// ЗАМЕНИТЬ:
'has_design_stage' => 'sometimes|boolean',
'has_print_stage' => 'sometimes|boolean',
// НА:
'assigned_stages' => 'sometimes|array',
'assigned_stages.*' => 'string|exists:stages,name',

// ЗАМЕНИТЬ логику создания назначений:
'has_design_stage' => $request->has('has_design_stage') ? ...
// НА:
foreach ($data['assigned_stages'] as $stageName) {
    $assignment->assignToStage($stageName);
}
```

**app/Http/Controllers/Api/ProjectController.php**

```php
// ЗАМЕНИТЬ валидацию:
'orders.*.has_design_stage' => 'sometimes|boolean',
// НА:
'orders.*.stages' => 'sometimes|array',
'orders.*.stages.*' => 'string|exists:stages,name',
```

#### 2. Модели с has\_\*\_stage полями:

**app/Models/Product.php**

```php
// УДАЛИТЬ из fillable и casts:
'has_design_stage',
'has_print_stage',
'has_engraving_stage',
'has_workshop_stage',

// Поля уже заменены новыми методами:
// hasStage(), getAvailableStages(), availableStages()
```

**app/Models/OrderAssignment.php**

```php
// УДАЛИТЬ из fillable:
'has_design_stage',
'has_print_stage',
'has_engraving_stage',
'has_workshop_stage',

// Поля уже заменены новыми методами:
// isAssignedToStage(), assignToStage(), assignedStages()
```

#### 3. Ресурсы API:

**app/Http/Resources/ProductResource.php**

```php
// ЗАМЕНИТЬ:
'has_design_stage' => $this->has_design_stage,
'has_print_stage' => $this->has_print_stage,
// НА:
'available_stages' => $this->availableStages,
'product_stages' => $this->productStages,
```

#### 4. Тестовые файлы:

**tests/Feature/\***

-   Обновить все тесты для работы с новыми полями
-   Заменить has\_\*\_stage на assigned_stages в тестах
-   Обновить ассерты для проверки новой структуры

#### 5. Утилиты и скрипты:

**test_api_direct.php**

```php
// ЗАМЕНИТЬ:
echo "- has_design_stage: " . ($responseData['has_design_stage'] ? 'true' : 'false') . "\n";
// НА:
echo "- available_stages: " . json_encode($responseData['available_stages']) . "\n";
```

**MULTIPLE_ASSIGNMENTS_GUIDE.md**

-   Обновить примеры API запросов
-   Заменить has\_\*\_stage на новую структуру в документации

### 🔧 Быстрые исправления:

#### Импорты в контроллерах:

```php
// Добавить в OrderAssignmentController:
use Illuminate\Support\Facades\Auth;
use App\Models\Stage;
use App\Models\OrderStageAssignment;

// Заменить auth()->user() на Auth::user()
```

#### Валидация в контроллерах:

```php
// Стандартная замена во всех контроллерах:
'has_design_stage' => 'boolean',          → удалить
'has_print_stage' => 'boolean',           → удалить
'has_engraving_stage' => 'boolean',       → удалить
'has_workshop_stage' => 'boolean',        → удалить

'stages' => 'sometimes|array',                    → добавить
'stages.*.stage_id' => 'required|exists:stages,id',  → добавить
'stages.*.is_available' => 'boolean',             → добавить
```

### 📋 План доработки:

1. **Исправить импорты в OrderAssignmentController**
2. **Обновить валидацию во всех контроллерах**
3. **Убрать has\_\*\_stage поля из моделей**
4. **Обновить ProductResource для новой структуры**
5. **Исправить тестовые файлы**
6. **Обновить документацию**

### 🎯 Критические места для проверки:

-   [ ] Создание заказов через ProjectController
-   [ ] Назначения пользователей через OrderAssignmentController
-   [ ] API ответы через ProductResource
-   [ ] Все тестовые сценарии
-   [ ] Фронтенд интеграция (проверить поломки)

### 🚀 После завершения:

1. Запустить все тесты: `php artisan test`
2. Проверить API endpoints с новой структурой
3. Убрать deprecated поля из БД (опционально)
4. Обновить фронтенд для работы с новыми полями

---

**Статус:** Основная логика мигрирована ✅, остались косметические исправления 🔧
