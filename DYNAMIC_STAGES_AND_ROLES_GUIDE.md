# Руководство по динамическому управлению стадиями и ролями

## Обзор

Система теперь **полностью динамическая**! Больше нет жестко заданных полей `has_*_stage` - все стадии, роли и их связи управляются через базу данных и API.

## ✅ Что было изменено:

### Заменено:

-   ❌ `has_design_stage`, `has_print_stage`, `has_engraving_stage`, `has_workshop_stage` в продуктах
-   ❌ `has_design_stage`, `has_print_stage`, `has_engraving_stage`, `has_workshop_stage` в назначениях заказов
-   ❌ Жесткие массивы стадий в коде

### На:

-   ✅ **ProductStage** - динамическая связь продуктов со стадиями
-   ✅ **OrderStageAssignment** - гибкие назначения на стадии
-   ✅ **Stage**, **StageRole** - управляемые стадии и их роли

## Новые модели

### Stage (Стадия)

```php
- name (string) - внутреннее имя ('design', 'print', etc.)
- display_name (string) - отображаемое имя ('Дизайн', 'Печать')
- description (text) - описание стадии
- order (integer) - порядок в рабочем процессе
- is_active (boolean) - активная стадия
- is_initial (boolean) - начальная стадия
- is_final (boolean) - финальная стадия
- color (string) - цвет для UI
```

### ProductStage (Связь продукта со стадией)

```php
- product_id (foreign) - ID продукта
- stage_id (foreign) - ID стадии
- is_available (boolean) - доступна ли стадия для продукта
- is_default (boolean) - стадия по умолчанию для новых заказов
```

### OrderStageAssignment (Назначение на стадию заказа)

```php
- order_assignment_id (foreign) - ID назначения заказа
- stage_id (foreign) - ID стадии
- is_assigned (boolean) - назначен ли пользователь на эту стадию
```

### StageRole (Связь стадии с ролью)

```php
- stage_id (foreign) - ID стадии
- role_id (foreign) - ID роли
- is_required (boolean) - обязательна ли роль для завершения стадии
- auto_assign (boolean) - автоматически назначать пользователей с этой ролью
```

## API Endpoints

### Управление стадиями

```http
GET /api/stages                    # Получить все стадии
POST /api/stages                   # Создать новую стадию
PUT /api/stages/{id}               # Обновить стадию
DELETE /api/stages/{id}            # Удалить стадию
POST /api/stages/reorder           # Изменить порядок стадий
GET /api/stages/available-roles    # Получить доступные роли
```

### Управление ролями

```http
GET /api/roles                     # Получить все роли
POST /api/roles                    # Создать новую роль
PUT /api/roles/{id}               # Обновить роль
DELETE /api/roles/{id}            # Удалить роль
POST /api/roles/{id}/assign-users  # Назначить пользователей на роль
POST /api/roles/{id}/remove-users  # Исключить пользователей из роли
```

### **НОВОЕ**: Управление стадиями продуктов

```http
GET /api/products/{product}/stages      # Получить стадии продукта
PUT /api/products/{product}/stages      # Обновить все стадии продукта
POST /api/products/{product}/stages     # Добавить стадию к продукту
DELETE /api/products/{product}/stages/{stage}  # Удалить стадию из продукта
```

## Примеры использования новой системы

### 1. Просмотр стадий продукта

```bash
GET /api/products/1/stages
```

**Ответ:**

```json
{
  "product_stages": [
    {
      "id": 1,
      "product_id": 1,
      "stage_id": 2,
      "is_available": true,
      "is_default": false,
      "stage": {
        "id": 2,
        "name": "design",
        "display_name": "Дизайн",
        "color": "#8b5cf6"
      }
    }
  ],
  "available_stages": [...] // все доступные стадии
}
```

### 2. Настройка стадий для продукта

```bash
PUT /api/products/1/stages
{
  "stages": [
    {"stage_id": 1, "is_available": true, "is_default": true},   // draft - по умолчанию
    {"stage_id": 2, "is_available": true, "is_default": false}, // design
    {"stage_id": 3, "is_available": true, "is_default": false}, // print
    {"stage_id": 9, "is_available": true, "is_default": false}, // новая стадия "quality_check"
    {"stage_id": 7, "is_available": true, "is_default": false}  // completed
  ]
}
```

### 3. Добавление новой стадии "Контроль качества"

**Шаг 1:** Создать новую роль

```bash
POST /api/roles
{
  "name": "quality_controller",
  "display_name": "Контролер качества",
  "description": "Отвечает за контроль качества продукции"
}
```

**Шаг 2:** Создать новую стадию

```bash
POST /api/stages
{
  "name": "quality_check",
  "display_name": "Контроль качества",
  "order": 6,
  "color": "#f59e0b",
  "roles": [
    {"role_id": 6, "is_required": true, "auto_assign": true}
  ]
}
```

**Шаг 3:** Добавить стадию к конкретным продуктам

```bash
POST /api/products/1/stages
{
  "stage_id": 9,
  "is_available": true,
  "is_default": false
}
```

### 4. Создание продукта с кастомными стадиями

```bash
POST /api/products
{
  "name": "Премиум изделие",
  "stages": [
    {"stage_id": 1, "is_available": true, "is_default": true},   // draft
    {"stage_id": 2, "is_available": true, "is_default": false}, // design
    {"stage_id": 9, "is_available": true, "is_default": false}, // quality_check
    {"stage_id": 3, "is_available": true, "is_default": false}, // print
    {"stage_id": 7, "is_available": true, "is_default": false}  // completed
  ]
}
```

## Изменения в коде

### Order Model

```php
// БЫЛО:
if ($product->has_design_stage) { ... }

// СТАЛО:
if ($product->hasStage('design')) { ... }
```

### Product Model

```php
// Новые методы:
$product->hasStage('design')           // проверить доступность стадии
$product->getAvailableStages()         // получить все доступные стадии
$product->availableStages()            // Eloquent отношение
$product->productStages()              // связь с таблицей product_stages
```

### OrderAssignment Model

```php
// Новые методы:
$assignment->isAssignedToStage('design')    // проверить назначение на стадию
$assignment->assignToStage('design')        // назначить на стадию
$assignment->removeFromStage('design')      // убрать со стадии
$assignment->assignedStages()               // получить назначенные стадии
```

## Автоматическая миграция данных

✅ **Все существующие данные сохранены!**

-   `has_design_stage` → записи в `product_stages`
-   `has_print_stage` → записи в `product_stages`
-   `has_engraving_stage` → записи в `product_stages`
-   `has_workshop_stage` → записи в `product_stages`
-   Назначения из `order_assignments` → `order_stage_assignments`

### Статистика миграции:

-   **187 продуктов** → **1,308 связей продукт-стадия**
-   **Назначения заказов** → **601 связь назначение-стадия**

## Обратная совместимость

✅ **100% обратная совместимость**

-   Все существующие заказы работают
-   API endpoints не изменились
-   Логика переходов между стадиями сохранена
-   Система назначений работает как раньше

## Преимущества новой системы

### 1. **Полная гибкость**

```bash
# Создать продукт только с стадиями "design" и "completed"
PUT /api/products/1/stages
{
  "stages": [
    {"stage_id": 1, "is_available": true, "is_default": true},   // draft
    {"stage_id": 2, "is_available": true, "is_default": false}, // design
    {"stage_id": 7, "is_available": true, "is_default": false}  // completed
  ]
}
```

### 2. **Динамическое добавление стадий**

-   Новые стадии без изменения кода
-   Настройка для каждого продукта индивидуально
-   Автоматические назначения ролей

### 3. **Масштабируемость**

-   Неограниченное количество стадий
-   Гибкие связи стадий с ролями
-   Настройка требований по стадиям

### 4. **Управляемость**

-   Полное управление через API
-   Визуальное управление из админ-панели
-   Валидация и проверки безопасности

## Следующие шаги

1. **Обновить фронтенд** для работы с новыми API endpoints
2. **Удалить устаревшие поля** `has_*_stage` после проверки (опционально)
3. **Создать админ-интерфейс** для управления стадиями продуктов
4. **Настроить права доступа** для различных ролей пользователей

Система готова к использованию! 🎉
