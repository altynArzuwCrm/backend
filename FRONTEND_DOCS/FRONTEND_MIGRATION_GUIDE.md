# 🚀 FRONTEND MIGRATION GUIDE - DYNAMIC STAGES & ROLES

## 📋 ОБЗОР ИЗМЕНЕНИЙ

Система была полностью переработана с **hardcoded стадий** на **динамическую систему**. Все стадии и роли теперь управляются через API и базу данных.

---

## ❌ УДАЛЕННЫЕ ПОЛЯ (НЕ ИСПОЛЬЗУЙТЕ!)

### Products

```javascript
// ❌ УСТАРЕВШИЕ ПОЛЯ - УДАЛЕНЫ!
{
  has_design_stage: true,     // УДАЛЕНО
  has_print_stage: true,      // УДАЛЕНО
  has_workshop_stage: true,   // УДАЛЕНО
  has_engraving_stage: true,  // УДАЛЕНО
  designer_id: 1,             // УДАЛЕНО
  print_operator_id: 2,       // УДАЛЕНО
  workshop_worker_id: 3       // УДАЛЕНО
}
```

### OrderAssignments

```javascript
// ❌ УСТАРЕВШИЕ ПОЛЯ - УДАЛЕНЫ!
{
  has_design_stage: true,     // УДАЛЕНО
  has_print_stage: true,      // УДАЛЕНО
  has_workshop_stage: true,   // УДАЛЕНО
  has_engraving_stage: true   // УДАЛЕНО
}
```

---

## ✅ НОВАЯ СТРУКТУРА ДАННЫХ

### 1. Product Response (GET /api/products)

```javascript
{
  "data": [
    {
      "id": 1,
      "name": "Смарт Блокнот A5-1 Черный",
      "created_at": "2025-07-28T10:00:00.000000Z",
      "updated_at": "2025-07-28T10:00:00.000000Z",

      // ✅ НОВОЕ ПОЛЕ!
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
            "is_default": true  // Стартовая стадия для заказов
          }
        },
        {
          "id": 2,
          "name": "design",
          "display_name": "Дизайн",
          "color": "#3b82f6",
          "order": 2,
          "is_initial": false,
          "is_final": false,
          "pivot": {
            "is_available": true,
            "is_default": false
          }
        }
        // ... остальные стадии
      ]
    }
  ]
}
```

### 2. Order Response (GET /api/orders)

```javascript
{
  "data": [
    {
      "id": 1,
      "current_stage": "design",  // ✅ НОВОЕ! Текущая стадия
      "product_id": 1,
      "client_id": 1,
      "status": "active",

      // ✅ НОВОЕ! Связь с текущей стадией
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

          // ✅ НОВОЕ! Назначенные стадии
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

---

## 🌐 НОВЫЕ API ЭНДПОИНТЫ

### Stages Management

```javascript
// Получить все стадии
GET /api/stages
Response: {
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
      "color": "#6b7280"
    }
  ]
}

// Создать стадию
POST /api/stages
Body: {
  "name": "quality_check",
  "display_name": "Контроль качества",
  "description": "Проверка качества продукции",
  "order": 6,
  "color": "#10b981"
}

// Обновить стадию
PUT /api/stages/{id}
Body: {
  "display_name": "Новое название",
  "color": "#ef4444"
}

// Удалить стадию
DELETE /api/stages/{id}

// Изменить порядок стадий
POST /api/stages/reorder
Body: {
  "stages": [
    {"id": 1, "order": 1},
    {"id": 2, "order": 2}
  ]
}
```

### Roles Management

```javascript
// Получить все роли
GET /api/roles
Response: {
  "data": [
    {
      "id": 1,
      "name": "designer",
      "display_name": "Дизайнер",
      "description": "Создание дизайнов"
    }
  ]
}

// Создать роль
POST /api/roles
Body: {
  "name": "quality_manager",
  "display_name": "Менеджер качества",
  "description": "Контроль качества продукции"
}

// Назначить пользователей на роль
POST /api/roles/{id}/assign-users
Body: {
  "user_ids": [1, 2, 3]
}

// Убрать пользователей с роли
POST /api/roles/{id}/remove-users
Body: {
  "user_ids": [1, 2]
}
```

### Product Stages Management

```javascript
// Получить стадии продукта
GET /api/products/{id}/stages
Response: {
  "data": [
    {
      "id": 1,
      "name": "design",
      "display_name": "Дизайн",
      "is_available": true,
      "is_default": false
    }
  ]
}

// Обновить все стадии продукта
PUT /api/products/{id}/stages
Body: {
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

// Добавить стадию к продукту
POST /api/products/{id}/stages
Body: {
  "stage_id": 3,
  "is_available": true,
  "is_default": false
}

// Убрать стадию у продукта
DELETE /api/products/{id}/stages/{stage_id}
```

---

## 🔄 ИЗМЕНЕНИЯ В СУЩЕСТВУЮЩИХ API

### Products API

```javascript
// Создание продукта
POST /api/products
Body: {
  "name": "Новый продукт",

  // ✅ НОВОЕ! Опциональные стадии
  "stages": [
    {
      "stage_id": 1,
      "is_available": true,
      "is_default": true
    }
  ]
}
// Если stages не указаны - автоматически назначаются ВСЕ стадии
```

### Orders API

```javascript
// Создание заказа
POST /api/orders
Body: {
  "product_id": 1,
  "client_id": 1,
  "quantity": 10,
  "current_stage": "draft"  // ✅ НОВОЕ! Обязательно указать стадию
}

// Обновление стадии заказа
PUT /api/orders/{id}
Body: {
  "current_stage": "design"  // ✅ Новая стадия
}
```

### OrderAssignments API

```javascript
// Создание назначения
POST /api/order-assignments
Body: {
  "order_id": 1,
  "user_id": 4,
  "role_type": "designer",

  // ✅ НОВОЕ! Назначенные стадии
  "assigned_stages": [1, 2]  // ID стадий
}

// Обновление назначения
PUT /api/order-assignments/{id}
Body: {
  "assigned_stages": [2, 3]  // Новые стадии
}
```

---

## 📱 ПРИМЕРЫ КОМПОНЕНТОВ

### 1. Stage Selector Component

```vue
<template>
    <div class="stage-selector">
        <h3>Доступные стадии</h3>
        <div
            v-for="stage in availableStages"
            :key="stage.id"
            class="stage-item"
        >
            <label>
                <input
                    type="checkbox"
                    :checked="selectedStages.includes(stage.id)"
                    @change="toggleStage(stage.id)"
                />
                <span :style="{ color: stage.color }">
                    {{ stage.display_name }}
                </span>
            </label>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            availableStages: [],
            selectedStages: [],
        };
    },

    async mounted() {
        const response = await fetch("/api/stages");
        const data = await response.json();
        this.availableStages = data.data;
    },

    methods: {
        toggleStage(stageId) {
            if (this.selectedStages.includes(stageId)) {
                this.selectedStages = this.selectedStages.filter(
                    (id) => id !== stageId
                );
            } else {
                this.selectedStages.push(stageId);
            }
        },
    },
};
</script>
```

### 2. Product Form Component

```vue
<template>
    <form @submit.prevent="createProduct">
        <input
            v-model="product.name"
            placeholder="Название продукта"
            required
        />

        <!-- ✅ НОВОЕ! Выбор стадий -->
        <stage-selector v-model="product.stages" />

        <button type="submit">Создать продукт</button>
    </form>
</template>

<script>
export default {
    data() {
        return {
            product: {
                name: "",
                stages: [], // ✅ НОВОЕ!
            },
        };
    },

    methods: {
        async createProduct() {
            const response = await fetch("/api/products", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(this.product),
            });

            if (response.ok) {
                // Успех! Продукт создан с автоназначением стадий
                this.$router.push("/products");
            }
        },
    },
};
</script>
```

---

## ⚠️ КРИТИЧНЫЕ ИЗМЕНЕНИЯ

### 1. Проверка полей в формах

```javascript
// ❌ УБРАТЬ эти проверки:
if (product.has_design_stage) { ... }
if (assignment.has_print_stage) { ... }

// ✅ ЗАМЕНИТЬ на:
if (product.available_stages.some(s => s.name === 'design')) { ... }
if (assignment.assigned_stages.some(s => s.name === 'print')) { ... }
```

### 2. Фильтрация данных

```javascript
// ❌ УБРАТЬ старые фильтры:
const designProducts = products.filter((p) => p.has_design_stage);

// ✅ ЗАМЕНИТЬ на:
const designProducts = products.filter((p) =>
    p.available_stages.some((s) => s.name === "design")
);
```

### 3. Создание заказов

```javascript
// ❌ УБРАТЬ старую логику:
const order = {
    product_id: 1,
    has_design_stage: true, // УДАЛЕНО!
};

// ✅ НОВАЯ логика:
const order = {
    product_id: 1,
    current_stage: "draft", // ОБЯЗАТЕЛЬНО!
};
```

---

## 🎯 ПЛАН МИГРАЦИИ ФРОНТЕНДА

### Этап 1: Обновить API вызовы

1. ✅ Заменить все запросы на новые эндпоинты
2. ✅ Убрать обработку `has_*_stage` полей
3. ✅ Добавить обработку `available_stages`

### Этап 2: Обновить компоненты

1. ✅ ProductForm - добавить выбор стадий
2. ✅ OrderForm - добавить current_stage
3. ✅ AssignmentForm - добавить assigned_stages
4. ✅ StageManager - новый компонент управления

### Этап 3: Обновить фильтры и поиск

1. ✅ Заменить фильтры по `has_*_stage`
2. ✅ Добавить поиск по `stage.name`
3. ✅ Обновить сортировку по `stage.order`

### Этап 4: UI/UX улучшения

1. ✅ Цветовая индикация стадий (`stage.color`)
2. ✅ Drag & drop для reorder стадий
3. ✅ Автокомплит для выбора ролей

---

## 🔧 УТИЛИТЫ ДЛЯ РАЗРАБОТКИ

### API Helper

```javascript
// utils/api.js
export const StagesAPI = {
    async getAll() {
        const response = await fetch("/api/stages");
        return response.json();
    },

    async create(stage) {
        const response = await fetch("/api/stages", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(stage),
        });
        return response.json();
    },

    async reorder(stages) {
        const response = await fetch("/api/stages/reorder", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ stages }),
        });
        return response.json();
    },
};
```

### Stage Helper

```javascript
// utils/stages.js
export function getStageByName(stages, name) {
    return stages.find((stage) => stage.name === name);
}

export function isStageAvailable(product, stageName) {
    return product.available_stages.some((s) => s.name === stageName);
}

export function getDefaultStage(product) {
    return product.available_stages.find((s) => s.pivot.is_default);
}
```

---

## 📞 ПОДДЕРЖКА

При возникновении вопросов по миграции:

1. 📖 Проверьте этот гайд
2. 🔍 Изучите новые API эндпоинты
3. 🧪 Тестируйте изменения пошагово
4. 💬 Задавайте вопросы в команде

**Удачной миграции! 🚀**
