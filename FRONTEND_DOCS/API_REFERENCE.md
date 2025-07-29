# 📡 API REFERENCE - ВСЕ НОВЫЕ ENDPOINTS

## 🎯 БАЗОВАЯ ИНФОРМАЦИЯ

**Base URL:** `http://localhost:8000/api`  
**Authentication:** `Bearer Token` (Sanctum)  
**Content-Type:** `application/json`

---

## 📋 STAGES API

### **GET /api/stages**

Получить все стадии

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "name": "draft",
            "display_name": "Черновик",
            "description": "Создание черновика заказа",
            "order": 1,
            "is_active": true,
            "is_initial": true,
            "is_final": false,
            "color": "#6b7280",
            "created_at": "2025-07-28T10:00:00.000000Z",
            "updated_at": "2025-07-28T10:00:00.000000Z"
        }
    ]
}
```

### **POST /api/stages**

Создать новую стадию

**Request:**

```json
{
    "name": "quality_check",
    "display_name": "Контроль качества",
    "description": "Проверка качества продукции",
    "order": 6,
    "color": "#10b981",
    "is_active": true
}
```

**Response:** `201 Created`

### **PUT /api/stages/{id}**

Обновить стадию

**Request:**

```json
{
    "display_name": "Новое название",
    "color": "#ef4444",
    "order": 3
}
```

### **DELETE /api/stages/{id}**

Удалить стадию

**Response:** `204 No Content`

### **POST /api/stages/reorder**

Изменить порядок стадий

**Request:**

```json
{
    "stages": [
        { "id": 1, "order": 1 },
        { "id": 2, "order": 2 },
        { "id": 3, "order": 3 }
    ]
}
```

---

## 👥 ROLES API

### **GET /api/roles**

Получить все роли

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "name": "designer",
            "display_name": "Дизайнер",
            "description": "Создание дизайнов",
            "created_at": "2025-07-28T10:00:00.000000Z",
            "updated_at": "2025-07-28T10:00:00.000000Z"
        }
    ]
}
```

### **POST /api/roles**

Создать новую роль

**Request:**

```json
{
    "name": "quality_manager",
    "display_name": "Менеджер качества",
    "description": "Контроль качества продукции"
}
```

### **PUT /api/roles/{id}**

Обновить роль

**Request:**

```json
{
    "display_name": "Новое название",
    "description": "Новое описание"
}
```

### **POST /api/roles/{id}/assign-users**

Назначить пользователей на роль

**Request:**

```json
{
    "user_ids": [1, 2, 3]
}
```

### **POST /api/roles/{id}/remove-users**

Убрать пользователей с роли

**Request:**

```json
{
    "user_ids": [1, 2]
}
```

---

## 📦 PRODUCTS API (ОБНОВЛЕННЫЕ)

### **GET /api/products**

Получить продукты с новой структурой

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "name": "Смарт Блокнот A5-1 Черный",
            "created_at": "2025-07-28T10:00:00.000000Z",
            "updated_at": "2025-07-28T10:00:00.000000Z",
            "available_stages": [
                {
                    "id": 1,
                    "name": "draft",
                    "display_name": "Черновик",
                    "color": "#6b7280",
                    "order": 1,
                    "is_initial": true,
                    "is_final": false,
                    "pivot": {
                        "is_available": true,
                        "is_default": true
                    }
                }
            ]
        }
    ]
}
```

### **POST /api/products**

Создать продукт (с автоназначением стадий)

**Request:**

```json
{
    "name": "Новый продукт",
    "stages": [
        {
            "stage_id": 1,
            "is_available": true,
            "is_default": true
        },
        {
            "stage_id": 2,
            "is_available": true,
            "is_default": false
        }
    ]
}
```

**Note:** Если `stages` не указаны, автоматически назначаются ВСЕ активные стадии.

---

## 🎯 PRODUCT STAGES API

### **GET /api/products/{id}/stages**

Получить стадии конкретного продукта

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "name": "design",
            "display_name": "Дизайн",
            "color": "#3b82f6",
            "is_available": true,
            "is_default": false
        }
    ]
}
```

### **PUT /api/products/{id}/stages**

Обновить все стадии продукта

**Request:**

```json
{
    "stages": [
        {
            "stage_id": 1,
            "is_available": true,
            "is_default": true
        },
        {
            "stage_id": 2,
            "is_available": true,
            "is_default": false
        }
    ]
}
```

### **POST /api/products/{id}/stages**

Добавить стадию к продукту

**Request:**

```json
{
    "stage_id": 3,
    "is_available": true,
    "is_default": false
}
```

### **DELETE /api/products/{id}/stages/{stage_id}**

Убрать стадию у продукта

**Response:** `204 No Content`

---

## 📋 ORDERS API (ОБНОВЛЕННЫЕ)

### **GET /api/orders**

Получить заказы с новой структурой

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "current_stage": "design",
            "product_id": 1,
            "client_id": 1,
            "status": "active",
            "current_stage_info": {
                "id": 2,
                "name": "design",
                "display_name": "Дизайн",
                "color": "#3b82f6"
            },
            "assignments": [
                {
                    "id": 1,
                    "user_id": 4,
                    "role_type": "designer",
                    "assigned_stages": [
                        {
                            "id": 2,
                            "name": "design",
                            "display_name": "Дизайн"
                        }
                    ]
                }
            ]
        }
    ]
}
```

### **POST /api/orders**

Создать заказ (НОВЫЙ ФОРМАТ)

**Request:**

```json
{
    "product_id": 1,
    "client_id": 1,
    "quantity": 10,
    "current_stage": "draft",
    "description": "Описание заказа"
}
```

**Note:** `current_stage` - ОБЯЗАТЕЛЬНОЕ поле!

### **PUT /api/orders/{id}**

Обновить заказ (включая смену стадии)

**Request:**

```json
{
    "current_stage": "design",
    "quantity": 15
}
```

---

## 🎯 ORDER ASSIGNMENTS API (ОБНОВЛЕННЫЕ)

### **GET /api/assignments**

Получить назначения (с фильтрацией)

**Query Parameters:**

-   `order_id` - ID заказа
-   `user_id` - ID пользователя
-   `status` - статус назначения
-   `role_type` - тип роли

**Response:**

```json
{
    "data": [
        {
            "id": 1,
            "order_id": 1,
            "user_id": 4,
            "role_type": "designer",
            "status": "pending",
            "assigned_stages": [
                {
                    "id": 2,
                    "name": "design",
                    "display_name": "Дизайн"
                }
            ],
            "user": {
                "id": 4,
                "name": "Вика"
            }
        }
    ]
}
```

### **POST /api/order-assignments**

Создать назначение (НОВЫЙ ФОРМАТ)

**Request:**

```json
{
    "order_id": 1,
    "user_id": 4,
    "role_type": "designer",
    "assigned_stages": [2, 3]
}
```

### **PUT /api/assignments/{id}/status**

Обновить статус назначения

**Request:**

```json
{
    "status": "in_progress",
    "assigned_stages": [2, 3, 4]
}
```

---

## 🚀 BULK ASSIGNMENTS API

### **POST /api/orders/{order}/bulk-assign**

Массовое назначение на один заказ

**Request:**

```json
{
    "assignments": [
        {
            "user_id": 4,
            "role_type": "designer",
            "assigned_stages": [2, 3]
        },
        {
            "user_id": 7,
            "role_type": "print_operator",
            "assigned_stages": [3]
        }
    ]
}
```

**Response:**

```json
{
  "message": "Массовое назначение завершено",
  "created_assignments": [...],
  "total_created": 2,
  "errors": []
}
```

### **POST /api/assignments/bulk-assign**

Массовое назначение на разные заказы

**Request:**

```json
{
    "assignments": [
        {
            "order_id": 123,
            "user_id": 4,
            "role_type": "designer",
            "assigned_stages": [2]
        },
        {
            "order_id": 124,
            "user_id": 4,
            "role_type": "designer",
            "assigned_stages": [2]
        }
    ]
}
```

### **POST /api/assignments/bulk-reassign**

Массовое переназначение

**Request:**

```json
{
    "reassignments": [
        {
            "assignment_id": 456,
            "new_user_id": 8,
            "reason": "Отпуск основного сотрудника"
        }
    ]
}
```

### **POST /api/assignments/bulk-update**

Массовое обновление стадий

**Request:**

```json
{
    "updates": [
        {
            "assignment_id": 456,
            "assigned_stages": [2, 3, 4]
        }
    ]
}
```

---

## 🔐 ПРАВА ДОСТУПА

### **Массовые операции:**

-   ✅ **Admin, Manager** - полный доступ
-   ❌ **Остальные роли** - 403 Forbidden

### **Единичные операции:**

-   ✅ **Admin, Manager** - полный доступ
-   🔒 **Остальные** - только свои назначения

---

## ⚠️ КОДЫ ОШИБОК

### **400 Bad Request**

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "current_stage": ["The current_stage field is required."]
    }
}
```

### **403 Forbidden**

```json
{
    "message": "Доступ запрещён"
}
```

### **404 Not Found**

```json
{
    "message": "No query results for model [App\\Models\\Stage] 999"
}
```

### **422 Unprocessable Entity**

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "assigned_stages.0": ["The selected assigned_stages.0 is invalid."]
    }
}
```

---

## 🧪 ПРИМЕРЫ ТЕСТИРОВАНИЯ

### **Проверка стадий:**

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/stages
```

### **Создание продукта:**

```bash
curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"name": "Test Product"}' \
     http://localhost:8000/api/products
```

### **Создание заказа:**

```bash
curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"product_id": 1, "client_id": 1, "current_stage": "draft"}' \
     http://localhost:8000/api/orders
```

**API полностью готово к использованию! 🚀**
