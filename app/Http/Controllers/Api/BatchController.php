<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheService;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;

class BatchController extends Controller
{
    protected OrderRepository $orderRepository;
    protected ProductRepository $productRepository;

    public function __construct(
        OrderRepository $orderRepository,
        ProductRepository $productRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Batch endpoint для объединения нескольких запросов в один
     * Полезно для медленного интернета - уменьшает количество запросов
     */
    public function batch(Request $request)
    {
        $user = request()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $requests = $request->validate([
            'requests' => 'required|array|max:10', // Максимум 10 запросов за раз
            'requests.*.endpoint' => 'required|string',
            'requests.*.method' => 'required|string|in:GET,POST,PUT,DELETE',
            'requests.*.params' => 'nullable|array',
        ])['requests'];

        $results = [];
        $cacheKey = 'batch_' . $user->id . '_' . md5(json_encode($requests));

        // Кэшируем batch запросы на 5 минут
        return Cache::remember($cacheKey, 300, function () use ($requests, $user, &$results) {
            foreach ($requests as $index => $req) {
                try {
                    $result = $this->executeRequest($req, $user);
                    $results[$index] = [
                        'success' => true,
                        'data' => $result
                    ];
                } catch (\Exception $e) {
                    $results[$index] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
            return $results;
        });
    }

    /**
     * Выполнить отдельный запрос из batch
     */
    private function executeRequest(array $request, $user)
    {
        $endpoint = $request['endpoint'];
        $method = $request['method'];
        $params = $request['params'] ?? [];

        // Простая маршрутизация для популярных endpoints
        switch ($endpoint) {
            case '/orders':
                if ($method === 'GET') {
                    $req = new \Illuminate\Http\Request($params);
                    $req->setUserResolver(fn() => $user);
                    return $this->orderRepository->getPaginatedOrders($req, $user);
                }
                break;

            case '/products':
                if ($method === 'GET') {
                    $req = new \Illuminate\Http\Request($params);
                    return $this->productRepository->getPaginatedProducts($req);
                }
                break;

            case '/stages':
                if ($method === 'GET') {
                    return \App\Models\Stage::getAllStagesByName();
                }
                break;

            case '/roles':
                if ($method === 'GET') {
                    $cacheKey = CacheService::PATTERN_ROLES_WITH_USERS;
                    return CacheService::rememberWithTags($cacheKey, 7200, function () {
                        return \App\Models\Role::select('id', 'name', 'display_name', 'description', 'created_at', 'updated_at')
                            ->withCount('users')
                            ->with(['stages' => function ($q) {
                                $q->select('stages.id', 'stages.name', 'stages.display_name', 'stages.order');
                            }])
                            ->orderBy('display_name')
                            ->get();
                    }, [CacheService::TAG_ROLES]);
                }
                break;

            default:
                throw new \Exception("Endpoint not supported in batch: {$endpoint}");
        }

        throw new \Exception("Method not supported: {$method} for {$endpoint}");
    }
}

