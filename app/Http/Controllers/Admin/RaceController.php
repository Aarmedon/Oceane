<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Race::query();

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if (strlen((string) $request->get('is_playable'))) {
            $isPlayable = (int) $request->get('is_playable') === 1;
            $query->where('is_playable', $isPlayable);
        }

        $races = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.races.index', compact('races'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.races.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:races,name'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_playable' => ['sometimes', 'boolean'],
            'modifiers' => ['sometimes', 'array'],
            'modifiers.*.key' => ['required_with:modifiers', 'string', 'max:100'],
            'modifiers.*.value' => ['required_with:modifiers', 'numeric', 'between:-1000,1000'],
        ]);

        // Generate unique slug from name
        $baseSlug = Str::slug($data['name']);
        $slug = $this->makeUniqueSlug($baseSlug);

        $payload = [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'is_playable' => (bool) ($data['is_playable'] ?? false),
        ];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('races', 'public');
            $payload['image_path'] = $path; // stored relative path on 'public' disk
        }

        $race = Race::create($payload);

        // Save modifiers if provided
        $mods = collect($request->input('modifiers', []))
            ->filter(fn($m) => isset($m['key']) && $m['key'] !== '' && isset($m['value']) && $m['value'] !== '')
            ->map(fn($m) => ['key' => trim($m['key']), 'value' => (float) $m['value']])
            ->values()
            ->all();
        if (!empty($mods)) {
            $race->modifiers()->createMany($mods);
        }

        return redirect()->route('admin.races.index')->with('status', 'Race created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Race $race)
    {
        return view('admin.races.show', compact('race'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Race $race)
    {
        return view('admin.races.edit', compact('race'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Race $race)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('races', 'name')->ignore($race->id)],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_playable' => ['sometimes', 'boolean'],
            'modifiers' => ['sometimes', 'array'],
            'modifiers.*.key' => ['required_with:modifiers', 'string', 'max:100'],
            'modifiers.*.value' => ['required_with:modifiers', 'numeric', 'between:-1000,1000'],
        ]);

        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_playable' => (bool) ($data['is_playable'] ?? false),
        ];

        // If name changed, regenerate a unique slug
        if ($race->name !== $data['name']) {
            $baseSlug = Str::slug($data['name']);
            $payload['slug'] = $this->makeUniqueSlug($baseSlug, $race->id);
        }

        if ($request->hasFile('image')) {
            // delete old image if exists
            if ($race->image_path && Storage::disk('public')->exists($race->image_path)) {
                Storage::disk('public')->delete($race->image_path);
            }
            $path = $request->file('image')->store('races', 'public');
            $payload['image_path'] = $path;
        }

        $race->update($payload);

        // Sync modifiers
        $incoming = collect($request->input('modifiers', []))
            ->filter(fn($m) => isset($m['key']) && $m['key'] !== '' && isset($m['value']) && $m['value'] !== '')
            ->mapWithKeys(function ($m) {
                $key = trim($m['key']);
                return [$key => ['key' => $key, 'value' => (float) $m['value']]];
            });

        // Update existing and delete removed
        $existing = $race->modifiers()->get();
        foreach ($existing as $mod) {
            if ($incoming->has($mod->key)) {
                $dataMod = $incoming->get($mod->key);
                $mod->update(['value' => $dataMod['value']]);
                $incoming->forget($mod->key);
            } else {
                $mod->delete();
            }
        }
        // Create new ones
        if ($incoming->isNotEmpty()) {
            $race->modifiers()->createMany($incoming->values()->all());
        }

        return redirect()->route('admin.races.index')->with('status', 'Race updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Race $race)
    {
        $race->delete();

        return redirect()->route('admin.races.index')->with('status', 'Race deleted successfully.');
    }

    /**
     * Create a unique slug, appending -2, -3, ... if needed.
     */
    protected function makeUniqueSlug(string $baseSlug, ?int $ignoreId = null): string
    {
        $base = $baseSlug !== '' ? $baseSlug : 'race';
        $slug = $base;
        $i = 2;
        while (
            Race::where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
