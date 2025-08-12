@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4 max-w-3xl">
    <h1 class="text-2xl font-bold mb-4">Éditer la race: {{ $race->name }}</h1>

    @if ($errors->any())
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.races.update', $race) }}" class="space-y-4" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-medium">Name</label>
            <input name="name" type="text" value="{{ old('name', $race->name) }}" class="border p-2 rounded w-full" required>
        </div>

        <div class="text-sm text-gray-600">
            Le slug est généré automatiquement à partir du nom. Il sera mis à jour si vous changez le nom.
        </div>

        <div>
            <label class="block font-medium">Description</label>
            <textarea name="description" class="border p-2 rounded w-full" rows="4">{{ old('description', $race->description) }}</textarea>
        </div>

        <div>
            <label class="inline-flex items-center gap-2">
                <input name="is_playable" type="checkbox" value="1" {{ old('is_playable', $race->is_playable) ? 'checked' : '' }}>
                <span>Jouable</span>
            </label>
        </div>

        <div>
            <label class="block font-medium mb-2">Bonus/Malus de la race</label>
            <p class="text-sm text-gray-600 mb-2">Ajoutez des paires <em>clé</em> / <em>valeur</em> (valeur numérique, ex: 1.1 pour +10%, -0.2 pour -20%).</p>
            <div id="modifiers-list" class="space-y-2">
                <!-- rows inserted by JS -->
            </div>
            <button type="button" id="add-modifier" class="mt-2 px-3 py-1 border rounded">+ Ajouter un modificateur</button>
        </div>

        <div>
            <label class="block font-medium">Image de la race (max 2 Mo)</label>
            <input name="image" type="file" accept="image/*" class="border p-2 rounded w-full">
            @if($race->image_path)
                <p class="text-sm text-gray-500 mt-1">Image actuelle:</p>
                <img src="{{ asset('storage/'.$race->image_path) }}" alt="{{ $race->name }}" class="mt-2 max-h-48 border rounded">
            @endif
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.races.index') }}" class="px-3 py-2 border rounded">Annuler</a>
            <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Enregistrer</button>
        </div>
    </form>
</div>
@endsection
@push('scripts')
<script>
    (function(){
        const list = document.getElementById('modifiers-list');
        const addBtn = document.getElementById('add-modifier');
        let idx = 0;

        function addRow(key = '', value = '') {
            const row = document.createElement('div');
            row.className = 'flex gap-2 items-center';
            row.innerHTML = `
                <input name="modifiers[${idx}][key]" type="text" placeholder="clé (ex: production_multiplier)" value="${key}"
                       class="border p-2 rounded w-1/2" />
                <input name="modifiers[${idx}][value]" type="number" step="0.001" placeholder="valeur (ex: 1.100)" value="${value}"
                       class="border p-2 rounded w-1/3" />
                <button type="button" class="px-2 py-1 border rounded text-red-700 remove-row">Suppr</button>
            `;
            list.appendChild(row);
            row.querySelector('.remove-row').addEventListener('click', () => row.remove());
            idx++;
        }

        addBtn.addEventListener('click', () => addRow());

        // Preload existing modifiers from server, or old() if validation failed
        const existing = @json(old('modifiers', $race->modifiers->map(fn($m) => ['key' => $m->key, 'value' => $m->value])));
        if (Array.isArray(existing) && existing.length) {
            existing.forEach(m => addRow(m.key ?? '', m.value ?? ''));
        } else {
            addRow();
        }
    })();
    </script>
@endpush
