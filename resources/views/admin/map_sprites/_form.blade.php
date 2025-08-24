@php
    $isEdit = isset($sprite);
    $scope = old('scope', $sprite->scope ?? 'system');
    $owner = old('owner_category', $sprite->owner_category ?? 'any');
    $sizeMin = old('size_min', $sprite->size_min ?? null);
    $sizeMax = old('size_max', $sprite->size_max ?? null);
    $priority = old('priority', $sprite->priority ?? 100);
    $imagePath = old('image_path', $sprite->image_path ?? '');
    $isActive = old('is_active', isset($sprite) ? (int) $sprite->is_active : 1);
@endphp

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

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-4 border rounded">
    <div>
        <label class="block text-sm mb-1">Portée (scope)</label>
        <select name="scope" class="border p-2 rounded w-full" required>
            <option value="system" @selected($scope === 'system')>Système</option>
            <option value="fleet" @selected($scope === 'fleet')>Flotte</option>
        </select>
        <p class="text-xs text-gray-600 mt-1">Pour les systèmes, <code>size_min</code> et <code>size_max</code> sont ignorés.</p>
    </div>

    <div>
        <label class="block text-sm mb-1">Catégorie d'appartenance</label>
        <select name="owner_category" class="border p-2 rounded w-full" required>
            @foreach(($categories ?? ['self','ally','neutral','unknown','any','enemy']) as $c)
                <option value="{{ $c }}" @selected($owner === $c)>{{ $c }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm mb-1">Taille min (flottes)</label>
        <input type="number" name="size_min" value="{{ $sizeMin }}" class="border p-2 rounded w-full" min="0" placeholder="ex: 11">
    </div>

    <div>
        <label class="block text-sm mb-1">Taille max (flottes)</label>
        <input type="number" name="size_max" value="{{ $sizeMax }}" class="border p-2 rounded w-full" min="0" placeholder="ex: 50">
    </div>

    <div>
        <label class="block text-sm mb-1">Priorité (plus petit = plus prioritaire)</label>
        <input type="number" name="priority" value="{{ $priority }}" class="border p-2 rounded w-full" min="0" max="100000" required>
    </div>

    <div>
        <label class="block text-sm mb-1">Actif</label>
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" {{ (int)$isActive === 1 ? 'checked' : '' }}>
            <span>Activer la règle</span>
        </label>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm mb-1">Téléverser une image (png/jpg/gif)</label>
        <input type="file" name="image" accept="image/*" class="block">
        <p class="text-xs text-gray-600 mt-1">Optionnel si vous fournissez un chemin ci-dessous.</p>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm mb-1">Chemin de l'image (si déjà présente)</label>
        <input type="text" name="image_path" value="{{ $imagePath }}" class="border p-2 rounded w-full" placeholder="sprites/... ou /storage/sprites/... ou URL absolue">
        @if(!empty($imagePath))
            @php
                $path = $imagePath;
                $url = $path;
                if (!preg_match('#^https?://#', (string) $path)) {
                    $clean = ltrim((string) $path, '/');
                    if (str_starts_with($clean, 'storage/')) {
                        $url = '/'.$clean;
                    } else {
                        // Build relative URL so browser keeps current host:port
                        $url = '/storage/'.$clean;
                    }
                }
            @endphp
            <div class="mt-2 flex items-center gap-2">
                <span class="text-sm text-gray-600">Aperçu actuel:</span>
                <img src="{{ $url }}" alt="aperçu sprite" class="w-12 h-12 object-contain inline-block" />
                <a href="{{ $url }}" target="_blank" class="text-blue-600 text-sm hover:underline">ouvrir</a>
            </div>
        @endif
    </div>
</div>
