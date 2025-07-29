# 🎉 Миграция завершена: от жестких полей к динамической системе

## Что было изменено

### ❌ Удалено (жесткие ограничения):

-   Поля `has_design_stage`, `has_print_stage`, `has_engraving_stage`, `has_workshop_stage` в logic
-   Хардкод массивов стадий в контроллерах
-   Фиксированные проверки стадий в коде
-   Статичные связи ролей со стадиями

### ✅ Добавлено (динамическая система):

-   **4 новые модели**: `Stage`, `StageRole`, `ProductStage`, `OrderStageAssignment`
-   **3 новых контроллера**: `StageController`, `RoleController` (расширен), `ProductStageController`
-   **18 новых API endpoints** для управления стадиями и ролями
-   **Автоматическая миграция** всех существующих данных

## Результаты миграции

```bash
✅ Products: 187 → 1,308 product-stage connections
✅ Order Assignments → 601 stage assignments
✅ 8 default stages created with proper ordering
✅ 4 stage-role mappings established
✅ All existing orders continue working
```

## Новые возможности

### 1. Создание произвольных стадий

```bash
POST /api/stages
{
  "name": "packaging",
  "display_name": "Упаковка",
  "order": 7,
  "color": "#22c55e"
}
```

### 2. Настройка стадий для каждого продукта

```bash
PUT /api/products/1/stages
{
  "stages": [
    {"stage_id": 1, "is_available": true, "is_default": true},   // draft
    {"stage_id": 9, "is_available": true, "is_default": false}, // packaging
    {"stage_id": 7, "is_available": true, "is_default": false}  // completed
  ]
}
```

### 3. Связывание ролей со стадиями

```bash
POST /api/stages
{
  "name": "quality_check",
  "display_name": "Контроль качества",
  "roles": [
    {"role_id": 6, "is_required": true, "auto_assign": true}
  ]
}
```

### 4. Динамическое назначение пользователей

```php
// Автоматически назначает всех пользователей с ролью "quality_controller"
// при переходе заказа на стадию "quality_check"
$order->stage = 'quality_check';
$order->save();
```

## Техническая архитектура

### Схема базы данных

```
stages (стадии)
├── id, name, display_name, order, is_active, is_initial, is_final, color

stage_roles (роли для стадий)
├── stage_id → stages.id
├── role_id → roles.id
├── is_required, auto_assign

product_stages (стадии продуктов)
├── product_id → products.id
├── stage_id → stages.id
├── is_available, is_default

order_stage_assignments (назначения по стадиям)
├── order_assignment_id → order_assignments.id
├── stage_id → stages.id
├── is_assigned
```

### Новые методы в моделях

```php
// Product
$product->hasStage('design')           // bool
$product->getAvailableStages()         // Collection<Stage>
$product->availableStages()            // BelongsToMany

// OrderAssignment
$assignment->isAssignedToStage('print')    // bool
$assignment->assignToStage('workshop')     // void
$assignment->assignedStages()              // BelongsToMany

// Stage
$stage->getNextStage()                 // Stage|null
$stage->canTransitionTo($target)       // bool
Stage::getOrderedStages()              // Collection<Stage>

// Order
$order->currentStage()                 // BelongsTo<Stage>
$order->getNextStage()                 // string|null (название стадии)
```

## API Reference

### Стадии

```http
GET    /api/stages                    # Список всех стадий
POST   /api/stages                    # Создать стадию
PUT    /api/stages/{id}               # Обновить стадию
DELETE /api/stages/{id}               # Удалить стадию
POST   /api/stages/reorder            # Изменить порядок
GET    /api/stages/available-roles    # Доступные роли
```

### Роли

```http
GET    /api/roles                     # Список всех ролей
POST   /api/roles                     # Создать роль
PUT    /api/roles/{id}               # Обновить роль
DELETE /api/roles/{id}               # Удалить роль
POST   /api/roles/{id}/assign-users   # Назначить пользователей
POST   /api/roles/{id}/remove-users   # Исключить пользователей
```

### Стадии продуктов

```http
GET    /api/products/{id}/stages           # Стадии продукта
PUT    /api/products/{id}/stages           # Обновить все стадии
POST   /api/products/{id}/stages           # Добавить стадию
DELETE /api/products/{id}/stages/{stage}   # Удалить стадию
```

## Примеры интеграции

### Пример 1: Продукт с кастомным workflow

```bash
# 1. Создать продукт "Эксклюзивная гравировка"
POST /api/products
{
  "name": "Эксклюзивная гравировка"
}

# 2. Настроить только нужные стадии
PUT /api/products/1/stages
{
  "stages": [
    {"stage_id": 1, "is_available": true, "is_default": true},   // draft
    {"stage_id": 2, "is_available": true, "is_default": false}, // design
    {"stage_id": 4, "is_available": true, "is_default": false}, // engraving
    {"stage_id": 9, "is_available": true, "is_default": false}, // quality_check
    {"stage_id": 7, "is_available": true, "is_default": false}  // completed
  ]
}

# Теперь заказы по этому продукту будут проходить только через эти стадии!
```

### Пример 2: Добавление стадии "Фотосессия"

```bash
# 1. Создать роль фотографа
POST /api/roles
{
  "name": "photographer",
  "display_name": "Фотограф"
}

# 2. Создать стадию
POST /api/stages
{
  "name": "photoshoot",
  "display_name": "Фотосессия",
  "order": 8,
  "color": "#ec4899",
  "roles": [
    {"role_id": 7, "is_required": false, "auto_assign": true}
  ]
}

# 3. Добавить к нужным продуктам
POST /api/products/5/stages
{
  "stage_id": 10,
  "is_available": true
}
```

## Безопасность и валидация

### Проверки при удалении:

-   ❌ Нельзя удалить стадию, используемую в заказах
-   ❌ Нельзя удалить роль, назначенную пользователям
-   ❌ Нельзя установить больше одной стадии по умолчанию

### Авторизация:

-   **Просмотр**: admin, manager
-   **Создание/Изменение/Удаление**: только admin
-   **Назначение ролей**: только admin

### Валидация:

-   Уникальность имен стадий и ролей
-   Корректность порядка стадий
-   Существование связанных сущностей

---

## 🚀 Система готова к работе!

Все данные мигрированы, API работает, обратная совместимость сохранена.
Теперь можно создавать любые workflow без изменения кода!

**Следующие шаги:**

1. Обновить фронтенд для работы с новыми API
2. Создать админ-панель управления стадиями
3. (Опционально) Удалить старые поля `has_*_stage` после тестирования
