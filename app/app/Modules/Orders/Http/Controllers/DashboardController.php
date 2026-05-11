<?php

namespace CMBcoreSeller\Modules\Orders\Http\Controllers;

use CMBcoreSeller\Http\Controllers\Controller;
use CMBcoreSeller\Modules\Channels\Models\ChannelAccount;
use CMBcoreSeller\Modules\Orders\Models\Order;
use CMBcoreSeller\Support\Enums\StandardOrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/dashboard/summary — headline counts for the dashboard cards / to-do
 * panel. (Lives in the Orders module for now; a fuller Reports module reads
 * everything via interfaces later.) See SPEC 0001 §6.
 */
class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        $today = now()->startOfDay();
        $preShipment = array_map(fn (StandardOrderStatus $s) => $s->value, array_filter(
            StandardOrderStatus::cases(), fn ($s) => $s->isPreShipment()
        ));

        return response()->json(['data' => [
            'channel_accounts' => [
                'total' => ChannelAccount::query()->count(),
                'active' => ChannelAccount::query()->active()->count(),
                'needs_reconnect' => ChannelAccount::query()->where('status', ChannelAccount::STATUS_EXPIRED)->count(),
            ],
            'orders' => [
                'today' => Order::query()->where('placed_at', '>=', $today)->count(),
                'to_process' => Order::query()->statusIn($preShipment)->count(),
                'ready_to_ship' => Order::query()->where('status', StandardOrderStatus::ReadyToShip->value)->count(),
                'shipped' => Order::query()->where('status', StandardOrderStatus::Shipped->value)->count(),
                'has_issue' => Order::query()->where('has_issue', true)->count(),
                'total' => Order::query()->count(),
            ],
            'revenue_today' => (int) Order::query()
                ->where('placed_at', '>=', $today)
                ->whereNotIn('status', [StandardOrderStatus::Cancelled->value])
                ->sum('grand_total'),
        ]]);
    }
}
