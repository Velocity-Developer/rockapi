<?php

namespace App\Http\Controllers;

use App\Models\ServerPackage;
use Illuminate\Http\Request;

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

        // order by name
        $order_by = $request->input('order_by', 'name');
        $order = $request->input('order', 'asc');
        $packages = $packages->orderBy($order_by, $order);

        // pagination
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
        // get by id
        $package = ServerPackage::with('server:id,name')->find($id);

        return response()->json($package);
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
        // get by id
        $package = ServerPackage::find($id);
        $package->delete();
    }
}
