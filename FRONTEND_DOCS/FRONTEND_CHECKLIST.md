# ✅ FRONTEND MIGRATION CHECKLIST

## 🚨 КРИТИЧНЫЕ ИЗМЕНЕНИЯ - СДЕЛАТЬ ПЕРВЫМ ДЕЛОМ!

### ❌ УДАЛИТЬ из всех файлов:

-   [ ] `has_design_stage` - везде убрать
-   [ ] `has_print_stage` - везде убрать
-   [ ] `has_workshop_stage` - везде убрать
-   [ ] `has_engraving_stage` - везде убрать
-   [ ] `designer_id` из продуктов
-   [ ] `print_operator_id` из продуктов
-   [ ] `workshop_worker_id` из продуктов

### ✅ ЗАМЕНИТЬ на:

-   [ ] `available_stages` в продуктах
-   [ ] `assigned_stages` в назначениях
-   [ ] `current_stage` в заказах

---

## 📡 API ENDPOINTS - ОБНОВИТЬ ВЫЗОВЫ

### Новые эндпоинты:

-   [ ] `GET /api/stages` - получить все стадии
-   [ ] `POST /api/stages` - создать стадию
-   [ ] `PUT /api/stages/{id}` - обновить стадию
-   [ ] `DELETE /api/stages/{id}` - удалить стадию
-   [ ] `POST /api/stages/reorder` - изменить порядок

-   [ ] `GET /api/roles` - получить все роли
-   [ ] `POST /api/roles` - создать роль
-   [ ] `PUT /api/roles/{id}` - обновить роль
-   [ ] `POST /api/roles/{id}/assign-users` - назначить пользователей

-   [ ] `GET /api/products/{id}/stages` - стадии продукта
-   [ ] `PUT /api/products/{id}/stages` - обновить стадии продукта
-   [ ] `POST /api/products/{id}/stages` - добавить стадию
-   [ ] `DELETE /api/products/{id}/stages/{stage_id}` - убрать стадию

### Обновленные эндпоинты:

-   [ ] `POST /api/products` - добавить поле `stages`
-   [ ] `POST /api/orders` - добавить поле `current_stage`
-   [ ] `POST /api/order-assignments` - добавить поле `assigned_stages`

---

## 🎨 КОМПОНЕНТЫ - СОЗДАТЬ/ОБНОВИТЬ

### Новые компоненты:

-   [ ] `StageSelector.vue` - выбор стадий
-   [ ] `StageManager.vue` - управление стадиями
-   [ ] `RoleManager.vue` - управление ролями
-   [ ] `ProductStages.vue` - стадии продукта

### Обновить существующие:

-   [ ] `ProductForm.vue` - добавить выбор стадий
-   [ ] `OrderForm.vue` - добавить current_stage
-   [ ] `AssignmentForm.vue` - добавить assigned_stages
-   [ ] `ProductList.vue` - показать available_stages
-   [ ] `OrderList.vue` - показать current_stage + цвет

---

## 🔍 ФИЛЬТРЫ И ПОИСК - ПЕРЕПИСАТЬ

### Заменить фильтры:

-   [ ] Вместо `has_design_stage` → `available_stages.some(s => s.name === 'design')`
-   [ ] Вместо `has_print_stage` → `available_stages.some(s => s.name === 'print')`
-   [ ] Добавить фильтр по `stage.color`
-   [ ] Добавить сортировку по `stage.order`

### Поиск:

-   [ ] По `stage.name`
-   [ ] По `stage.display_name`
-   [ ] По `role.name`

---

## 📱 UI/UX УЛУЧШЕНИЯ

### Цвета и визуализация:

-   [ ] Использовать `stage.color` для индикации
-   [ ] Прогресс-бар по стадиям
-   [ ] Иконки для типов стадий

### Интерактивность:

-   [ ] Drag & drop для reorder стадий
-   [ ] Автокомплит для выбора ролей
-   [ ] Быстрые фильтры по стадиям

---

## 🧪 ТЕСТИРОВАНИЕ

### Проверить работу:

-   [ ] Создание продукта с автоназначением стадий
-   [ ] Создание продукта с кастомными стадиями
-   [ ] Создание заказа с указанием стадии
-   [ ] Смена стадии заказа
-   [ ] Назначение пользователей на стадии
-   [ ] Фильтрация по новым полям

### Проверить отсутствие:

-   [ ] Ошибок в консоли про `has_*_stage`
-   [ ] 404 ошибок на старых эндпоинтах
-   [ ] Неработающих фильтров

---

## 🔧 УТИЛИТЫ - СОЗДАТЬ

### API helpers:

-   [ ] `StagesAPI.js` - все операции со стадиями
-   [ ] `RolesAPI.js` - все операции с ролями
-   [ ] `ProductStagesAPI.js` - стадии продуктов

### Helper функции:

-   [ ] `getStageByName(stages, name)`
-   [ ] `isStageAvailable(product, stageName)`
-   [ ] `getDefaultStage(product)`
-   [ ] `getStageColor(stageName)`

---

## 📋 ПРОВЕРКА ПЕРЕД РЕЛИЗОМ

### Код:

-   [ ] Удалены ВСЕ упоминания `has_*_stage`
-   [ ] Обновлены ВСЕ API вызовы
-   [ ] Работают ВСЕ новые компоненты
-   [ ] Нет ошибок в консоли

### Функциональность:

-   [ ] Создание продуктов работает
-   [ ] Создание заказов работает
-   [ ] Назначения работают
-   [ ] Фильтры работают
-   [ ] Поиск работает

### Пользовательский интерфейс:

-   [ ] Цвета стадий отображаются
-   [ ] Названия корректные
-   [ ] Порядок стадий правильный
-   [ ] Нет сломанных элементов

---

## 🆘 ЭКСТРЕННАЯ ПОМОЩЬ

### Если что-то не работает:

1. 🔍 Проверь консоль браузера на ошибки
2. 📡 Проверь Network tab - какие запросы 404/500
3. 📖 Сверься с `FRONTEND_MIGRATION_GUIDE.md`
4. 🧪 Протестируй API напрямую в Postman
5. 💬 Спроси в команде

### Частые ошибки:

-   ❌ Забыл убрать `has_*_stage` из запроса
-   ❌ Неправильный URL эндпоинта
-   ❌ Забыл добавить `current_stage` в заказ
-   ❌ Старый фильтр по несуществующему полю

**Удачи! 💪**
