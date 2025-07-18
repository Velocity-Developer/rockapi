<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServerController extends Controller
{
    public function index(Request $request)
    {
        $query = Server::select([
            'id',
            'name',
            'type',
            'ip_address',
            'hostname',
            'port',
            'is_active'
        ]);

        //pagination
        $per_page = $request->input('per_page', 20);

        $servers = $query->paginate($per_page);

        return response()->json($servers);
    }

    public function show($id)
    {
        $server = Server::findOrFail($id);

        return response()->json([
            'id' => $server->id,
            'name' => $server->name,
            'type' => $server->type,
            'ip_address' => $server->ip_address,
            'hostname' => $server->hostname,
            'port' => $server->port,
            'username' => $server->username,
            'password' => $server->password,
            'options' => $server->options,
            'is_active' => $server->is_active,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:servers,name',
            'type' => 'required|string|in:directadmin,cpanel,plesk,other',
            'ip_address' => 'nullable|ip',
            'hostname'  => 'nullable|string',
            'port'      => 'required|integer',
            'username'  => 'required|string',
            'password'  => 'required|string',
            'options'   => 'nullable|array',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $server = new Server($request->except('password'));
        $server->password = $request->password;
        $server->save();

        return response()->json(['message' => 'Server created', 'id' => $server->id]);
    }

    public function update(Request $request, $id)
    {
        $server = Server::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'          => 'sometimes|string',
            'type'          => 'sometimes|string|in:directadmin,cpanel,plesk,other',
            'ip_address'    => 'nullable|ip',
            'hostname'      => 'nullable|string',
            'port'          => 'sometimes|integer',
            'username'      => 'nullable|string',
            'password'      => 'nullable|string',
            'options'       => 'nullable|array',
            'is_active'     => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $server->fill($request->except('password'));
        if ($request->filled('password')) {
            $server->password = $request->password;
        }
        $server->save();

        return response()->json(['message' => 'Server updated']);
    }

    public function destroy($id)
    {
        $server = Server::findOrFail($id);
        $server->delete();

        return response()->json(['message' => 'Server deleted']);
    }
}
