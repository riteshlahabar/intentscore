<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SmartLink\IntentScore;
use App\Models\SmartLink\Prospect;
use App\Models\SmartLink\SalesActivity;
use App\Models\SmartLink\SmartLinkModel;
use App\Models\SmartLink\SmartPageVisit;
use App\Models\User;
use App\Services\Common\AccessService;
use Illuminate\View\View;

/**
 * The "Dashboard" sidebar link and the post-login landing page. Renders a
 * distinct summary per role - a business-wide pipeline snapshot for admins,
 * a personal snapshot for salespeople - separate from the detailed traffic
 * analytics on the Smart Links Overview page and the raw list on My Prospects.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly AccessService $access)
    {
    }

    public function index(): View
    {
        return $this->access->isSalesperson() ? $this->salesperson() : $this->admin();
    }

    private function admin(): View
    {
        $statusCounts = Prospect::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'prospects' => Prospect::count(),
            'new_this_week' => Prospect::where('created_at', '>=', now()->subDays(7))->count(),
            'smart_links' => SmartLinkModel::count(),
            'unseen_alerts' => IntentScore::where('intent_level', 'HIGH INTENT')->where('alert_seen', false)->count(),
        ];

        $leaderboard = User::where('role', 'salesperson')
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                $ids = Prospect::where('salesperson_id', $user->id)->pluck('id');

                return (object) [
                    'name' => $user->name,
                    'prospects' => $ids->count(),
                    'won' => Prospect::whereIn('id', $ids)->where('status', 'won')->count(),
                    'high_intent' => IntentScore::whereIn('prospect_id', $ids)->where('intent_level', 'HIGH INTENT')->count(),
                ];
            })
            ->filter(fn ($row) => $row->prospects > 0)
            ->sortByDesc('won')
            ->take(5)
            ->values();

        return view('admin.dashboard.admin', [
            'statusCounts' => $statusCounts,
            'stats' => $stats,
            'leaderboard' => $leaderboard,
            'alerts' => IntentScore::with('prospect')
                ->where('intent_level', 'HIGH INTENT')->where('alert_seen', false)
                ->latest('updated_at')->limit(6)->get(),
            'activity' => SalesActivity::with(['prospect', 'user'])->latest()->limit(8)->get(),
        ]);
    }

    private function salesperson(): View
    {
        $userId = auth()->id();
        $ids = Prospect::where('salesperson_id', $userId)->pluck('id');

        $statusCounts = Prospect::where('salesperson_id', $userId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'prospects' => $ids->count(),
            'won' => Prospect::whereIn('id', $ids)->where('status', 'won')->count(),
            'high_intent' => IntentScore::whereIn('prospect_id', $ids)->where('intent_level', 'HIGH INTENT')->count(),
            'visits' => SmartPageVisit::whereIn('prospect_id', $ids)->count(),
        ];

        return view('admin.dashboard.salesperson', [
            'statusCounts' => $statusCounts,
            'stats' => $stats,
            'alerts' => IntentScore::with('prospect')
                ->whereIn('prospect_id', $ids)
                ->where('intent_level', 'HIGH INTENT')->where('alert_seen', false)
                ->latest('updated_at')->limit(6)->get(),
            'activity' => SalesActivity::with('prospect')
                ->whereIn('prospect_id', $ids)
                ->where('user_id', $userId)
                ->latest()->limit(8)->get(),
        ]);
    }
}
