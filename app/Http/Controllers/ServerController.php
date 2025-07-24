<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\ServerServices;
use App\Models\Server;
use App\Models\ServerPackage;
use App\Models\ServerUser;

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
        $serverPackage->update([
            'bandwidth' => $packages['bandwidth'],
            'email_daily_limit' => $packages['email_daily_limit'],
            'inode' => $packages['inode'],
            'quota' => $packages['quota'],
        ]);

        return response()->json($serverPackage);
    }

    public function sync_users($id)
    {
        $serverService = ServerServices::make($id);
        $users = $serverService->getUsers();

        if (isset($users['error']) && $users['error'] == true) {
            return response()->json($users, 500);
        }

        //loop simpan serverUser
        $newUsers = [];
        foreach ($users as $user) {
            $user = ServerUser::updateOrCreate([
                'server_id' => $id,
                'username'  => $user,
            ]);
            $newUsers[] = $user;
        }

        return response()->json($newUsers);
    }

    public function sync_userDetail($iduserserver)
    {
        //get serverUser by id
        $serverUser = ServerUser::find($iduserserver);
        $server_id = $serverUser->server_id;
        $username = $serverUser->username;

        $serverService = ServerServices::make($server_id);
        $users = $serverService->getUserDetails($username);

        if (isset($users['error'])) {
            return response()->json($users, 500);
        }

        //save serverUser
        $serverUser->update([
            'cron'          => $users['cron'],
            'domain'        => $users['domain'],
            'domains'       => $users['domains'],
            'ip'            => $users['ip'],
            'lets_encrypt'  => $users['letsEncrypt'],
            'name'          => $users['name'],
            'ns1'           => $users['ns1'],
            'ns2'           => $users['ns2'],
            'package'       => $users['package'],
            'php'           => $users['php'],
            'spam'          => $users['spam'],
            'ssh'           => $users['ssh'],
            'ssl'           => $users['ssl'],
            'suspended'     => $users['suspended'],
            'user_type'     => $users['userType'],
            'users'         => $users['users'],
            'wordpress'     => $users['wordpress'],
        ]);

        //get ServerPackage by name and id server
        $serverPackage = ServerPackage::where('server_id', $server_id)->where('name', $users['package'])->first();
        $serverUser->server_package_id = $serverPackage->id ?? '';
        $serverUser->save();

        $userServer = ServerUser::with('server:id,name', 'server_package:id,name')->find($serverUser->id);

        return response()->json($userServer);
    }
}
