<?php

namespace App\Http\Controllers;

use App\Services\EnrollmentManager;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function requestJoin(Request $request, EnrollmentManager $manager)
    {
        $payload = $request->only(['commander_name', 'race_id', 'description']);
        try {
            $manager->requestJoin($request->user(), $payload);
            return back()->with('status', "Demande d'inscription enregistrée. Elle sera traitée lors de la résolution du prochain tour.");
        } catch (\Throwable $e) {
            return back()->withErrors(['enrollment' => $e->getMessage()]);
        }
    }

    public function requestLeave(Request $request, EnrollmentManager $manager)
    {
        $payload = $request->only(['note']);
        try {
            $manager->requestLeave($request->user(), $payload);
            return back()->with('status', 'Demande de départ enregistrée. Elle sera traitée lors de la résolution du prochain tour.');
        } catch (\Throwable $e) {
            return back()->withErrors(['enrollment' => $e->getMessage()]);
        }
    }
}
