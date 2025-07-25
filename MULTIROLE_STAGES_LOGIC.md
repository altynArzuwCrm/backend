# Влияние многоролевости на логику стадий

## Стадии заказов:

-   `draft` (черновик)
-   `design` (дизайн)
-   `print` (печать)
-   `engraving` (гравировка)
-   `workshop` (цех)
-   `final` (финал)
-   `completed` (завершен)
-   `cancelled` (отменен)

## Роли:

-   `admin` (администратор)
-   `manager` (менеджер)
-   `designer` (дизайнер)
-   `print_operator` (оператор печати)
-   `workshop_worker` (работник цеха)

## Многоролевость и множественные назначения

-   Один пользователь может иметь несколько ролей.
-   На каждую стадию заказа можно назначить несколько сотрудников через систему назначений (assignments).
-   Все назначения хранятся в таблицах product_assignments и order_assignments.

## Логика назначений на стадии

```php
// Проверка ролей пользователя (поддержка многоролевости)
$assignments = $this->assignments()
    ->whereHas('user.roles', function ($q) use ($role) {
        $q->where('name', $role);
    })
    ->get();
```

## Преимущества многоролевости

-   Один пользователь может работать на разных стадиях
-   Автоматические назначения на стадии по ролям
-   Гибкая логика переходов между стадиями

## Пример автоматического назначения

```php
// При переходе заказа на стадию, например, design:
$roleType = 'designer';
$assignments = $order->assignments()
    ->whereHas('user.roles', function($q) use ($roleType) {
        $q->where('name', $roleType);
    })
    ->get();
// Если нет назначений — создаём их из product_assignments
```

## Пример проверки готовности к переходу

```php
public function isCurrentStageApproved()
{
    $stage = $this->stage;
    $roleMap = [
        'design' => 'designer',
        'print' => 'print_operator',
        'engraving' => 'print_operator',
        'workshop' => 'workshop_worker',
    ];
    if (!isset($roleMap[$stage])) return true;
    $roleType = $roleMap[$stage];
    $assignments = $this->assignments()
        ->whereHas('user.roles', function ($q) use ($roleType) {
            $q->where('name', $roleType);
        })
        ->get();
    return $assignments->isNotEmpty() && $assignments->every(fn($a) => $a->status === 'approved');
}
```

## Рекомендации

-   Используйте только систему множественных назначений для всех стадий и уведомлений
-   Не используйте устаревшие поля типа designer_id, print_operator_id, workshop_worker_id
-   Вся логика уведомлений и переходов стадий должна строиться на order_assignments
