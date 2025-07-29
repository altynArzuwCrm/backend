# 🎯 ПОЛНОЕ РУКОВОДСТВО - СИСТЕМА НАЗНАЧЕНИЙ

## 📋 ОБЗОР НОВОЙ СИСТЕМЫ

Система назначений теперь **полностью динамическая**:

-   ✅ **Стадии** управляются через БД
-   ✅ **Роли** управляются через БД
-   ✅ **Назначения** привязаны к конкретным стадиям
-   ✅ **Автоподстановка** работает по правилам в БД

---

## 🏗️ АРХИТЕКТУРА СИСТЕМЫ

### 1. **Основные сущности:**

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Stages    │────│ StageRoles  │────│   Roles     │
│   (стадии)  │    │(связка)     │    │  (роли)     │
└─────────────┘    └─────────────┘    └─────────────┘
       │                    │                   │
       │            ┌─────────────┐             │
       └────────────│   Orders    │─────────────┘
                    │  (заказы)   │
                    └─────────────┘
                           │
                    ┌─────────────┐
                    │OrderAssign- │
                    │ments        │
                    │(назначения) │
                    └─────────────┘
```

### 2. **Как это работает:**

1. **Стадия** определяет этап работы (`design`, `print`, `workshop`)
2. **Роль** определяет тип работника (`designer`, `print_operator`)
3. **StageRole** связывает: какие роли нужны для какой стадии
4. **OrderAssignment** назначает конкретного пользователя на заказ + стадии

---

## 🔄 ЖИЗНЕННЫЙ ЦИКЛ ЗАКАЗА

### Этап 1: **Создание заказа**

```javascript
// POST /api/orders
{
  "product_id": 1,
  "client_id": 1,
  "quantity": 10,
  "current_stage": "draft"  // Стартовая стадия
}
```

**Что происходит:**

1. ✅ Заказ создается в стадии `draft`
2. ✅ Система ищет роли для стадии `draft` в `stage_roles`
3. ✅ **АВТОПОДСТАНОВКА**: назначает пользователей с нужными ролями

### Этап 2: **Автоподстановка пользователей**

```sql
-- Логика автоподстановки
SELECT users.* FROM users
JOIN user_roles ON users.id = user_roles.user_id
JOIN stage_roles ON user_roles.role_id = stage_roles.role_id
WHERE stage_roles.stage_id = 1 -- draft
AND stage_roles.auto_assign = true
```

**Результат:** Автоматически создаются `OrderAssignment` записи

### Этап 3: **Переход на следующую стадию**

```javascript
// PUT /api/orders/123
{
  "current_stage": "design"  // Переход на дизайн
}
```

**Что происходит:**

1. ✅ Заказ переходит в стадию `design`
2. ✅ Система проверяет: все ли обязательные роли назначены
3. ✅ **АВТОПОДСТАНОВКА**: добавляет недостающих пользователей

---

## 🎯 ТИПЫ НАЗНАЧЕНИЙ

### 1. **Автоматические назначения**

```javascript
// В stage_roles таблице:
{
  "stage_id": 2,      // design
  "role_id": 3,       // designer
  "auto_assign": true, // ✅ Автоназначение
  "is_required": true  // Обязательная роль
}
```

**Когда работает:**

-   При создании заказа
-   При смене стадии заказа
-   При добавлении новой роли в стадию

### 2. **Ручные назначения**

```javascript
// POST /api/order-assignments
{
  "order_id": 123,
  "user_id": 5,
  "role_type": "designer",
  "assigned_stages": [2, 3]  // design, print
}
```

**Возможности:**

-   Назначить конкретного пользователя
-   Указать конкретные стадии
-   Переназначить с одного на другого

### 3. **Групповые назначения**

```javascript
// PUT /api/orders/123/assignments
{
  "assignments": [
    {
      "user_id": 4,
      "role_type": "designer",
      "assigned_stages": [2]
    },
    {
      "user_id": 7,
      "role_type": "print_operator",
      "assigned_stages": [3]
    }
  ]
}
```

---

## 🤖 АВТОПОДСТАНОВКА - ДЕТАЛЬНО

### Правила автоподстановки:

```php
// В OrderController при смене стадии:
public function updateStage($orderId, $newStage)
{
    $order = Order::find($orderId);
    $order->current_stage = $newStage;
    $order->save();

    // 🤖 АВТОПОДСТАНОВКА
    $this->autoAssignUsersForStage($order, $newStage);
}

private function autoAssignUsersForStage($order, $stageName)
{
    $stage = Stage::where('name', $stageName)->first();

    // Найти роли с автоназначением для этой стадии
    $autoAssignRoles = $stage->roles()
        ->where('stage_roles.auto_assign', true)
        ->get();

    foreach ($autoAssignRoles as $role) {
        // Найти доступных пользователей с этой ролью
        $availableUsers = $role->users()
            ->where('is_active', true)
            ->get();

        if ($availableUsers->isNotEmpty()) {
            // Назначить первого доступного
            $user = $availableUsers->first();

            OrderAssignment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'role_type' => $role->name,
            ]);

            // Привязать к стадии
            $assignment = OrderAssignment::latest()->first();
            $assignment->assignToStage($stage->id);
        }
    }
}
```

### Приоритеты автоподстановки:

1. **Загруженность** - менее загруженный пользователь
2. **Специализация** - пользователь со специализацией на продукте
3. **Последние назначения** - равномерное распределение

---

## 📝 ИНДИВИДУАЛЬНЫЕ ИЗМЕНЕНИЯ НАЗНАЧЕНИЙ

### 1. **Изменить пользователя на заказе**

```javascript
// PUT /api/order-assignments/456
{
  "user_id": 8,  // Новый пользователь
  "assigned_stages": [2, 3]  // Те же стадии
}
```

### 2. **Изменить стадии у назначения**

```javascript
// PUT /api/order-assignments/456
{
  "assigned_stages": [3, 4, 5]  // Новые стадии: print, workshop, completed
}
```

### 3. **Добавить дополнительного пользователя**

```javascript
// POST /api/order-assignments
{
  "order_id": 123,
  "user_id": 9,           // Дополнительный дизайнер
  "role_type": "designer",
  "assigned_stages": [2]   // Только design
}
```

### 4. **Убрать пользователя со стадии**

```javascript
// DELETE /api/order-assignments/456/stages/2
// Убирает пользователя со стадии "design"
```

---

## 🔧 УПРАВЛЕНИЕ СТАДИЯМИ И РОЛЯМИ

### 1. **Настройка автоназначения для стадии**

```javascript
// PUT /api/stages/2
{
  "roles": [
    {
      "role_id": 3,        // designer
      "auto_assign": true,  // ✅ Автоназначение
      "is_required": true   // Обязательная роль
    },
    {
      "role_id": 4,        // manager
      "auto_assign": false, // Только ручное назначение
      "is_required": false
    }
  ]
}
```

### 2. **Создание новой стадии с ролями**

```javascript
// POST /api/stages
{
  "name": "quality_check",
  "display_name": "Контроль качества",
  "order": 6,
  "color": "#10b981",
  "roles": [
    {
      "role_id": 7,        // quality_manager
      "auto_assign": true,
      "is_required": true
    }
  ]
}
```

---

## 📊 СТАТИСТИКА И ОТЧЕТЫ

### 1. **Загруженность пользователей**

```javascript
// GET /api/users/workload
{
  "data": [
    {
      "user_id": 4,
      "name": "Вика",
      "role": "designer",
      "active_assignments": 5,
      "stages": ["design", "review"],
      "workload_percentage": 80
    }
  ]
}
```

### 2. **Статистика по стадиям**

```javascript
// GET /api/stages/statistics
{
  "data": [
    {
      "stage_name": "design",
      "total_orders": 15,
      "completed_orders": 10,
      "avg_completion_time": "2.5 days",
      "assigned_users": 3
    }
  ]
}
```

---

## 🎮 ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ

### Сценарий 1: **Создание срочного заказа**

```javascript
// 1. Создаем заказ
const order = await fetch("/api/orders", {
    method: "POST",
    body: JSON.stringify({
        product_id: 1,
        client_id: 5,
        current_stage: "draft",
        priority: "urgent", // Высокий приоритет
    }),
});

// 2. Автоподстановка сработала автоматически
// 3. Дополнительно назначаем второго дизайнера
await fetch("/api/order-assignments", {
    method: "POST",
    body: JSON.stringify({
        order_id: order.id,
        user_id: 10, // Второй дизайнер
        role_type: "designer",
        assigned_stages: [2], // design
    }),
});
```

### Сценарий 2: **Переназначение при отпуске**

```javascript
// 1. Находим все назначения пользователя
const assignments = await fetch(`/api/users/4/assignments?active=true`);

// 2. Переназначаем на другого пользователя
for (const assignment of assignments.data) {
    await fetch(`/api/order-assignments/${assignment.id}`, {
        method: "PUT",
        body: JSON.stringify({
            user_id: 11, // Замещающий пользователь
            assigned_stages: assignment.assigned_stages,
        }),
    });
}
```

### Сценарий 3: **Массовое переназначение стадии**

```javascript
// Все заказы в стадии "design" переназначить на нового дизайнера
const designOrders = await fetch("/api/orders?current_stage=design");

const bulkReassign = designOrders.data.map((order) => ({
    order_id: order.id,
    old_user_id: 4, // Старый дизайнер
    new_user_id: 12, // Новый дизайнер
    role_type: "designer",
}));

await fetch("/api/assignments/bulk-reassign", {
    method: "POST",
    body: JSON.stringify({ reassignments: bulkReassign }),
});
```

---

## ⚡ ОПТИМИЗАЦИЯ И ПРОИЗВОДИТЕЛЬНОСТЬ

### 1. **Умная автоподстановка**

```php
// Алгоритм выбора оптимального пользователя:
private function getBestUserForAssignment($role, $stage, $order)
{
    return User::whereHas('roles', function($query) use ($role) {
        $query->where('name', $role);
    })
    ->where('is_active', true)
    ->withCount(['activeAssignments'])  // Загруженность
    ->orderBy('active_assignments_count', 'asc')  // Менее загруженный первым
    ->first();
}
```

### 2. **Кэширование назначений**

```php
// Кэшируем часто используемые данные
$userWorkload = Cache::remember("user_workload_{$userId}", 300, function() use ($userId) {
    return OrderAssignment::where('user_id', $userId)
        ->where('status', 'active')
        ->count();
});
```

---

## 🚨 КРИТИЧНЫЕ МОМЕНТЫ

### 1. **Обязательные проверки**

-   ✅ Все `is_required` роли должны быть назначены
-   ✅ Пользователь должен быть активным (`is_active = true`)
-   ✅ Роль должна существовать в системе
-   ✅ Стадия должна быть доступна для продукта

### 2. **Безопасность**

-   ✅ Проверка прав доступа через Policy
-   ✅ Валидация входящих данных
-   ✅ Логирование всех изменений назначений

### 3. **Уведомления**

```php
// При назначении пользователя - отправляем уведомление
event(new OrderAssigned($order, $user, $stages));

// При смене стадии - уведомляем всех назначенных
event(new OrderStageChanged($order, $oldStage, $newStage));
```

---

## 🎯 ГЛАВНЫЕ ПРЕИМУЩЕСТВА НОВОЙ СИСТЕМЫ

### ✅ **Гибкость**

-   Любые стадии можно добавить/убрать
-   Любые роли можно создать
-   Настраиваемые правила автоназначения

### ✅ **Автоматизация**

-   Автоподстановка пользователей
-   Умное распределение нагрузки
-   Автоматические уведомления

### ✅ **Контроль**

-   Полная история изменений
-   Статистика по загруженности
-   Гибкие отчеты

### ✅ **Масштабируемость**

-   Поддержка любого количества стадий
-   Неограниченное количество ролей
-   Эффективная работа с большими объемами

**Система готова к любым изменениям бизнес-процессов! 🚀**
