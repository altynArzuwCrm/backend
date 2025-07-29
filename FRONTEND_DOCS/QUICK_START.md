# ⚡ БЫСТРЫЙ СТАРТ - 15 МИНУТ

## 🚀 САМОЕ ВАЖНОЕ ЗА 15 МИНУТ

### 📋 **ШАГ 1: Что изменилось (2 мин)**

Система стадий теперь **динамическая**:

-   ❌ `has_design_stage`, `has_print_stage` → **УДАЛЕНЫ**
-   ✅ `available_stages`, `assigned_stages` → **НОВЫЕ**
-   ✅ `current_stage` → **НОВОЕ** в заказах

### 📋 **ШАГ 2: Тестируем новые API (5 мин)**

```bash
# Получить все стадии
curl GET http://localhost:8000/api/stages

# Получить продукт с новой структурой
curl GET http://localhost:8000/api/products/1

# Создать заказ (НОВЫЙ ФОРМАТ)
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{"product_id": 1, "client_id": 1, "current_stage": "draft"}'
```

### 📋 **ШАГ 3: Обновляем фронтенд (8 мин)**

#### **3.1. Удаляем старые поля (2 мин):**

```javascript
// Найти и удалить ВСЕ упоминания:
has_design_stage
has_print_stage
has_workshop_stage
has_engraving_stage
designer_id (в продуктах)
print_operator_id (в продуктах)
workshop_worker_id (в продуктах)
```

#### **3.2. Заменяем фильтры (3 мин):**

```javascript
// ❌ СТАРОЕ:
products.filter((p) => p.has_design_stage);

// ✅ НОВОЕ:
products.filter((p) => p.available_stages.some((s) => s.name === "design"));
```

#### **3.3. Обновляем формы (3 мин):**

```javascript
// ❌ СТАРОЕ создание заказа:
{ product_id: 1, has_design_stage: true }

// ✅ НОВОЕ создание заказа:
{ product_id: 1, current_stage: 'draft' }
```

---

## 🎯 КРИТИЧЕСКИЕ ТОЧКИ

### **1. Product API изменения:**

```javascript
// Ответ теперь содержит:
{
  "id": 1,
  "name": "Продукт",
  "available_stages": [    // ← НОВОЕ!
    {
      "id": 1,
      "name": "draft",
      "display_name": "Черновик",
      "color": "#6b7280",
      "pivot": {
        "is_available": true,
        "is_default": true   // ← Стартовая стадия
      }
    }
  ]
}
```

### **2. Order API изменения:**

```javascript
// Обязательно указывать current_stage:
POST /api/orders {
  "product_id": 1,
  "client_id": 1,
  "current_stage": "draft"  // ← ОБЯЗАТЕЛЬНО!
}
```

### **3. Assignment API изменения:**

```javascript
// Назначения теперь привязаны к стадиям:
POST /api/order-assignments {
  "order_id": 1,
  "user_id": 4,
  "role_type": "designer",
  "assigned_stages": [1, 2]  // ← ID стадий
}
```

---

## 🔧 HELPER ФУНКЦИИ

### **Для проверки стадий:**

```javascript
// utils/stages.js
export function hasStage(product, stageName) {
    return product.available_stages?.some((s) => s.name === stageName) || false;
}

export function getStageColor(stages, stageName) {
    const stage = stages.find((s) => s.name === stageName);
    return stage?.color || "#gray";
}

export function getDefaultStage(product) {
    return product.available_stages?.find((s) => s.pivot?.is_default);
}
```

### **Для API запросов:**

```javascript
// utils/api.js
export async function getStages() {
    const response = await fetch("/api/stages");
    return response.json();
}

export async function createOrder(orderData) {
    const response = await fetch("/api/orders", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            ...orderData,
            current_stage: orderData.current_stage || "draft",
        }),
    });
    return response.json();
}
```

---

## 🚨 ЧАСТЫЕ ОШИБКИ

### **Ошибка 1: Забыли указать current_stage**

```javascript
// ❌ Ошибка 422:
POST /api/orders { "product_id": 1 }

// ✅ Правильно:
POST /api/orders { "product_id": 1, "current_stage": "draft" }
```

### **Ошибка 2: Используете старые поля**

```javascript
// ❌ Ошибка - поле не существует:
if (product.has_design_stage) { ... }

// ✅ Правильно:
if (hasStage(product, 'design')) { ... }
```

### **Ошибка 3: Неправильный формат assigned_stages**

```javascript
// ❌ Строки не работают:
"assigned_stages": ["design", "print"]

// ✅ Нужны ID:
"assigned_stages": [2, 3]
```

---

## ✅ ПРОВЕРОЧНЫЙ СПИСОК

После внесения изменений проверьте:

-   [ ] **Удалены** все `has_*_stage` из кода
-   [ ] **Обновлены** все фильтры продуктов
-   [ ] **Добавлен** `current_stage` в создание заказов
-   [ ] **Работает** создание продуктов (автоназначение стадий)
-   [ ] **Работает** создание заказов
-   [ ] **Работает** создание назначений
-   [ ] **Нет ошибок** в консоли браузера
-   [ ] **Нет 404/422** ошибок в Network tab

---

## 📞 ПОМОЩЬ

### **Если не работает:**

1. **Проверьте консоль** - есть ли ошибки JS?
2. **Проверьте Network** - какие запросы падают?
3. **Проверьте API** - отвечает ли `/api/stages`?
4. **Сверьтесь с [FRONTEND_MIGRATION_GUIDE.md](./FRONTEND_MIGRATION_GUIDE.md)**

### **Готовые примеры:**

-   [ASSIGNMENT_EXAMPLES.md](./ASSIGNMENT_EXAMPLES.md) - 10 сценариев
-   [BULK_ASSIGNMENT_EXAMPLES.md](./BULK_ASSIGNMENT_EXAMPLES.md) - массовые операции

**Система полностью готова! Удачи! 🚀**
