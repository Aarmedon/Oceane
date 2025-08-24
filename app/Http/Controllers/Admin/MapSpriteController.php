<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MapSprite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\SpriteResolver;

class MapSpriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MapSprite::query();

        if ($scope = $request->get('scope')) {
            $query->where('scope', $scope);
        }
        if ($cat = $request->get('owner_category')) {
            $query->where('owner_category', $cat);
        }
        if (strlen((string) $request->get('active'))) {
            $active = (int) $request->get('active') === 1;
            $query->where('is_active', $active);
        }
        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('image_path', 'like', "%{$search}%")
                  ->orWhere('scope', 'like', "%{$search}%")
                  ->orWhere('owner_category', 'like', "%{$search}%");
            });
        }

        $sprites = $query->orderBy('scope')->orderBy('priority')->paginate(20)->withQueryString();

        $categories = ['self', 'ally', 'neutral', 'unknown', 'any', 'enemy'];

        return view('admin.map_sprites.index', compact('sprites', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ['self', 'ally', 'neutral', 'unknown', 'any', 'enemy'];
        return view('admin.map_sprites.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'scope' => ['required', 'in:system,fleet'],
            'owner_category' => ['required', 'in:self,ally,neutral,unknown,any,enemy'],
            'size_min' => ['nullable', 'integer', 'min:0'],
            'size_max' => ['nullable', 'integer', 'min:0'],
            'priority' => ['required', 'integer', 'between:0,100000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // At least one of image or image_path
        if (!$request->hasFile('image') && empty($data['image_path'] ?? null)) {
            return back()->withErrors(['image' => 'Veuillez téléverser une image ou renseigner un chemin.'])->withInput();
        }

        // Scope-specific handling
        if ($data['scope'] === 'system') {
            $data['size_min'] = null;
            $data['size_max'] = null;
        } else {
            // Validate range
            if (!is_null($data['size_min'] ?? null) && !is_null($data['size_max'] ?? null) && $data['size_min'] > $data['size_max']) {
                return back()->withErrors(['size_min' => 'size_min doit être inférieur ou égal à size_max.'])->withInput();
            }
        }

        // Store upload if present
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('sprites', 'public');
            $data['image_path'] = $path;
        }

        // Normalize image_path input when provided manually
        if (!empty($data['image_path'] ?? null)) {
            $data['image_path'] = $this->normalizeImagePath($data['image_path']);
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        MapSprite::create([
            'scope' => $data['scope'],
            'owner_category' => $data['owner_category'],
            'size_min' => $data['size_min'] ?? null,
            'size_max' => $data['size_max'] ?? null,
            'priority' => (int) $data['priority'],
            'image_path' => $data['image_path'],
            'is_active' => $data['is_active'],
        ]);

        // Invalidate sprite cache so changes apply immediately
        SpriteResolver::clearGlobalCache();

        return redirect()->route('admin.map-sprites.index')->with('status', 'Sprite créé.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MapSprite $sprite)
    {
        $categories = ['self', 'ally', 'neutral', 'unknown', 'any', 'enemy'];
        return view('admin.map_sprites.edit', compact('sprite', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MapSprite $sprite)
    {
        $data = $request->validate([
            'scope' => ['required', 'in:system,fleet'],
            'owner_category' => ['required', 'in:self,ally,neutral,unknown,any,enemy'],
            'size_min' => ['nullable', 'integer', 'min:0'],
            'size_max' => ['nullable', 'integer', 'min:0'],
            'priority' => ['required', 'integer', 'between:0,100000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($data['scope'] === 'system') {
            $data['size_min'] = null;
            $data['size_max'] = null;
        } else {
            if (!is_null($data['size_min'] ?? null) && !is_null($data['size_max'] ?? null) && $data['size_min'] > $data['size_max']) {
                return back()->withErrors(['size_min' => 'size_min doit être inférieur ou égal à size_max.'])->withInput();
            }
        }

        // If new upload, store and replace path
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('sprites', 'public');
            $data['image_path'] = $path;
        } elseif (array_key_exists('image_path', $data) && $data['image_path'] === '') {
            // Empty string means keep current path; normalize to current
            $data['image_path'] = $sprite->image_path;
        } else {
            $data['image_path'] = $data['image_path'] ?? $sprite->image_path;
        }

        // Normalize image_path input when provided or retained
        if (!empty($data['image_path'] ?? null)) {
            $data['image_path'] = $this->normalizeImagePath($data['image_path']);
        }

        $sprite->update([
            'scope' => $data['scope'],
            'owner_category' => $data['owner_category'],
            'size_min' => $data['size_min'] ?? null,
            'size_max' => $data['size_max'] ?? null,
            'priority' => (int) $data['priority'],
            'image_path' => $data['image_path'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        // Invalidate sprite cache so changes apply immediately
        SpriteResolver::clearGlobalCache();

        return redirect()->route('admin.map-sprites.index')->with('status', 'Sprite mis à jour.');
    }

    /**
     * Toggle the active flag quickly from the index.
     */
    public function toggle(MapSprite $sprite)
    {
        $sprite->is_active = !$sprite->is_active;
        $sprite->save();

        // Invalidate sprite cache so changes apply immediately
        SpriteResolver::clearGlobalCache();
        return back()->with('status', 'État du sprite mis à jour.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MapSprite $sprite)
    {
        $sprite->delete();

        // Invalidate sprite cache so changes apply immediately
        SpriteResolver::clearGlobalCache();
        return redirect()->route('admin.map-sprites.index')->with('status', 'Sprite supprimé.');
    }

    /**
     * Normalize an image path to be relative to the public disk (e.g., 'sprites/...').
     */
    private function normalizeImagePath(?string $path): ?string
    {
        if ($path === null) return null;
        $path = trim($path);
        if ($path === '') return '';
        if (preg_match('#^https?://#', $path)) return $path; // absolute URL untouched
        $p = ltrim($path, '/');
        if (str_starts_with($p, 'storage/')) {
            $p = substr($p, strlen('storage/'));
        }
        if (str_starts_with($p, 'app/public/')) {
            $p = substr($p, strlen('app/public/'));
        }
        if (str_starts_with($p, 'public/')) {
            $p = substr($p, strlen('public/'));
        }
        return $p;
    }
}
