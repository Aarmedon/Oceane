@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Modifier la flotte: {{ $fleet->name }}</h1>

    @if (session('success'))
        <div class="mb-4 p-3 border border-green-300 bg-green-50 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 border border-red-300 bg-red-50 text-red-700 rounded">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 border border-red-300 bg-red-50 text-red-700 rounded">
            <strong>Veuillez corriger les erreurs suivantes :</strong>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.fleets.update', $fleet) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-4 border rounded">
        @csrf
        @method('PATCH')

        <div>
            <label class="block text-sm mb-1">Nom</label>
            <input type="text" name="name" value="{{ old('name', $fleet->name) }}" class="border p-2 rounded w-full" required>
        </div>

        <div>
            <label class="block text-sm mb-1">Commandant</label>
            <select name="commander_id" class="border p-2 rounded w-full" required>
                @foreach($commanders as $c)
                    <option value="{{ $c->id }}" @selected(old('commander_id', $fleet->commander_id) == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm mb-1">Système actuel</label>
            <select name="current_system_id" class="border p-2 rounded w-full" required>
                @foreach($systems as $s)
                    <option value="{{ $s->id }}" @selected(old('current_system_id', $fleet->current_system_id) == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm mb-1">Directive</label>
            <select name="directive_id" class="border p-2 rounded w-full" required>
                @foreach($directives as $d)
                    <option value="{{ $d->id }}" @selected(old('directive_id', $fleet->directive_id) == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm mb-1">Statut</label>
            <select name="status" class="border p-2 rounded w-full" required>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(old('status', $fleet->status) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2 flex gap-2 mt-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Enregistrer</button>
            <a href="{{ route('admin.fleets.index') }}" class="px-4 py-2 border rounded">Annuler</a>
        </div>
    </form>

    <div class="mt-6 bg-white p-4 border rounded">
        <h2 class="text-lg font-semibold mb-2">Ajouter des vaisseaux</h2>
        <form action="{{ route('admin.fleets.composition.add', $fleet) }}" method="POST" class="mb-3 flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="block text-sm mb-1">Design</label>
                <select name="ship_design_id" class="border p-2 rounded min-w-[220px]" required>
                    @foreach($shipDesigns as $sd)
                        <option value="{{ $sd->id }}">{{ $sd->name }} @if($sd->is_stackable) (stack) @endif</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">Quantité</label>
                <input type="number" name="count" value="1" min="1" class="border p-2 rounded w-24" required>
            </div>
            <div class="shrink-0 mt-6">
                <button type="submit" class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white border border-blue-600 min-w-[120px]" title="Ajouter les vaisseaux">Valider</button>
            </div>
            <div>
                <label class="block text-sm mb-1">Préfixe nom (optionnel)</label>
                <input type="text" name="base_name" class="border p-2 rounded w-56" placeholder="ex: Alpha">
            </div>
        </form>
    </div>

    @if(!empty($composition))
        <div class="mt-6 bg-white p-4 border rounded">
            <h2 class="text-lg font-semibold mb-2">Composition (stacks)</h2>
            @php
                $totalOp = 0; $totalDmg = 0; $totalDestroyed = 0;
                foreach ($composition as $row) {
                    $totalOp += (int) $row['operational'];
                    $totalDmg += (int) $row['damaged'];
                    $totalDestroyed += (int) ($row['destroyed'] ?? 0);
                }
            @endphp
            <div class="text-sm text-gray-700 mb-2">
                Total: <strong>{{ $totalOp + $totalDmg }}</strong> ({{ $totalOp }} op, {{ $totalDmg }} dmg)
                @if($totalDestroyed > 0)
                    — <span class="text-red-600">{{ $totalDestroyed }} détruits</span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left p-2">Design</th>
                            <th class="text-right p-2">Opérationnels</th>
                            <th class="text-right p-2">Endommagés</th>
                            <th class="text-right p-2">Détruits</th>
                            <th class="text-left p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($composition as $row)
                            <tr class="border-t">
                                <td class="p-2">{{ $row['design']->name ?? '—' }}</td>
                                <td class="p-2 text-right">{{ (int) $row['operational'] }}</td>
                                <td class="p-2 text-right">{{ (int) $row['damaged'] }}</td>
                                <td class="p-2 text-right">{{ (int) ($row['destroyed'] ?? 0) }}</td>
                                <td class="p-2">
                                    @php $available = (int)$row['operational'] + (int)$row['damaged']; @endphp
                                    @if($available > 0)
                                        <div class="flex gap-2 flex-wrap">
                                            <form action="{{ route('admin.fleets.composition.remove', $fleet) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="ship_design_id" value="{{ $row['design']->id ?? 0 }}">
                                                <input type="hidden" name="count" value="1">
                                                <button type="submit" class="px-2 py-1 text-xs border rounded hover:bg-gray-50">Retirer 1</button>
                                            </form>
                                            <form action="{{ route('admin.fleets.composition.remove', $fleet) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="ship_design_id" value="{{ $row['design']->id ?? 0 }}">
                                                <input type="hidden" name="count" value="5">
                                                <button type="submit" class="px-2 py-1 text-xs border rounded hover:bg-gray-50">Retirer 5</button>
                                            </form>
                                            <form action="{{ route('admin.fleets.composition.remove', $fleet) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="ship_design_id" value="{{ $row['design']->id ?? 0 }}">
                                                <input type="hidden" name="count" value="{{ $available }}">
                                                <button type="submit" class="px-2 py-1 text-xs border rounded hover:bg-red-50 text-red-700">Retirer tout</button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($fleet->scheduled_for_deletion)
        <div class="mt-4 p-3 border border-amber-300 bg-amber-50 text-amber-800 rounded">
            Cette flotte est planifiée pour suppression au tour {{ $fleet->scheduled_deletion_turn }}.
            <form action="{{ route('admin.fleets.cancel-deletion', $fleet) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="ml-2 underline">Annuler la suppression planifiée</button>
            </form>
        </div>
    @endif
</div>
@endsection
