# 🛠️ ПРАКТИЧЕСКИЕ ПРИМЕРЫ - СИСТЕМА НАЗНАЧЕНИЙ

## 🚀 БЫСТРЫЙ СТАРТ

### Проверим текущее состояние:

```bash
# Посмотрим какие стадии есть
curl GET /api/stages

# Посмотрим какие роли есть
curl GET /api/roles

# Посмотрим связки стадия-роль
curl GET /api/stages/2/roles  # для стадии design
```

---

## 📋 СЦЕНАРИЙ 1: СОЗДАНИЕ ЗАКАЗА С АВТОНАЗНАЧЕНИЕМ

### Шаг 1: Создаем заказ

```javascript
const response = await fetch("/api/orders", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        product_id: 1,
        client_id: 5,
        quantity: 100,
        current_stage: "draft", // Стартовая стадия
        description: "Срочный заказ блокнотов",
    }),
});

const order = await response.json();
console.log("Заказ создан:", order.data.id);
```

### Шаг 2: Проверяем автоназначения

```javascript
const assignments = await fetch(`/api/orders/${order.data.id}/assignments`);
const assignmentData = await assignments.json();

console.log("Автоматически назначены:");
assignmentData.data.forEach((assignment) => {
    console.log(`- ${assignment.user.name} (${assignment.role_type})`);
    console.log(
        `  Стадии: ${assignment.assigned_stages
            .map((s) => s.display_name)
            .join(", ")}`
    );
});
```

**Результат:**

```
Заказ создан: 124
Автоматически назначены:
- Вика (designer)
  Стадии: Черновик
- Ширали (print_operator)
  Стадии: Печать
```

---

## 🔄 СЦЕНАРИЙ 2: ПЕРЕХОД НА СЛЕДУЮЩУЮ СТАДИЮ

### Шаг 1: Переводим заказ на стадию "design"

```javascript
await fetch(`/api/orders/${orderId}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        current_stage: "design",
    }),
});
```

### Шаг 2: Проверяем новые автоназначения

```javascript
const updatedAssignments = await fetch(`/api/orders/${orderId}/assignments`);
const data = await updatedAssignments.json();

console.log('После перехода на "design":');
data.data.forEach((assignment) => {
    console.log(
        `- ${assignment.user.name}: ${assignment.assigned_stages
            .map((s) => s.display_name)
            .join(", ")}`
    );
});
```

**Результат:**

```
После перехода на "design":
- Вика: Дизайн
- Максим: Дизайн (дополнительно назначен)
```

---

## 👥 СЦЕНАРИЙ 3: РУЧНОЕ НАЗНАЧЕНИЕ ДОПОЛНИТЕЛЬНОГО СОТРУДНИКА

### Добавляем второго дизайнера

```javascript
const newAssignment = await fetch("/api/order-assignments", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        order_id: 124,
        user_id: 8, // ID пользователя "Диана"
        role_type: "designer",
        assigned_stages: [2, 3], // design + print (помощь на двух стадиях)
        priority: "high",
    }),
});

console.log("Назначен дополнительный дизайнер:", await newAssignment.json());
```

---

## 🔧 СЦЕНАРИЙ 4: ПЕРЕНАЗНАЧЕНИЕ ПОЛЬЗОВАТЕЛЯ

### Случай: Вика заболела, нужно переназначить на Диану

```javascript
// 1. Находим назначение Вики
const assignments = await fetch(`/api/orders/${orderId}/assignments`);
const data = await assignments.json();
const vikaAssignment = data.data.find((a) => a.user.name === "Вика");

// 2. Переназначаем на Диану
await fetch(`/api/order-assignments/${vikaAssignment.id}`, {
    method: "PUT",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        user_id: 6, // ID Дианы
        assigned_stages: vikaAssignment.assigned_stages.map((s) => s.id),
        note: "Переназначение из-за болезни",
    }),
});

console.log("Заказ переназначен с Вики на Диану");
```

---

## 📊 СЦЕНАРИЙ 5: ПРОВЕРКА ЗАГРУЖЕННОСТИ

### Проверяем кто сколько заказов ведет

```javascript
const workload = await fetch("/api/users/workload");
const workloadData = await workload.json();

console.log("Загруженность сотрудников:");
workloadData.data.forEach((user) => {
    console.log(
        `${user.name} (${user.role}): ${user.active_assignments} заказов (${user.workload_percentage}%)`
    );

    if (user.workload_percentage > 80) {
        console.log(`  ⚠️ ПЕРЕГРУЖЕН! Нужно перераспределить заказы`);
    }
});
```

**Результат:**

```
Загруженность сотрудников:
Вика (designer): 8 заказов (85%)
  ⚠️ ПЕРЕГРУЖЕН! Нужно перераспределить заказы
Диана (designer): 5 заказов (60%)
Максим (designer): 3 заказа (40%)
```

---

## 🎯 СЦЕНАРИЙ 6: МАССОВОЕ ПЕРЕНАЗНАЧЕНИЕ

### Случай: Перераспределяем заказы с перегруженной Вики

```javascript
// 1. Находим активные назначения Вики
const vikaAssignments = await fetch("/api/users/4/assignments?status=active");
const assignments = await vikaAssignments.json();

// 2. Берем последние 3 заказа для переназначения
const toReassign = assignments.data.slice(-3);

// 3. Массово переназначаем на менее загруженных
const reassignments = toReassign.map((assignment, index) => ({
    assignment_id: assignment.id,
    new_user_id: index % 2 === 0 ? 6 : 8, // Чередуем Диану и Максима
    reason: "Разгрузка перегруженного сотрудника",
}));

await fetch("/api/assignments/bulk-reassign", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ reassignments }),
});

console.log(`Переназначено ${reassignments.length} заказов`);
```

---

## ⚙️ СЦЕНАРИЙ 7: НАСТРОЙКА АВТОНАЗНАЧЕНИЯ

### Добавляем новую стадию "quality_check" с автоназначением

```javascript
// 1. Создаем стадию
const stage = await fetch("/api/stages", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        name: "quality_check",
        display_name: "Контроль качества",
        description: "Проверка качества готовой продукции",
        order: 5,
        color: "#10b981",
        is_active: true,
    }),
});

const stageData = await stage.json();

// 2. Настраиваем автоназначение ролей для этой стадии
await fetch(`/api/stages/${stageData.data.id}/roles`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
        roles: [
            {
                role_id: 7, // quality_manager
                auto_assign: true, // Автоназначение
                is_required: true, // Обязательная роль
            },
            {
                role_id: 3, // designer (для проверки)
                auto_assign: false, // Только ручное назначение
                is_required: false,
            },
        ],
    }),
});

console.log("Стадия с автоназначением настроена");
```

---

## 📈 СЦЕНАРИЙ 8: АНАЛИТИКА И ОТЧЕТЫ

### Получаем статистику по эффективности стадий

```javascript
const stageStats = await fetch("/api/stages/statistics?period=month");
const stats = await stageStats.json();

console.log("Статистика по стадиям за месяц:");
stats.data.forEach((stage) => {
    console.log(`\n${stage.display_name}:`);
    console.log(`  Заказов обработано: ${stage.completed_orders}`);
    console.log(`  Среднее время: ${stage.avg_completion_time}`);
    console.log(`  Эффективность: ${stage.efficiency_percentage}%`);

    if (stage.efficiency_percentage < 70) {
        console.log(`  ⚠️ Низкая эффективность! Нужна оптимизация`);
    }
});
```

**Результат:**

```
Статистика по стадиям за месяц:

Дизайн:
  Заказов обработано: 45
  Среднее время: 2.3 дня
  Эффективность: 85%

Печать:
  Заказов обработано: 42
  Среднее время: 1.8 дня
  Эффективность: 92%

Цех:
  Заказов обработано: 38
  Среднее время: 4.1 дня
  Эффективность: 65%
  ⚠️ Низкая эффективность! Нужна оптимизация
```

---

## 🔔 СЦЕНАРИЙ 9: УВЕДОМЛЕНИЯ И СОБЫТИЯ

### Подписываемся на события назначений

```javascript
// WebSocket подключение для real-time уведомлений
const socket = new WebSocket("ws://localhost:6001");

socket.onmessage = function (event) {
    const data = JSON.parse(event.data);

    switch (data.type) {
        case "order.assigned":
            console.log(
                `🎯 Новое назначение: ${data.user.name} назначен на заказ ${data.order.id}`
            );
            updateAssignmentsList();
            break;

        case "order.stage_changed":
            console.log(
                `🔄 Заказ ${data.order.id} перешел в стадию "${data.new_stage}"`
            );
            checkAutoAssignments(data.order.id);
            break;

        case "assignment.overload":
            console.log(
                `⚠️ ВНИМАНИЕ: ${data.user.name} перегружен (${data.assignments_count} заказов)`
            );
            showOverloadWarning(data.user);
            break;
    }
};
```

---

## 🧪 СЦЕНАРИЙ 10: ТЕСТИРОВАНИЕ СИСТЕМЫ

### Полный цикл тестирования

```javascript
async function testAssignmentSystem() {
    console.log("🧪 Тестирование системы назначений...\n");

    // 1. Создаем тестовый заказ
    const order = await createTestOrder();
    console.log(`✅ Заказ ${order.id} создан`);

    // 2. Проверяем автоназначения
    const assignments = await getOrderAssignments(order.id);
    console.log(`✅ Автоназначений: ${assignments.length}`);

    // 3. Переводим на следующую стадию
    await updateOrderStage(order.id, "design");
    console.log(`✅ Заказ переведен в стадию "design"`);

    // 4. Проверяем новые назначения
    const newAssignments = await getOrderAssignments(order.id);
    console.log(`✅ Новых назначений: ${newAssignments.length}`);

    // 5. Делаем ручное назначение
    await createManualAssignment(order.id, 8, "designer", [2]);
    console.log(`✅ Ручное назначение добавлено`);

    // 6. Проверяем финальный результат
    const finalAssignments = await getOrderAssignments(order.id);
    console.log(`✅ Итого назначений: ${finalAssignments.length}`);

    console.log("\n🎉 Тестирование завершено успешно!");
}

// Запускаем тест
testAssignmentSystem().catch(console.error);
```

---

## 🚨 TROUBLESHOOTING

### Частые проблемы и решения:

#### Проблема: Автоназначение не сработало

```javascript
// Проверяем настройки стадии
const stage = await fetch(`/api/stages?name=design`);
const stageData = await stage.json();

const autoAssignRoles = stageData.data[0].roles.filter(
    (r) => r.pivot.auto_assign
);
console.log("Роли с автоназначением:", autoAssignRoles);

if (autoAssignRoles.length === 0) {
    console.log("❌ Нет ролей с автоназначением для этой стадии");
}
```

#### Проблема: Пользователь не назначается

```javascript
// Проверяем доступность пользователя
const user = await fetch(`/api/users/4`);
const userData = await user.json();

console.log("Статус пользователя:", {
    active: userData.data.is_active,
    roles: userData.data.roles.map((r) => r.name),
    current_load: userData.data.assignments_count,
});
```

#### Проблема: Дублирование назначений

```javascript
// Проверяем существующие назначения перед созданием
const existing = await fetch(
    `/api/order-assignments?order_id=${orderId}&user_id=${userId}`
);
const existingData = await existing.json();

if (existingData.data.length > 0) {
    console.log("⚠️ Пользователь уже назначен на этот заказ");
    // Обновляем вместо создания нового
    await updateAssignment(existingData.data[0].id, newStages);
} else {
    // Создаем новое назначение
    await createAssignment(orderId, userId, roleType, stages);
}
```

**Система готова к любым сценариям работы! 🎯**
