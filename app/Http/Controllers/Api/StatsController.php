<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsController extends Controller
{
    public function index()
    {
        $users = User::count();

        $orders = Order::count();

        $revenue = Project::sum('total_price') ?? 0;

        $newClients = Client::where('created_at', '>=', Carbon::now()->subDays(30))->count();

        return response()->json([
            'users' => $users,
            'orders' => $orders,
            'revenue' => $revenue,
            'newClients' => $newClients,
        ]);
    }
}
