@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Races</h1>

    @if (session('status'))
        <div class="bg-green-100 text-green-800 p-2 rounded mb-4">{{ session('status') }}</div>
    @endif

    <div class="flex items-center justify-between mb-4">
        <form method="GET" action="{{ route('admin.races.index') }}" class="flex gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Recherche name/slug" class="border p-2 rounded" />
            <select name="is_playable" class="border p-2 rounded">
                <option value="">Toutes</option>
                <option value="1" @selected(request('is_playable')==='1')>Jouables</option>
                <option value="0" @selected(request('is_playable')==='0')>Non jouables</option>
            </select>
            <button class="bg-blue-600 text-white px-3 py-2 rounded">Filtrer</button>
        </form>
        <a href="{{ route('admin.races.create') }}" class="bg-green-600 text-white px-3 py-2 rounded">Nouvelle race</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border">
            <thead>
                <tr class="bg-gray-100 text-left">
                    <th class="p-2 border">Name</th>
                    <th class="p-2 border">Slug</th>
                    <th class="p-2 border">Jouable</th>
                    <th class="p-2 border">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($races as $race)
                    <tr>
                        <td class="p-2 border">{{ $race->name }}</td>
                        <td class="p-2 border">{{ $race->slug }}</td>
                        <td class="p-2 border">{{ $race->is_playable ? 'Oui' : 'Non' }}</td>
                        <td class="p-2 border flex gap-2">
                            <a class="text-blue-600" href="{{ route('admin.races.show', $race) }}">Voir</a>
                            <a class="text-yellow-700" href="{{ route('admin.races.edit', $race) }}">Éditer</a>
                            <form method="POST" action="{{ route('admin.races.destroy', $race) }}" onsubmit="return confirm('Supprimer cette race ?');">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-700" type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center text-gray-500">Aucune race</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $races->links() }}</div>
</div>
@endsection
