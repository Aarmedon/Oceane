@extends('layouts.app')

@section('title', 'Inscriptions (MJ)')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Inscriptions – Modération MJ</h1>

    @if(session('success'))
        <div class="mb-4 rounded border border-green-600 bg-green-50 p-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.enrollments.index') }}" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
        <div>
            <label class="block text-sm">Type</label>
            <select name="type" class="border p-2 rounded w-full">
                <option value="">-- Tous --</option>
                <option value="join" @selected(request('type')==='join')>Join</option>
                <option value="leave" @selected(request('type')==='leave')>Leave</option>
            </select>
        </div>
        <div>
            <label class="block text-sm">Statut</label>
            <select name="status" class="border p-2 rounded w-full">
                <option value="">-- Tous --</option>
                <option value="pending" @selected(request('status')==='pending')>Pending</option>
                <option value="processed" @selected(request('status')==='processed')>Processed</option>
                <option value="blocked" @selected(request('status')==='blocked')>Blocked</option>
                <option value="rejected" @selected(request('status')==='rejected')>Rejected</option>
            </select>
        </div>
        <div></div>
        <div>
            <button class="px-3 py-2 border rounded w-full">Filtrer</button>
        </div>
    </form>

    <div class="overflow-x-auto bg-white border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 text-left">#</th>
                    <th class="p-2 text-left">Type</th>
                    <th class="p-2 text-left">Statut</th>
                    <th class="p-2 text-left">Utilisateur</th>
                    <th class="p-2 text-left">Commandant</th>
                    <th class="p-2 text-left">Créé</th>
                    <th class="p-2 text-left">Traité</th>
                    <th class="p-2 text-left">Tour</th>
                    <th class="p-2 text-left">Note</th>
                    <th class="p-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $enr)
                    <tr class="border-t">
                        <td class="p-2">{{ $enr->id }}</td>
                        <td class="p-2">{{ ucfirst($enr->type) }}</td>
                        <td class="p-2">{{ $enr->status }}</td>
                        <td class="p-2">{{ optional($enr->user)->name }} ({{ optional($enr->user)->email }})</td>
                        <td class="p-2">{{ optional($enr->commander)->name ?? '—' }}</td>
                        <td class="p-2">{{ optional($enr->created_at)->format('Y-m-d H:i') }}</td>
                        <td class="p-2">{{ optional($enr->processed_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="p-2">{{ $enr->processed_turn ?? '—' }}</td>
                        <td class="p-2 max-w-xs">{{ $enr->note }}</td>
                        <td class="p-2">
                            @if($enr->status === 'pending')
                                <form method="POST" action="{{ route('admin.enrollments.block', $enr) }}" class="flex items-center gap-1">
                                    @csrf
                                    <input type="text" name="note" class="border p-1 rounded text-xs" placeholder="Motif (optionnel)">
                                    <button type="submit" class="text-red-700 hover:underline">Bloquer</button>
                                </form>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="p-4 text-center text-gray-600">Aucune demande.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $enrollments->links() }}</div>
</div>
@endsection
