<?php

namespace App\Http\Controllers\Admin\Prospect;

use App\Http\Controllers\Controller;
use App\Models\SmartLink\IntentScoreRule;
use App\Models\SmartLink\Prospect;
use App\Services\SmartLink\IntentScoreService;
use Illuminate\Http\Request;

/** Admin-configurable scoring weights (PDF section 10). */
class IntentSettingController extends Controller
{
    public function index()
    {
        return view('admin.prospects.intent-settings', [
            'rules' => IntentScoreRule::orderByDesc('points')->get(),
        ]);
    }

    public function update(Request $request, IntentScoreService $intentScore)
    {
        $data = $request->validate([
            'rules' => ['required', 'array'],
            'rules.*.points' => ['required', 'integer', 'min:0', 'max:100'],
            'rules.*.max_times' => ['nullable', 'integer', 'min:1', 'max:50'],
            'rules.*.is_active' => ['nullable', 'boolean'],
        ]);

        foreach ($data['rules'] as $id => $values) {
            IntentScoreRule::where('id', (int) $id)->update([
                'points' => $values['points'],
                'max_times' => $values['max_times'] ?? null,
                'is_active' => (bool) ($values['is_active'] ?? false),
            ]);
        }

        // Weights changed, so every stored score is stale until it is rebuilt.
        Prospect::with('intentScore')->chunkById(200, function ($prospects) use ($intentScore) {
            foreach ($prospects as $prospect) {
                $intentScore->recalculate($prospect);
            }
        });

        return back()->with('success', 'Scoring rules saved and all intent scores recalculated.');
    }
}
