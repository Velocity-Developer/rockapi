<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServerPackage;
use App\Models\ServerUser;

class ServerUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $server_id = $request->server_id ?? '';
        $users = ServerUser::where('server_id', $server_id);
        $users = $users->with('server:id,name', 'server_package:id,name');

        //order by name
        $order_by   = $request->input('order_by', 'username');
        $order      = $request->input('order', 'asc');
        $users      = $users->orderBy($order_by, $order);

        //pagination
        $per_page   = $request->input('per_page', 20);
        $users      = $users->paginate($per_page);

        return response()->json($users);
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
        //get by id
        $userServer = ServerUser::with('server:id,name', 'server_package:id,name')->find($id);
        return response()->json($userServer);
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
