# 🔐 СИСТЕМА ПРАВ ДОСТУПА - НАЗНАЧЕНИЯ

## 📋 ТЕКУЩИЕ ПРАВА ДОСТУПА

### ✅ **КТО МОЖЕТ ДЕЛАТЬ МАССОВЫЕ НАЗНАЧЕНИЯ:**

| Роль                | Назначения | Переназначения | Обновления | Просмотр |
| ------------------- | ---------- | -------------- | ---------- | -------- |
| **Admin**           | ✅ Все     | ✅ Все         | ✅ Все     | ✅ Все   |
| **Manager**         | ✅ Все     | ✅ Все         | ✅ Все     | ✅ Все   |
| **Designer**        | ❌ Нет     | ❌ Нет         | ❌ Нет     | ✅ Свои  |
| **Print Operator**  | ❌ Нет     | ❌ Нет         | ❌ Нет     | ✅ Свои  |
| **Workshop Worker** | ❌ Нет     | ❌ Нет         | ❌ Нет     | ✅ Свои  |

---

## 🚫 ОГРАНИЧЕНИЯ ДОСТУПА

### **Массовые операции (только Admin + Manager):**

```javascript
// ❌ ЗАПРЕЩЕНО для обычных пользователей:
POST / api / orders / { order } / bulk - assign; // 403 Forbidden
POST / api / assignments / bulk - assign; // 403 Forbidden
POST / api / assignments / bulk - reassign; // 403 Forbidden
POST / api / assignments / bulk - update; // 403 Forbidden
```

### **Единичные операции:**

```javascript
// ✅ РАЗРЕШЕНО Admin + Manager:
POST / api / orders / { order } / assign; // Назначить на заказ
PUT / api / assignments / { id } / status; // Изменить статус
DELETE / api / assignments / { id }; // Удалить назначение

// 🔒 ОГРАНИЧЕНО для обычных пользователей:
GET / api / assignments; // Только свои назначения
GET / api / assignments / { id }; // Только если назначен
PUT / api / assignments / { id } / status; // Только свои (НЕ на "approved")
```

---

## 🔍 ПРОВЕРКА ПРАВ В КОДЕ

### **OrderAssignmentPolicy.php:**

```php
public function before($user, $ability)
{
    // Admin и Manager имеют ВСЕ права
    if ($user->hasAnyRole(['admin', 'manager'])) {
        return true;
    }
}

public function assign(User $user)
{
    // Только Admin и Manager могут назначать
    return $user->hasAnyRole(['admin', 'manager']);
}

public function viewAny(User $user)
{
    if ($user->hasAnyRole(['admin', 'manager'])) {
        return true; // Видят ВСЕ назначения
    }

    return $user->assignedOrders()->exists(); // Только СВОИ
}

public function updateStatus(User $user, OrderAssignment $assignment)
{
    if ($user->hasAnyRole(['admin', 'manager'])) {
        return true; // Могут менять ЛЮБОЙ статус
    }

    // Обычный пользователь может менять только СВОИ назначения
    if ($user->id === $assignment->user_id) {
        return true; // Но НЕ на "approved" (проверяется в контроллере)
    }

    return false;
}
```

---

## 🧪 ТЕСТИРОВАНИЕ ПРАВ ДОСТУПА

### **Проверка текущего пользователя:**

```javascript
// GET /api/me
{
  "id": 1,
  "name": "Aylana",
  "roles": [
    {
      "id": 1,
      "name": "admin",
      "display_name": "Администратор"
    }
  ]
}
```

### **Тест прав на массовые операции:**

```bash
# Как Admin/Manager (успех):
curl -X POST /api/assignments/bulk-assign \
  -H "Authorization: Bearer {admin_token}" \
  -d '{"assignments": [...]}'
# → 201 Created

# Как обычный пользователь (ошибка):
curl -X POST /api/assignments/bulk-assign \
  -H "Authorization: Bearer {user_token}" \
  -d '{"assignments": [...]}'
# → 403 Forbidden: "Доступ запрещён"
```

---

## ⚙️ НАСТРОЙКА ПРАВ (при необходимости)

### **Если нужно дать права другим ролям:**

1. **Обновить политику:**

```php
// app/Policies/OrderAssignmentPolicy.php
public function assign(User $user)
{
    // Добавить новую роль, например 'supervisor'
    return $user->hasAnyRole(['admin', 'manager', 'supervisor']);
}
```

2. **Создать новую роль:**

```php
// В сидере или через API
Role::create([
    'name' => 'supervisor',
    'display_name' => 'Супервайзер',
    'description' => 'Может управлять назначениями'
]);
```

3. **Назначить роль пользователю:**

```javascript
// POST /api/roles/{role_id}/assign-users
{
  "user_ids": [5, 6, 7]
}
```

---

## 🔐 БЕЗОПАСНОСТЬ

### **Принципы:**

1. **Минимальные права** - каждый видит только что нужно
2. **Разделение обязанностей** - массовые операции только у руководителей
3. **Аудит действий** - все изменения логируются
4. **Проверка на каждом уровне** - политики + контроллеры

### **Логирование:**

```php
// Все массовые операции автоматически логируются:
Log::info('Массовое переназначение', [
    'assignment_id' => $assignment->id,
    'old_user_id' => $oldUser->id,
    'new_user_id' => $newUser->id,
    'reason' => $reason,
    'reassigned_by' => Auth::user()->id
]);
```

---

## 📊 ТЕКУЩИЕ ПОЛЬЗОВАТЕЛИ И РОЛИ

```javascript
// GET /api/users - список пользователей с ролями
{
  "data": [
    {
      "id": 1,
      "name": "Aylana",
      "roles": ["admin"],
      "can_assign": true,
      "can_bulk_assign": true
    },
    {
      "id": 2,
      "name": "Test",
      "roles": ["manager"],
      "can_assign": true,
      "can_bulk_assign": true
    },
    {
      "id": 4,
      "name": "Вика",
      "roles": ["designer"],
      "can_assign": false,
      "can_bulk_assign": false
    }
  ]
}
```

---

## ✅ ИТОГОВЫЕ ПРАВИЛА

### **МАССОВЫЕ ОПЕРАЦИИ = ТОЛЬКО ADMIN + MANAGER**

| Операция        | Admin | Manager | Designer | Print Op | Workshop |
| --------------- | ----- | ------- | -------- | -------- | -------- |
| `bulk-assign`   | ✅    | ✅      | ❌       | ❌       | ❌       |
| `bulk-reassign` | ✅    | ✅      | ❌       | ❌       | ❌       |
| `bulk-update`   | ✅    | ✅      | ❌       | ❌       | ❌       |

### **ЕДИНИЧНЫЕ ОПЕРАЦИИ = ОГРАНИЧЕННЫЕ**

| Операция        | Admin    | Manager  | Остальные                      |
| --------------- | -------- | -------- | ------------------------------ |
| `assign`        | ✅ Все   | ✅ Все   | ❌ Нет                         |
| `view`          | ✅ Все   | ✅ Все   | ✅ Только свои                 |
| `update-status` | ✅ Любой | ✅ Любой | 🔒 Только свои (НЕ "approved") |

**Система безопасности настроена правильно! 🔒**
