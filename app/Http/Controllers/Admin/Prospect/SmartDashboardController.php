<?php

namespace App\Http\Controllers\Admin\Prospect;

use App\Http\Controllers\Controller;
use App\Models\SmartLink\IntentScore;
use App\Models\SmartLink\Prospect;
use App\Models\SmartLink\SmartEvent;
use App\Models\SmartLink\SmartLinkModel;
use App\Models\SmartLink\SmartPageVisit;
use App\Models\User;

/** Admin dashboard for the Smart Links product (PDF section 14). */
class SmartDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'prospects' => Prospect::count(),
            'smart_links' => SmartLinkModel::count(),
            'visits' => SmartPageVisit::count(),
            'returning' => SmartPageVisit::where('is_return_visit', true)->count(),
            'interested' => IntentScore::where('intent_level', 'INTERESTED')->count(),
            'high_intent' => IntentScore::where('intent_level', 'HIGH INTENT')->count(),
        ];

        $salespeople = User::orderBy('name')
            ->get()
            ->map(function (User $user) {
                $prospectIds = Prospect::where('salesperson_id', $user->id)->pluck('id');

                return (object) [
                    'name' => $user->name,
                    'role' => $user->role,
                    'prospects' => $prospectIds->count(),
                    'visits' => SmartPageVisit::whereIn('prospect_id', $prospectIds)->count(),
                    'high_intent' => IntentScore::whereIn('prospect_id', $prospectIds)
                        ->where('intent_level', 'HIGH INTENT')->count(),
                ];
            })
            ->filter(fn ($row) => $row->prospects > 0)
            ->values();

        return view('admin.prospects.dashboard', [
            'stats' => $stats,
            'salespeople' => $salespeople,
            'hot' => IntentScore::with('prospect.salesperson')
                ->where('score', '>', 0)
                ->orderByDesc('score')
                ->limit(10)
                ->get(),
            'recent' => SmartEvent::with('prospect')
                ->latest('occurred_at')
                ->limit(25)
                ->get(),
        ]);
    }
}
