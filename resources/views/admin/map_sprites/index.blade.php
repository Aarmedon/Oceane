@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Sprites de carte</h1>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.map-sprites.create') }}" class="inline-block bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700">+ Nouvelle règle</a>
    </div>

    <form method="GET" action="{{ route('admin.map-sprites.index') }}" class="mb-4 grid grid-cols-1 md:grid-cols-5 gap-2 items-end">
        <div>
            <label class="block text-sm">Recherche</label>
            <input type="text" name="q" value="{{ request('q') }}" class="border p-2 rounded w-full" placeholder="image_path, scope, owner">
        </div>
        <div>
            <label class="block text-sm">Scope</label>
            <select name="scope" class="border p-2 rounded w-full">
                <option value="">-- Tous --</option>
                <option value="system" @selected(request('scope')==='system')>system</option>
                <option value="fleet" @selected(request('scope')==='fleet')>fleet</option>
            </select>
        </div>
        <div>
            <label class="block text-sm">Appartenance</label>
            <select name="owner_category" class="border p-2 rounded w-full">
                <option value="">-- Toutes --</option>
                @foreach($categories as $c)
                    <option value="{{ $c }}" @selected(request('owner_category')===$c)>{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm">Actif</label>
            <select name="active" class="border p-2 rounded w-full">
                <option value="">-- Tous --</option>
                <option value="1" @selected(request('active')==='1')>Oui</option>
                <option value="0" @selected(request('active')==='0')>Non</option>
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
                    <th class="text-left p-2">Scope</th>
                    <th class="text-left p-2">Appartenance</th>
                    <th class="text-left p-2">Taille</th>
                    <th class="text-right p-2">Priorité</th>
                    <th class="text-left p-2">Image</th>
                    <th class="text-left p-2">Actif</th>
                    <th class="text-left p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sprites as $sprite)
                    @php
                        $preview = '';
                        if (!empty($sprite->image_path)) {
                            $path = (string) $sprite->image_path;
                            $preview = $path;
                            if (!preg_match('#^https?://#', $path)) {
                                $clean = ltrim($path, '/');
                                if (str_starts_with($clean, 'storage/')) {
                                    $preview = '/'.$clean;
                                } else {
                                    // Build relative URL so browser keeps current host:port
                                    $preview = '/storage/'.$clean;
                                }
                            }
                        }
                    @endphp
                    <tr class="border-t">
                        <td class="p-2">{{ $sprite->scope }}</td>
                        <td class="p-2">{{ $sprite->owner_category }}</td>
                        <td class="p-2">
                            @if($sprite->scope==='fleet')
                                {{ $sprite->size_min ?? '—' }} – {{ $sprite->size_max ?? '—' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="p-2 text-right">{{ $sprite->priority }}</td>
                        <td class="p-2">
                            @if($preview)
                                <img src="{{ $preview }}" alt="img" class="w-10 h-10 object-contain"/>
                            @else
                                —
                            @endif
                        </td>
                        <td class="p-2">{{ $sprite->is_active ? 'Oui' : 'Non' }}</td>
                        <td class="p-2 space-x-2">
                            <a href="{{ route('admin.map-sprites.edit', $sprite) }}" class="text-blue-600 hover:underline">Modifier</a>
                            <form action="{{ route('admin.map-sprites.toggle', $sprite) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-amber-700 hover:underline">{{ $sprite->is_active ? 'Désactiver' : 'Activer' }}</button>
                            </form>
                            <form action="{{ route('admin.map-sprites.destroy', $sprite) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer cette règle ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-gray-600">Aucune règle.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $sprites->links() }}</div>
</div>
@endsection
