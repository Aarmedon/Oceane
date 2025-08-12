<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $gmEmails = array_map('strtolower', config('oceane.admin.gamemasters_emails', []));
        $isGmEmail = $user ? in_array(strtolower((string) $user->email), $gmEmails, true) : false;
        $canAdmin = $user ? (($user->is_admin ?? false) || $isGmEmail) : false;

        $player = null;
        if ($user) {
            try {
                $hasCommander = (bool) $user->commanders()->exists();
                $pendingJoin = (bool) \App\Models\GameEnrollment::where('user_id', $user->id)
                    ->where('type', 'join')->where('status', 'pending')->exists();
                $pendingLeave = (bool) \App\Models\GameEnrollment::where('user_id', $user->id)
                    ->where('type', 'leave')->where('status', 'pending')->exists();
                $freezeActive = app(\App\Services\EnrollmentManager::class)->isFreezeWindowActive();
                $player = [
                    'hasCommander' => $hasCommander,
                    'pendingJoin' => $pendingJoin,
                    'pendingLeave' => $pendingLeave,
                    'registrationOpen' => (bool) config('oceane.game.registration_open', true),
                    'freezeWindowActive' => $freezeActive,
                ];
            } catch (\Throwable $e) {
                $player = null;
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'can' => [
                'admin' => $canAdmin,
            ],
            'player' => $player,
            'flash' => [
                'status' => session('status'),
            ],
        ];
    }
}
