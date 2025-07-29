# 📚 ПОЛНАЯ ДОКУМЕНТАЦИЯ ДЛЯ ФРОНТЕНДА

## 🎯 О ЧЕМ ЭТА ПАПКА

Система была **полностью переработана** с hardcoded стадий на **динамическую систему**.
Эта папка содержит **ВСЮ информацию** необходимую для адаптации фронтенда под новую архитектуру.

---

## 📋 СОСТАВ ДОКУМЕНТАЦИИ

### 🚀 **НАЧАТЬ ОТСЮДА:**

1. **[FRONTEND_MIGRATION_GUIDE.md](./FRONTEND_MIGRATION_GUIDE.md)** - Полное руководство по миграции
2. **[FRONTEND_CHECKLIST.md](./FRONTEND_CHECKLIST.md)** - Чеклист задач для выполнения

### 🎯 **СИСТЕМА НАЗНАЧЕНИЙ:**

3. **[ASSIGNMENT_SYSTEM_GUIDE.md](./ASSIGNMENT_SYSTEM_GUIDE.md)** - Как работает новая система
4. **[ASSIGNMENT_EXAMPLES.md](./ASSIGNMENT_EXAMPLES.md)** - 10 практических примеров
5. **[BULK_ASSIGNMENT_EXAMPLES.md](./BULK_ASSIGNMENT_EXAMPLES.md)** - Массовые операции

### 🔐 **БЕЗОПАСНОСТЬ:**

6. **[PERMISSIONS_GUIDE.md](./PERMISSIONS_GUIDE.md)** - Права доступа пользователей

---

## ⚡ КРАТКОЕ РЕЗЮМЕ ИЗМЕНЕНИЙ

### ❌ **ЧТО УДАЛЕНО (НЕ ИСПОЛЬЗУЙТЕ!):**

```javascript
// Полностью удалены из API:
has_design_stage: true; // ❌ УДАЛЕНО
has_print_stage: true; // ❌ УДАЛЕНО
has_workshop_stage: true; // ❌ УДАЛЕНО
has_engraving_stage: true; // ❌ УДАЛЕНО
designer_id: 1; // ❌ УДАЛЕНО
print_operator_id: 2; // ❌ УДАЛЕНО
workshop_worker_id: 3; // ❌ УДАЛЕНО
```

### ✅ **ЧТО ДОБАВЛЕНО (ИСПОЛЬЗУЙТЕ!):**

```javascript
// Новые поля в API:
available_stages: [...]     // ✅ В продуктах
assigned_stages: [...]      // ✅ В назначениях
current_stage: "design"     // ✅ В заказах
```

---

## 🌐 НОВЫЕ API ENDPOINTS

### **Управление стадиями:**

```
GET    /api/stages                    - Все стадии
POST   /api/stages                    - Создать стадию
PUT    /api/stages/{id}               - Обновить стадию
DELETE /api/stages/{id}               - Удалить стадию
POST   /api/stages/reorder            - Изменить порядок
```

### **Управление ролями:**

```
GET    /api/roles                     - Все роли
POST   /api/roles                     - Создать роль
POST   /api/roles/{id}/assign-users   - Назначить пользователей
POST   /api/roles/{id}/remove-users   - Убрать пользователей
```

### **Стадии продуктов:**

```
GET    /api/products/{id}/stages      - Стадии продукта
PUT    /api/products/{id}/stages      - Обновить стадии
POST   /api/products/{id}/stages      - Добавить стадию
DELETE /api/products/{id}/stages/{stage_id} - Убрать стадию
```

### **Массовые назначения:**

```
POST   /api/orders/{order}/bulk-assign        - На один заказ
POST   /api/assignments/bulk-assign           - На разные заказы
POST   /api/assignments/bulk-reassign         - Переназначение
POST   /api/assignments/bulk-update           - Обновление стадий
```

---

## 🔄 ИЗМЕНЕННЫЕ API ENDPOINTS

### **Products API:**

```javascript
// Старый формат (НЕ РАБОТАЕТ):
POST /api/products {
  "name": "Продукт",
  "has_design_stage": true    // ❌ УДАЛЕНО
}

// Новый формат:
POST /api/products {
  "name": "Продукт",
  "stages": [                 // ✅ НОВОЕ
    {
      "stage_id": 1,
      "is_available": true,
      "is_default": true
    }
  ]
}
// Если stages не указаны - автоматически назначаются ВСЕ стадии
```

### **Orders API:**

```javascript
// Старый формат (НЕ РАБОТАЕТ):
POST /api/orders {
  "product_id": 1,
  "has_design_stage": true    // ❌ УДАЛЕНО
}

// Новый формат:
POST /api/orders {
  "product_id": 1,
  "current_stage": "draft"    // ✅ ОБЯЗАТЕЛЬНО
}
```

### **OrderAssignments API:**

```javascript
// Старый формат (НЕ РАБОТАЕТ):
POST /api/order-assignments {
  "order_id": 1,
  "user_id": 4,
  "has_design_stage": true    // ❌ УДАЛЕНО
}

// Новый формат:
POST /api/order-assignments {
  "order_id": 1,
  "user_id": 4,
  "role_type": "designer",
  "assigned_stages": [1, 2]   // ✅ НОВОЕ (ID стадий)
}
```

---

## 📊 НОВАЯ СТРУКТУРА ДАННЫХ

### **Product Response:**

```javascript
{
  "id": 1,
  "name": "Блокнот",
  "available_stages": [       // ✅ НОВОЕ ПОЛЕ
    {
      "id": 1,
      "name": "draft",
      "display_name": "Черновик",
      "color": "#6b7280",
      "order": 1,
      "pivot": {
        "is_available": true,
        "is_default": true    // Стартовая стадия
      }
    }
  ]
}
```

### **Order Response:**

```javascript
{
  "id": 1,
  "current_stage": "design",  // ✅ НОВОЕ
  "current_stage_info": {     // ✅ НОВОЕ
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
      "assigned_stages": [     // ✅ НОВОЕ
        {
          "id": 2,
          "name": "design",
          "display_name": "Дизайн"
        }
      ]
    }
  ]
}
```

---

## 🎨 КОМПОНЕНТЫ ДЛЯ СОЗДАНИЯ

### **Новые компоненты:**

-   `StageSelector.vue` - Выбор стадий для продуктов
-   `StageManager.vue` - Управление стадиями (админ)
-   `RoleManager.vue` - Управление ролями (админ)
-   `ProductStages.vue` - Настройка стадий продукта
-   `BulkAssignment.vue` - Массовые назначения
-   `AssignmentCalendar.vue` - Календарь назначений

### **Обновить существующие:**

-   `ProductForm.vue` - Добавить выбор стадий
-   `OrderForm.vue` - Добавить current_stage
-   `AssignmentForm.vue` - Добавить assigned_stages
-   `ProductList.vue` - Показать available_stages
-   `OrderList.vue` - Показать current_stage + цвет
-   `UserDashboard.vue` - Новая статистика

---

## 🔍 КРИТИЧНЫЕ ЗАМЕНЫ В КОДЕ

### **1. Фильтры продуктов:**

```javascript
// ❌ СТАРОЕ (не работает):
const designProducts = products.filter((p) => p.has_design_stage);

// ✅ НОВОЕ:
const designProducts = products.filter((p) =>
    p.available_stages.some((s) => s.name === "design")
);
```

### **2. Проверка доступности стадии:**

```javascript
// ❌ СТАРОЕ:
if (product.has_print_stage) { ... }

// ✅ НОВОЕ:
if (product.available_stages.some(s => s.name === 'print')) { ... }
```

### **3. Создание заказа:**

```javascript
// ❌ СТАРОЕ:
const order = {
    product_id: 1,
    has_design_stage: true,
};

// ✅ НОВОЕ:
const order = {
    product_id: 1,
    current_stage: "draft", // ОБЯЗАТЕЛЬНО!
};
```

---

## 🚨 ПРАВА ДОСТУПА

### **Массовые операции - только Admin + Manager:**

-   Aylana (admin)
-   Test (manager)

### **Обычные пользователи (только просмотр своих):**

-   Все дизайнеры, операторы, цех

---

## 📝 ПЛАН РАБОТЫ ДЛЯ ФРОНТЕНДА

### **Этап 1: Подготовка (1-2 дня)**

1. ✅ Изучить документацию
2. ✅ Проверить новые API в Postman/Insomnia
3. ✅ Спланировать изменения компонентов

### **Этап 2: Основные изменения (3-5 дней)**

1. ✅ Удалить все `has_*_stage` из кода
2. ✅ Обновить API вызовы
3. ✅ Заменить фильтры и условия
4. ✅ Обновить формы создания/редактирования

### **Этап 3: Новая функциональность (2-3 дня)**

1. ✅ Создать компоненты управления стадиями
2. ✅ Добавить массовые операции
3. ✅ Реализовать цветовую индикацию стадий

### **Этап 4: Тестирование (1-2 дня)**

1. ✅ Протестировать все сценарии
2. ✅ Проверить права доступа
3. ✅ Оптимизировать производительность

---

## 🆘 ПОДДЕРЖКА

### **Если что-то не понятно:**

1. 📖 Проверьте соответствующий .md файл в этой папке
2. 🧪 Протестируйте API напрямую
3. 💬 Задавайте вопросы команде

### **Полезные ссылки:**

-   [FRONTEND_MIGRATION_GUIDE.md](./FRONTEND_MIGRATION_GUIDE.md) - Полное руководство
-   [FRONTEND_CHECKLIST.md](./FRONTEND_CHECKLIST.md) - Чеклист задач
-   [ASSIGNMENT_EXAMPLES.md](./ASSIGNMENT_EXAMPLES.md) - Примеры кода

---

## ✅ ИТОГ

**Система полностью готова к использованию!**
Все изменения протестированы и работают в production.

**Удачной миграции! 🚀**

---

**📁 Состав папки FRONTEND_DOCS:**

-   README.md (этот файл)
-   FRONTEND_MIGRATION_GUIDE.md
-   FRONTEND_CHECKLIST.md
-   ASSIGNMENT_SYSTEM_GUIDE.md
-   ASSIGNMENT_EXAMPLES.md
-   BULK_ASSIGNMENT_EXAMPLES.md
-   PERMISSIONS_GUIDE.md
