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
            'id_karyawan'           => 'required|integer',
            'id_cs_main_project'    => 'required|integer',
            'webmaster'             => 'required|string',
            'date_mulai'            => 'required|date',
            'date_selesai'          => 'nullable|date',
            'qc'                    => 'nullable|array',
            'catatan'               => 'nullable|string',
            'status_multi'          => 'required|string|in:pending,selesai',
        ]);

        //create wm_project
        $wm_project = WmProject::create([
            'id_karyawan'   => $request->id_karyawan,
            'id'            => $request->id_cs_main_project,
            'webmaster'     => $request->webmaster,
            'date_mulai'    => $request->date_mulai,
            'date_selesai'  => $request->date_selesai,
            'qc'            => $request->qc,
            'catatan'       => $request->catatan,
            'status_multi'  => $request->status_multi,
            'start'         => now(),
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
        $request->validate([
            'id_karyawan'           => 'required|integer',
            'id_cs_main_project'    => 'required|integer',
            'webmaster'             => 'required|string',
            'date_mulai'            => 'required|date',
            'date_selesai'          => 'nullable|date',
            'qc'                    => 'nullable|array',
            'catatan'               => 'nullable|string',
            'status_multi'          => 'required|string|in:pending,selesai',
        ]);

        //update wm_project
        $wm_project = WmProject::find($id);
        $wm_project->update([
            'id_karyawan'   => $request->id_karyawan,
            'id'            => $request->id_cs_main_project,
            'webmaster'     => $request->webmaster,
            'date_mulai'    => $request->date_mulai,
            'date_selesai'  => $request->date_selesai,
            'qc'            => $request->qc ? serialize($request->qc) : '',
            'catatan'       => $request->catatan,
            'status_multi'  => $request->status_multi,
        ]);

        return response()->json($wm_project);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //hapus wm_project
        WmProject::where('id_wm_project', $id)->delete();
    }
}
