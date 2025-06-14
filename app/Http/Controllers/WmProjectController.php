<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WmProject;

class WmProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'catatan' => 'nullable|text',
            'date_mulai' => 'required|date',
            'date_selesai' => 'nullable|date',
            'status_multi' => 'required|string',
            'id_cs_main_project' => 'required|integer',
            'qc' => 'nullable|array',
            'webmaster' => 'required|string',
        ]);

        //create wm_project
        $wm_project = WmProject::create([
            'catatan' => $request->catatan,
            'date_mulai' => $request->date_mulai,
            'date_selesai' => $request->date_selesai,
            'status_multi' => $request->status_multi,
            'id' => $request->id_cs_main_project,
            'qc' => $request->qc,
            'webmaster' => $request->webmaster
        ]);
        return response()->json($wm_project);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //get wm_project
        $wm_project = WmProject::find($id);
        return response()->json($wm_project);
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
        //hapus wm_project
        WmProject::where('id', $id)->delete();
    }
}
