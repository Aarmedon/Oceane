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

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'can' => [
                'admin' => $canAdmin,
            ],
        ];
    }
}
