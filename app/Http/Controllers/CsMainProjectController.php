<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CsMainProject;

class CsMainProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // TODO
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //get cs_main_project
        $cs_main_project = CsMainProject::find($id);
        //delete cs_main_project
        $cs_main_project->delete();
    }
}
