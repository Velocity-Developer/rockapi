<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Webhost;

class WebhostController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //get by id, with paket,csMainProjects
        $webhost = Webhost::with('paket', 'csMainProjects')->find($id);

        return response()->json($webhost);
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
        //get by id
        $webhost = Webhost::find($id);
        $webhost->delete();
        return response()->json($webhost);
    }

    //search by keyword
    public function search(string $keyword)
    {
        //get nama_web by keyword, ambil kolom nama_web dan id_webhost
        $webhosts = Webhost::where('nama_web', 'like', '%' . $keyword . '%')
            ->select('nama_web', 'id_webhost')
            ->get();

        // $webhosts = Webhost::where('nama_web', 'like', '%' . $keyword . '%')->get();
        return response()->json($webhosts);
    }
}
