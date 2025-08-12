<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameEnrollment;
use App\Services\EnrollmentManager;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index(Request $request)
    {
        $query = GameEnrollment::query()->with(['user', 'commander'])
            ->when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->when($request->get('type'), fn($q, $type) => $q->where('type', $type));

        $enrollments = $query->orderBy('created_at')->paginate(20)->withQueryString();

        return view('admin.enrollments.index', compact('enrollments'));
    }

    public function block(Request $request, GameEnrollment $enrollment, EnrollmentManager $manager)
    {
        $data = $request->validate([
            'note' => ['nullable','string','max:1000'],
        ]);

        $manager->blockRequest($enrollment, $request->user(), $data['note'] ?? null);

        return redirect()->route('admin.enrollments.index')
            ->with('success', "Demande #{$enrollment->id} bloquée.");
    }

    public function unblock(Request $request, GameEnrollment $enrollment, EnrollmentManager $manager)
    {
        $data = $request->validate([
            'note' => ['nullable','string','max:1000'],
        ]);

        $manager->unblockRequest($enrollment, $request->user(), $data['note'] ?? null);

        return redirect()->route('admin.enrollments.index')
            ->with('success', "Demande #{$enrollment->id} débloquée (retour à 'pending').");
    }
}
