<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\ServerServices;
use App\Models\Server;
use App\Models\ServerPackage;

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
        return response()->json($server);
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

    public function sync_packages($id)
    {
        $serverService = ServerServices::make($id);
        $packages = $serverService->getPackages();

        if (isset($packages['error'])) {
            return response()->json($packages, 500);
        }

        //loop
        $newPackages = [];
        foreach ($packages as $package) {
            $package = ServerPackage::updateOrCreate([
                'server_id' => $id,
                'name'      => $package,
            ]);
            $newPackages[] = $package;
        }

        return response()->json($newPackages);
    }

    public function sync_packageDetail($idpackage)
    {
        //get serverPackage by id
        $serverPackage = ServerPackage::find($idpackage);
        $server_id = $serverPackage->server_id;
        $packageName = $serverPackage->name;

        $serverService = ServerServices::make($server_id);
        $packages = $serverService->getPackageDetail($packageName);

        if (isset($packages['error'])) {
            return response()->json($packages, 500);
        }

        //save serverPackage
        // $serverPackage->update([
        //     'price' => $packages['price'],
        // ]);

        return response()->json($packages);
    }

    public function get_users($id)
    {
        $serverService = ServerServices::make($id);
        $users = $serverService->getUsers();

        if (isset($accounts['error'])) {
            return response()->json($users, 500);
        }

        return response()->json($users);
    }
}
