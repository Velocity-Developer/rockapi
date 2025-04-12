<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Term;

class TermsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $taxonomy = $request->input('taxonomy');
        $terms = Term::where('taxonomy', $taxonomy)
            ->orderBy('name', 'desc')
            ->paginate(20);

        return response()->json($terms);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'taxonomy' => 'required|string',
        ]);

        $term = Term::create([
            'name'          => $request->name,
            'description'   => $request->description,
            'taxonomy'      => $request->taxonomy ?? 'category',
        ]);

        return response()->json($term);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $term = Term::find($id);
        return response()->json($term);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $term = Term::find($id);
        $term->name = $request->name;
        $term->description = $request->description;
        $term->save();

        return response()->json($term);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $term = Term::find($id);
        $term->delete();
    }
}
