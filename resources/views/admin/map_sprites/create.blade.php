@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Nouvelle règle de sprite</h1>

    <form method="POST" action="{{ route('admin.map-sprites.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.map_sprites._form')
        <div class="mt-3 flex gap-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Créer</button>
            <a href="{{ route('admin.map-sprites.index') }}" class="px-4 py-2 border rounded">Annuler</a>
        </div>
    </form>
</div>
@endsection
