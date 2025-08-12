@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Créer une flotte</h1>

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

    <form method="POST" action="{{ route('admin.fleets.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-4 border rounded">
        @csrf

        <div>
            <label class="block text-sm mb-1">Nom</label>
            <input type="text" name="name" value="{{ old('name') }}" class="border p-2 rounded w-full" required>
        </div>

        <div>
            <label class="block text-sm mb-1">Commandant</label>
            <select name="commander_id" class="border p-2 rounded w-full" required>
                <option value="">-- Choisir --</option>
                @foreach($commanders as $c)
                    <option value="{{ $c->id }}" @selected(old('commander_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm mb-1">Système actuel</label>
            <select name="current_system_id" class="border p-2 rounded w-full" required>
                <option value="">-- Choisir --</option>
                @foreach($systems as $s)
                    <option value="{{ $s->id }}" @selected(old('current_system_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm mb-1">Directive</label>
            <select name="directive_id" class="border p-2 rounded w-full" required>
                <option value="">-- Choisir --</option>
                @foreach($directives as $d)
                    <option value="{{ $d->id }}" @selected(old('directive_id') == $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm mb-1">Statut</label>
            <select name="status" class="border p-2 rounded w-full">
                <option value="">(défaut: Docked)</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(old('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2 flex gap-2 mt-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Créer</button>
            <a href="{{ route('admin.fleets.index') }}" class="px-4 py-2 border rounded">Annuler</a>
        </div>
    </form>
</div>
@endsection
