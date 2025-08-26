# 🚀 Alytn Arzuw - Backend (Laravel)

Современный бэкенд для системы управления заказами и проектами, построенный на Laravel 10 с оптимизированной производительностью.

## 📋 Содержание

- [Технологии](#технологии)
- [Требования](#требования)
- [Установка и настройка](#установка-и-настройка)
- [Архитектура проекта](#архитектура-проекта)
- [API документация](#api-документация)
- [Модели и отношения](#модели-и-отношения)
- [Репозитории](#репозитории)
- [Политики и разрешения](#политики-и-разрешения)
- [Кэширование](#кэширование)
- [Производительность](#производительность)
- [Безопасность](#безопасность)
- [Тестирование](#тестирование)
- [Развертывание](#развертывание)
- [Мониторинг](#мониторинг)

## 🛠 Технологии

- **Laravel 10** - современный PHP фреймворк
- **PHP 8.1+** - последняя версия PHP
- **MySQL/PostgreSQL** - реляционная база данных
- **Redis** - кэширование и сессии
- **Laravel Sanctum** - API аутентификация
- **Laravel Queue** - асинхронные задачи
- **Laravel Notifications** - система уведомлений
- **Laravel Policies** - управление разрешениями
- **Eloquent ORM** - объектно-реляционное отображение

## ⚙️ Требования

### Системные требования
- PHP 8.1 или выше
- Composer 2.0+
- MySQL 8.0+ или PostgreSQL 13+
- Redis 6.0+ (опционально)
- Node.js 18+ (для сборки фронтенда)

### PHP расширения
```bash
# Обязательные расширения
php-bcmath
php-curl
php-dom
php-fileinfo
php-json
php-mbstring
php-mysql
php-openssl
php-pdo
php-tokenizer
php-xml
php-zip

# Рекомендуемые расширения
php-redis
php-memcached
php-gd
php-imagick
```

## 🚀 Установка и настройка

### 1. Клонирование репозитория
```bash
git clone <repository-url>
cd alytn_arzuw
```

### 2. Установка зависимостей
```bash
composer install
```

### 3. Настройка окружения
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Настройка базы данных
```bash
# В .env файле
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=altyn_arzuw
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Создание базы данных
mysql -u root -p -e "CREATE DATABASE altyn_arzuw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Миграции и сиды
```bash
php artisan migrate
php artisan db:seed
```

### 6. Настройка кэша
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Запуск сервера
```bash
php artisan serve
```

Приложение будет доступно по адресу: `http://localhost:8000`

## 🏗 Архитектура проекта

```
app/
├── Console/           # Консольные команды
├── DTOs/             # Data Transfer Objects
├── Enums/            # Перечисления
├── Http/             # HTTP слой
│   ├── Controllers/  # Контроллеры
│   ├── Middleware/   # Промежуточное ПО
│   ├── Requests/     # Валидация запросов
│   └── Resources/    # API ресурсы
├── Models/           # Eloquent модели
├── Notifications/    # Уведомления
├── Observers/        # Наблюдатели моделей
├── Policies/         # Политики доступа
├── Providers/        # Сервис-провайдеры
├── Repositories/     # Репозитории
└── Services/         # Бизнес-логика

database/
├── migrations/       # Миграции БД
└── seeders/         # Сиды данных

routes/
├── api.php          # API маршруты
├── web.php          # Web маршруты
└── console.php      # Консольные команды
```

## 🔌 API документация

### Аутентификация

#### Вход в систему
```http
POST /api/auth/login
Content-Type: application/json

{
    "username": "user@example.com",
    "password": "password"
}
```

#### Ответ
```json
{
    "user": {
        "id": 1,
        "username": "user@example.com",
        "name": "User Name",
        "roles": ["admin"]
    },
    "token": "1|abc123..."
}
```

### Заказы

#### Получение списка заказов
```http
GET /api/orders?page=1&per_page=20&search=keyword
Authorization: Bearer {token}
```

#### Создание заказа
```http
POST /api/orders
Authorization: Bearer {token}
Content-Type: application/json

{
    "client_id": 1,
    "product_id": 1,
    "quantity": 5,
    "deadline": "2024-12-31",
    "price": 1000.00
}
```

#### Обновление заказа
```http
PUT /api/orders/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "quantity": 10,
    "price": 2000.00
}
```

### Клиенты

#### Получение списка клиентов
```http
GET /api/clients?page=1&search=company
Authorization: Bearer {token}
```

#### Создание клиента
```http
POST /api/clients
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Company Name",
    "company_name": "Company LLC",
    "contacts": [
        {
            "type": "email",
            "value": "contact@company.com"
        }
    ]
}
```

### Продукты

#### Получение списка продуктов
```http
GET /api/products?page=1&search=product
Authorization: Bearer {token}
```

#### Создание продукта
```http
POST /api/products
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Product Name",
    "description": "Product description",
    "price": 500.00
}
```

## 🗄️ Модели и отношения

### Order (Заказ)
```php
class Order extends Model
{
    protected $fillable = [
        'client_id',
        'project_id',
        'product_id',
        'stage_id',
        'quantity',
        'deadline',
        'price',
        'work_type',
        'reason',
        'reason_status',
        'is_archived',
        'archived_at'
    ];

    // Отношения
    public function client() {
        return $this->belongsTo(Client::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function stage() {
        return $this->belongsTo(Stage::class);
    }

    public function assignments() {
        return $this->hasMany(OrderAssignment::class);
    }
}
```

### Client (Клиент)
```php
class Client extends Model
{
    protected $fillable = [
        'name',
        'company_name'
    ];

    public function contacts() {
        return $this->hasMany(ClientContact::class);
    }

    public function orders() {
        return $this->hasMany(Order::class);
    }
}
```

### User (Пользователь)
```php
class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'is_active'
    ];

    public function roles() {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function assignments() {
        return $this->hasMany(OrderAssignment::class);
    }
}
```

## 🏪 Репозитории

### OrderRepository
```php
class OrderRepository
{
    public function getPaginatedOrders(Request $request, User $user): LengthAwarePaginator
    {
        $cacheKey = 'orders_' . $user->id . '_' . md5($request->fullUrl());
        
        return Cache::remember($cacheKey, 300, function () use ($request, $user) {
            $query = Order::with(['project', 'product', 'client', 'stage']);
            
            // Фильтрация по правам доступа
            if (!$user->hasAnyRole(['admin', 'manager'])) {
                $assignedOrderIds = OrderAssignment::query()
                    ->where('user_id', $user->id)
                    ->pluck('order_id');
                $query->whereIn('id', $assignedOrderIds);
            }
            
            // Применение фильтров
            $this->applyFilters($query, $request);
            
            return $query->paginate($request->input('per_page', 30));
        });
    }
}
```

### ClientRepository
```php
class ClientRepository
{
    public function getPaginatedClients(Request $request, User $user): LengthAwarePaginator
    {
        $query = Client::with('contacts');
        
        // Фильтрация по правам доступа
        if (!$user->hasAnyRole(['admin', 'manager'])) {
            $assignedClientIds = $this->getAssignedClientIds($user);
            $query->whereIn('id', $assignedClientIds);
        }
        
        return $query->paginate($request->input('per_page', 30));
    }
}
```

## 🔐 Политики и разрешения

### OrderPolicy
```php
class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'user']);
    }

    public function view(User $user, Order $order): bool
    {
        // Админы и менеджеры видят все заказы
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }
        
        // Обычные пользователи видят только назначенные заказы
        return $order->assignments()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Order $order): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }
}
```

### ClientPolicy
```php
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'user']);
    }

    public function view(User $user, Client $client): bool
    {
        if ($user->hasAnyRole(['admin', 'manager'])) {
            return true;
        }
        
        // Проверяем, есть ли у пользователя заказы этого клиента
        return $client->orders()
            ->whereHas('assignments', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->exists();
    }
}
```

## 💾 Кэширование

### Стратегия кэширования
```php
// Репозитории используют кэширование для часто запрашиваемых данных
$cacheKey = 'orders_' . $user->id . '_' . md5($request->fullUrl());
$cacheTime = 300; // 5 минут

return Cache::remember($cacheKey, $cacheTime, function () {
    // Дорогой запрос к БД
    return $this->executeExpensiveQuery();
});
```

### Очистка кэша
```php
// При изменении данных очищаем соответствующий кэш
public function updateOrder(Order $order, array $data): OrderDTO
{
    $order->update($data);
    
    // Очищаем кэш заказов
    Cache::flush();
    
    return OrderDTO::fromModel($order);
}
```

### Redis кэширование (опционально)
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## ⚡ Производительность

### ✅ Оптимизации N+1 проблем

**До оптимизации (N+1 проблема):**
```php
// ❌ Плохо - N+1 запросов
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->client->name; // Новый запрос для каждого заказа
}
```

**После оптимизации:**
```php
// ✅ Хорошо - 1 запрос с JOIN
$orders = Order::with(['client', 'product', 'stage'])->get();
foreach ($orders as $order) {
    echo $order->client->name; // Данные уже загружены
}
```

### Индексы базы данных
```sql
-- Индексы для ускорения запросов
CREATE INDEX idx_orders_client_id ON orders(client_id);
CREATE INDEX idx_orders_stage_id ON orders(stage_id);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_order_assignments_user_id ON order_assignments(user_id);
CREATE INDEX idx_order_assignments_order_id ON order_assignments(order_id);
```

### Пагинация
```php
// Все API endpoints используют пагинацию
return $query->paginate($request->input('per_page', 30));
```

### Ленивая загрузка
```php
// Используем with() для eager loading
$order = Order::with([
    'client.contacts',
    'product.stages',
    'assignments.user.roles'
])->find($id);
```

## 🔒 Безопасность

### Аутентификация
- **Laravel Sanctum** для API токенов
- JWT-подобные токены с истечением срока действия
- Автоматическое обновление токенов

### Авторизация
- **Policies** для проверки разрешений
- **Middleware** для защиты маршрутов
- **Gates** для сложной логики доступа

### Валидация
```php
// Валидация всех входящих данных
public function store(CreateOrderRequest $request)
{
    $validated = $request->validated();
    // Данные уже проверены
}
```

### Защита от CSRF
- Автоматическая защита для web маршрутов
- API маршруты защищены токенами

### Rate Limiting
```php
// Ограничение количества запросов
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});
```

## 🧪 Тестирование

### Unit тесты
```bash
# Запуск всех тестов
php artisan test

# Запуск конкретного теста
php artisan test tests/Unit/OrderTest.php

# Запуск с покрытием
php artisan test --coverage
```

### Feature тесты
```php
class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_order()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'client_id' => $client->id,
                'product_id' => $product->id,
                'quantity' => 5
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', [
            'client_id' => $client->id,
            'product_id' => $product->id,
            'quantity' => 5
        ]);
    }
}
```

### Database тесты
```bash
# Использование тестовой базы данных
php artisan test --env=testing
```

## 🚀 Развертывание

### Production настройки
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=production-db-host
DB_DATABASE=production_db
DB_USERNAME=prod_user
DB_PASSWORD=secure_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

### Оптимизация для продакшена
```bash
# Кэширование конфигурации
php artisan config:cache

# Кэширование маршрутов
php artisan route:cache

# Кэширование представлений
php artisan view:cache

# Оптимизация автозагрузчика
composer install --optimize-autoloader --no-dev
```

### Supervisor для очередей
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/worker.log
```

### Nginx конфигурация
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/project/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 📊 Мониторинг

### Логирование
```php
// Структурированное логирование
Log::info('Order created', [
    'order_id' => $order->id,
    'user_id' => $user->id,
    'client_id' => $order->client_id
]);
```

### Health checks
```php
// Проверка состояния системы
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::store()->get('health_check') ? 'working' : 'not working'
    ]);
});
```

### Метрики производительности
```php
// Время выполнения запросов
DB::enableQueryLog();
// ... выполнение операций
$queries = DB::getQueryLog();
Log::info('Query performance', ['queries' => $queries]);
```

## 🔧 Консольные команды

### Очистка кэша
```bash
# Очистка всех кэшей
php artisan cache:clear

# Очистка конфигурации
php artisan config:clear

# Очистка маршрутов
php artisan route:clear

# Очистка представлений
php artisan view:clear
```

### Миграции
```bash
# Запуск миграций
php artisan migrate

# Откат последней миграции
php artisan migrate:rollback

# Сброс всех миграций
php artisan migrate:reset

# Перезапуск миграций
php artisan migrate:refresh
```

### Сиды
```bash
# Запуск всех сидов
php artisan db:seed

# Запуск конкретного сида
php artisan db:seed --class=UserSeeder

# Запуск сидов с миграциями
php artisan migrate:fresh --seed
```

## 📚 Дополнительные ресурсы

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Eloquent ORM](https://laravel.com/docs/eloquent)
- [Laravel Policies](https://laravel.com/docs/authorization)
- [Laravel Queue](https://laravel.com/docs/queues)
- [Laravel Notifications](https://laravel.com/docs/notifications)

## 🤝 Вклад в проект

1. Fork репозитория
2. Создайте feature branch (`git checkout -b feature/amazing-feature`)
3. Commit изменения (`git commit -m 'Add amazing feature'`)
4. Push в branch (`git push origin feature/amazing-feature`)
5. Откройте Pull Request

## 📄 Лицензия

Этот проект является частью системы Alytn Arzuw и защищен авторским правом.

## 📞 Поддержка

Если у вас есть вопросы или предложения:

- Создайте Issue в репозитории
- Обратитесь к команде разработки
- Проверьте документацию по API

---

**Создано с ❤️ командой LTM**
