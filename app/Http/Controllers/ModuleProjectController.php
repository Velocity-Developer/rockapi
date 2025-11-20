<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ModuleProject;

class ModuleProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage   = (int) ($request->input('per_page', 20));
        $orderBy   = $request->input('order_by', 'created_at');
        $order     = $request->input('order', 'desc');
        $search    = $request->input('q');

        $query = ModuleProject::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('version', 'like', "%{$search}%")
                    ->orWhere('github_url', 'like', "%{$search}%")
                    ->orWhere('download_url', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->input('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by name
        if ($request->input('name')) {
            $query->where('name', 'like', "%{$request->input('name')}%");
        }

        // Filter by version
        if ($request->input('version')) {
            $query->where('version', 'like', "%{$request->input('version')}%");
        }

        // Filter by github_url
        if ($request->input('github_url')) {
            $query->where('github_url', 'like', "%{$request->input('github_url')}%");
        }

        // Filter by download_url
        if ($request->input('download_url')) {
            $query->where('download_url', 'like', "%{$request->input('download_url')}%");
        }

        // Simple whitelist for order_by
        if (!in_array($orderBy, ['name', 'version', 'type', 'github_url', 'download_url', 'created_at', 'updated_at'])) {
            $orderBy = 'created_at';
        }
        $order = strtolower($order) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($orderBy, $order);

        $moduleProjects = $query->paginate($perPage);
        return response()->json($moduleProjects);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|min:2|max:255',
            'version'      => 'required|string|max:50',
            'github_url'   => 'nullable|url|max:500',
            'download_url' => 'nullable|url|max:500',
            'type'         => 'required|in:theme,plugin,child_theme',
        ]);

        $moduleProject = ModuleProject::create($validated);
        return response()->json($moduleProject, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $moduleProject = ModuleProject::find($id);
        if (!$moduleProject) {
            return response()->json(['message' => 'Module Project tidak ditemukan'], 404);
        }
        return response()->json($moduleProject);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $moduleProject = ModuleProject::find($id);
        if (!$moduleProject) {
            return response()->json(['message' => 'Module Project tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'name'         => 'required|string|min:2|max:255',
            'version'      => 'required|string|max:50',
            'github_url'   => 'nullable|url|max:500',
            'download_url' => 'nullable|url|max:500',
            'type'         => 'required|in:theme,plugin,child_theme',
        ]);

        $moduleProject->update($validated);
        return response()->json($moduleProject);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $moduleProject = ModuleProject::find($id);
        if (!$moduleProject) {
            return response()->json(['message' => 'Module Project tidak ditemukan'], 404);
        }

        $moduleProject->delete();
        return response()->json(['message' => 'Module Project berhasil dihapus']);
    }
}