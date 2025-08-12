@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4 max-w-3xl">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">Race: {{ $race->name }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.races.edit', $race) }}" class="px-3 py-2 bg-yellow-600 text-white rounded">Éditer</a>
            <form method="POST" action="{{ route('admin.races.destroy', $race) }}" onsubmit="return confirm('Supprimer cette race ?');">
                @csrf
                @method('DELETE')
                <button class="px-3 py-2 bg-red-700 text-white rounded" type="submit">Supprimer</button>
            </form>
        </div>
    </div>

    <div class="bg-white border rounded p-4 space-y-2">
        <div><span class="font-semibold">Name:</span> {{ $race->name }}</div>
        <div class="space-y-2">
            <div><span class="font-semibold">Slug:</span> {{ $race->slug }}</div>
            <div><span class="font-semibold">Jouable:</span> {{ $race->is_playable ? 'Oui' : 'Non' }}</div>
            <div><span class="font-semibold">Description:</span>
                <p class="mt-1 whitespace-pre-line">{{ $race->description }}</p>
            </div>
            <div>
                <span class="font-semibold">Bonus/Malus:</span>
                @if($race->modifiers->count())
                    <ul class="list-disc list-inside mt-1">
                        @foreach($race->modifiers as $m)
                            <li><code>{{ $m->key }}</code>: <span class="font-mono">{{ number_format($m->value, 3, '.', ' ') }}</span></li>
                        @endforeach
                    </ul>
                @else
                    <span class="text-gray-600">Aucun</span>
                @endif
            </div>
        </div>
        @if($race->image_path)
            <div>
                <span class="font-semibold">Image:</span>
                <div class="mt-2">
                    <img src="{{ asset('storage/'.$race->image_path) }}" alt="{{ $race->name }}" class="max-h-48 border rounded">
                </div>
            </div>
        @endif
                <p class="mt-1 whitespace-pre-line">{{ $race->description }}</p>
            </div>
        @endif
    </div>

    <div class="mt-4">
        <a href="{{ route('admin.races.index') }}" class="px-3 py-2 border rounded">Retour</a>
    </div>
</div>
@endsection
