<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmartLink\InstagramAudit;
use App\Services\SmartLink\InstagramProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * The two endpoints the local worker PC talks to.
 *
 * Instagram refuses this server's shared-hosting IP whatever cookie it sends, so audits
 * are queued here as `pending` rows and a PC on an ordinary home or office connection
 * does the fetching. That PC has no public address of its own, so it always calls in -
 * asking for work, then posting the result back - and nothing needs to reach it.
 *
 * Both endpoints are guarded by a shared secret rather than a login, because the worker
 * is a script rather than a user. They are deliberately the only two things it can do:
 * read the queue, and file a result against a row that is already in it.
 */
class InstagramWorkerController extends Controller
{
    public function __construct(private InstagramProfileService $instagram)
    {
    }

    /** Oldest first, so a queue that builds up is worked through in order. */
    public function pending(Request $request): JsonResponse
    {
        if ($denied = $this->denied($request)) {
            return $denied;
        }

        $jobs = InstagramAudit::where('status', 'pending')
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'username']);

        return response()->json(['jobs' => $jobs]);
    }

    /**
     * Files a fetched profile against a queued row. The worker sends what Instagram gave
     * it and this end does the scoring, so the audit rules live in one place and the
     * worker never has to be redeployed when they change.
     */
    public function result(Request $request): JsonResponse
    {
        if ($denied = $this->denied($request)) {
            return $denied;
        }

        $data = $request->validate([
            'id' => ['required', 'integer'],
            'user' => ['nullable', 'array'],
            'profile_pic' => ['nullable', 'string'],
            'error' => ['nullable', 'string', 'max:500'],
        ]);

        $audit = InstagramAudit::where('status', 'pending')->find($data['id']);

        if (! $audit) {
            return response()->json(['message' => 'No pending audit with that id.'], 404);
        }

        if (empty($data['user'])) {
            $audit->update([
                'status' => 'failed',
                'error_message' => $data['error'] ?: 'The worker could not read this profile.',
            ]);

            return response()->json(['status' => 'failed']);
        }

        $audit->update($this->instagram->rowFromProfile(
            $audit->username,
            $data['user'],
            $this->picture($data['profile_pic'] ?? null),
        ));

        return response()->json(['status' => $audit->status]);
    }

    /**
     * The picture arrives as a data URI the worker already downloaded. It is stored as-is
     * but only after the shape is checked, since it is rendered straight into an <img>.
     */
    private function picture(?string $picture): ?string
    {
        return $picture && preg_match('#^data:image/(png|jpeg|jpg|webp);base64,[A-Za-z0-9+/=]+$#', $picture)
            ? $picture
            : null;
    }

    private function denied(Request $request): ?JsonResponse
    {
        $token = (string) config('services.instagram.worker_token');

        if ($token === '' || ! hash_equals($token, (string) $request->header('X-Worker-Token'))) {
            return response()->json(['message' => 'Invalid worker token.'], 401);
        }

        return null;
    }
}
