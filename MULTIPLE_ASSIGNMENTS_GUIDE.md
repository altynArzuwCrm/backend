# Руководство по множественным назначениям

## Возможности системы

Теперь вы можете назначать **несколько пользователей на одну роль** для продукта. Например, у продукта может быть 4 дизайнера по умолчанию, и они будут автоматически назначаться на заказы.

## Как это работает

### 1. Назначение пользователей на продукт

#### Добавление одного назначения:

```bash
POST /api/products/{product_id}/assignments
{
    "user_id": 1,
    "role_type": "designer",
    "priority": 1,
    "is_active": true
}
```

#### Массовое добавление назначений:

```bash
POST /api/products/{product_id}/assignments/bulk
{
    "assignments": [
        {
            "user_id": 1,
            "role_type": "designer",
            "priority": 1
        },
        {
            "user_id": 2,
            "role_type": "designer",
            "priority": 2
        },
        {
            "user_id": 3,
            "role_type": "designer",
            "priority": 3
        },
        {
            "user_id": 4,
            "role_type": "designer",
            "priority": 4
        }
    ]
}
```

### 2. Приоритеты назначений

-   **Priority 1** - высший приоритет (назначается первым)
-   **Priority 2** - второй приоритет
-   **Priority 3** - третий приоритет
-   И так далее...

### 3. Автоматическое назначение на заказы

Когда заказ переходит на стадию (например, `design`), система автоматически:

1. Проверяет назначенных пользователей для продукта
2. Создает назначения для всех активных пользователей с соответствующей ролью
3. Отправляет уведомления всем назначенным пользователям

## API Endpoints

### Получение назначений продукта

```bash
GET /api/products/{product_id}/assignments
```

**Ответ:**

```json
{
    "product_id": 1,
    "assignments": {
        "designer": [
            {
                "id": 1,
                "user_id": 1,
                "role_type": "designer",
                "priority": 1,
                "is_active": true,
                "user": {
                    "id": 1,
                    "name": "Дизайнер 1",
                    "role": "designer"
                }
            },
            {
                "id": 2,
                "user_id": 2,
                "role_type": "designer",
                "priority": 2,
                "is_active": true,
                "user": {
                    "id": 2,
                    "name": "Дизайнер 2",
                    "role": "designer"
                }
            }
        ],
        "print_operator": [
            {
                "id": 3,
                "user_id": 3,
                "role_type": "print_operator",
                "priority": 1,
                "is_active": true,
                "user": {
                    "id": 3,
                    "name": "Оператор печати",
                    "role": "print_operator"
                }
            }
        ]
    }
}
```

### Получение доступных пользователей

```bash
GET /api/products/{product_id}/assignments/available-users?role_type=designer
```

### Обновление назначения

```bash
PUT /api/products/{product_id}/assignments/{assignment_id}
{
    "priority": 2,
    "is_active": false
}
```

### Удаление назначения

```bash
DELETE /api/products/{product_id}/assignments/{assignment_id}
```

## Примеры использования

### Пример 1: Назначение 4 дизайнеров на продукт

```php
// Создаем продукт
$product = Product::create([
    'name' => 'Смарт Блокнот A5-1 Черный',
    'has_design_stage' => true,
    'has_print_stage' => true,
    'has_workshop_stage' => true,
]);

// Назначаем 4 дизайнера
$designers = User::where('role', 'designer')->take(4)->get();

foreach ($designers as $index => $designer) {
    $product->assignments()->create([
        'user_id' => $designer->id,
        'role_type' => 'designer',
        'priority' => $index + 1,
        'is_active' => true
    ]);
}
```

### Пример 2: Создание заказа с автоматическим назначением

```php
// Создаем заказ
$order = Order::create([
    'client_id' => 1,
    'product_id' => $product->id,
    'stage' => 'draft'
]);

// Переводим на стадию дизайна
$order->update(['stage' => 'design']);

// Автоматически создаются назначения для всех 4 дизайнеров
$assignments = $order->assignments()->with('user')->get();
// Результат: 4 назначения для дизайнеров
```

### Пример 3: Управление приоритетами

```php
// Получаем назначения дизайнеров
$designerAssignments = $product->designerAssignments()
    ->where('is_active', true)
    ->orderBy('priority')
    ->get();

// Первый дизайнер (priority 1) будет назначен первым
$firstDesigner = $designerAssignments->first()->user;

// Если первый дизайнер занят, назначается второй
if ($firstDesigner->isBusy()) {
    $secondDesigner = $designerAssignments->skip(1)->first()->user;
}
```

## Логика переходов между стадиями

### Текущая логика:

-   Заказ переходит на следующую стадию, когда **все** назначения текущей стадии имеют статус `approved`

### Новая логика с множественными назначениями:

-   Заказ переходит на следующую стадию, когда **хотя бы одно** назначение текущей стадии имеет статус `approved`
-   Или можно настроить, чтобы требовалось одобрение от определенного количества пользователей

## Настройка логики переходов

Вы можете настроить логику переходов в методе `isCurrentStageApproved()` модели `Order`:

```php
public function isCurrentStageApproved()
{
    $stage = $this->stage;
    $product = $this->product;

    $roleMap = [
        'design' => 'designer',
        'print' => 'print_operator',
        'engraving' => 'print_operator',
        'workshop' => 'workshop_worker',
    ];

    if (!isset($roleMap[$stage])) {
        return true;
    }

    $roleType = $roleMap[$stage];

    // Получаем все назначения для текущей стадии
    $assignments = $this->assignments()
        ->whereHas('user.roles', function ($q) use ($roleType) {
            $q->where('name', $roleType);
        })
        ->get();

    // Вариант 1: Требуется одобрение от всех назначенных
    return $assignments->isNotEmpty() && $assignments->every(fn($a) => $a->status === 'approved');

    // Вариант 2: Требуется одобрение хотя бы от одного
    // return $assignments->isNotEmpty() && $assignments->contains('status', 'approved');

    // Вариант 3: Требуется одобрение от большинства
    // $approvedCount = $assignments->where('status', 'approved')->count();
    // return $assignments->isNotEmpty() && $approvedCount > ($assignments->count() / 2);
}
```

## Преимущества системы

1. **Гибкость:** Можно назначить любое количество пользователей на роль
2. **Приоритизация:** Система приоритетов позволяет контролировать порядок назначений
3. **Автоматизация:** Автоматическое назначение при переходе на стадию
4. **Масштабируемость:** Легко добавлять и удалять назначения
5. **Контроль:** Можно деактивировать назначения без удаления

## Рекомендации по использованию

1. **Приоритеты:** Используйте приоритеты для обозначения основного и резервных сотрудников
2. **Активность:** Деактивируйте назначения вместо удаления для сохранения истории
3. **Балансировка:** Распределяйте нагрузку между несколькими сотрудниками
4. **Мониторинг:** Отслеживайте эффективность работы с множественными назначениями
