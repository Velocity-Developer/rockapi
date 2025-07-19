<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServerPackage;

class ServerPackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $server_id = $request->server_id ?? '';
        $packages = ServerPackage::where('server_id', $server_id);
        $packages = $packages->with('server:id,name');

        //pagination
        $per_page = $request->input('per_page', 20);
        $packages = $packages->paginate($per_page);

        return response()->json($packages);
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
        //
    }
}
