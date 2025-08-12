@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Flottes</h1>

    <div class="mb-4">
        <a href="{{ route('admin.fleets.create') }}" class="inline-block bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700">+ Créer une flotte</a>
    </div>

    <form method="GET" action="{{ route('admin.fleets.index') }}" class="mb-4 grid grid-cols-1 md:grid-cols-5 gap-2 items-end">
        <div>
            <label class="block text-sm">Recherche</label>
            <input type="text" name="q" value="{{ request('q') }}" class="border p-2 rounded w-full" placeholder="Nom de flotte">
        </div>
        <div>
            <label class="block text-sm">Commandant</label>
            <select name="commander_id" class="border p-2 rounded w-full">
                <option value="">-- Tous --</option>
                @foreach($commanders as $c)
                    <option value="{{ $c->id }}" @selected(request('commander_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm">Directive</label>
            <select name="directive_id" class="border p-2 rounded w-full">
                <option value="">-- Toutes --</option>
                @foreach($directives as $d)
                    <option value="{{ $d->id }}" @selected(request('directive_id') == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm">Statut</label>
            <select name="status" class="border p-2 rounded w-full">
                <option value="">-- Tous --</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button class="px-3 py-2 border rounded w-full">Filtrer</button>
        </div>
    </form>

    <div class="overflow-x-auto bg-white border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-left p-2">Nom</th>
                    <th class="text-left p-2">Commandant</th>
                    <th class="text-left p-2">Directive</th>
                    <th class="text-left p-2">Composition</th>
                    <th class="text-right p-2">Cargo</th>
                    <th class="text-left p-2">Position</th>
                    <th class="text-left p-2">Statut</th>
                    <th class="text-left p-2">MAJ</th>
                    <th class="text-left p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fleets as $fleet)
                    <tr class="border-t {{ $fleet->scheduled_for_deletion ? 'bg-red-50' : '' }}">
                        <td class="p-2">{{ $fleet->name }}</td>
                        <td class="p-2">{{ optional($fleet->commander)->name ?? '—' }}</td>
                        <td class="p-2">{{ optional($fleet->directive)->name ?? '—' }}</td>
                        <td class="p-2">
                            @php
                                $comp = $fleet->composition ?? [];
                                $total = 0;
                                foreach ($comp as $row) { $total += (int) ($row['operational'] + $row['damaged']); }
                            @endphp
                            <div class="font-semibold">{{ $total ?: ($fleet->ships_count ?? 0) }} vaisseaux</div>
                            @if(!empty($comp))
                                <ul class="mt-1 space-y-0.5 text-xs text-gray-700">
                                    @php $shown = 0; @endphp
                                    @foreach($comp as $row)
                                        @break($shown >= 5)
                                        <li>
                                            {{ optional($row['design'])->name ?? 'Design' }}:
                                            {{ $row['operational'] }} op,
                                            {{ $row['damaged'] }} dmg
                                            @if(($row['destroyed'] ?? 0) > 0)
                                                <span class="text-red-600">({{ $row['destroyed'] }} détruits)</span>
                                            @endif
                                        </li>
                                        @php $shown++; @endphp
                                    @endforeach
                                    @if(count($comp) > 5)
                                        <li>…</li>
                                    @endif
                                </ul>
                            @endif
                        </td>
                        <td class="p-2 text-right">{{ $fleet->cargo_count }}</td>
                        <td class="p-2">
                            @php
                                $pos = [];
                                if ($fleet->currentSystem && $fleet->currentSystem->name) { $pos[] = 'I: '.$fleet->currentSystem->name; }
                                if ($fleet->destinationSystem && $fleet->destinationSystem->name) { $pos[] = 'D: '.$fleet->destinationSystem->name; }
                                if (!empty($pos)) { echo implode(' → ', $pos); }
                                else { echo $fleet->position_x !== null && $fleet->position_y !== null ? $fleet->position_x.', '.$fleet->position_y : '—'; }
                            @endphp
                        </td>
                        <td class="p-2">
                            <div>{{ $fleet->status ?? '—' }}</div>
                            @if($fleet->scheduled_for_deletion)
                                <div class="text-red-600 text-xs mt-1">
                                    Suppression planifiée (tour {{ $fleet->scheduled_deletion_turn ?? '—' }})
                                </div>
                            @endif
                        </td>
                        <td class="p-2">{{ optional($fleet->updated_at)->format('Y-m-d H:i') }}</td>
                        <td class="p-2 space-x-2">
                            <a href="{{ route('admin.fleets.edit', $fleet) }}" class="text-blue-600 hover:underline">Modifier</a>
                            @if($fleet->scheduled_for_deletion)
                                <form action="{{ route('admin.fleets.cancel-deletion', $fleet) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-amber-700 hover:underline">Annuler</button>
                                </form>
                            @else
                                <form action="{{ route('admin.fleets.destroy', $fleet) }}" method="POST" class="inline" onsubmit="return confirm('Planifier la suppression de cette flotte au prochain tour ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-4 text-center text-gray-600">Aucune flotte trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $fleets->links() }}</div>
</div>
@endsection
