<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhmcsUser;


class WhmcsUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = WhmcsUser::query();

        if ($request->input('search')) {
            $query->where('firstname', 'like', '%' . $request->input('search') . '%')
                ->orWhere('lastname', 'like', '%' . $request->input('search') . '%')
                ->orWhere('email', 'like', '%' . $request->input('search') . '%');
        }

        $per_page = $request->input('per_page', 20);
        $results = $query->paginate($per_page);

        $results->withPath('/whmcs-user');

        return response()->json($results);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //validate request
        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'whmcs_id' => 'required|integer',
        ]);

        //create whmcs user
        $whmcsUser = WhmcsUser::create([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'whmcs_id' => $validated['whmcs_id'],
        ]);

        return response()->json($whmcsUser);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $query = WhmcsUser::query();

        //check if with parameter is domains or hostings
        $with = $request->input('with', '');
        if ($with) {
            //explode with parameter
            $with = explode(',', $with);
            //trim each item in with array
            $with = array_map('trim', $with);
            $query->with($with);
        }

        //get whmcs by id
        $query->where('id', $id);

        $whmcsUser = $query->first();

        if (!$whmcsUser) {
            return response()->json(['message' => 'Whmcs User not found'], 404);
        }

        return response()->json($whmcsUser);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //validate request
        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'whmcs_id' => 'required|integer',
        ]);

        //get whmcs user by id
        $whmcsUser = WhmcsUser::find($id);

        if (!$whmcsUser) {
            return response()->json(['message' => 'Whmcs User not found'], 404);
        }

        //update whmcs user
        $whmcsUser->update([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'whmcs_id' => $validated['whmcs_id'],
        ]);

        return response()->json($whmcsUser);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //get whmcs user by id
        $whmcsUser = WhmcsUser::find($id);

        if (!$whmcsUser) {
            return response()->json(['message' => 'Whmcs User not found'], 404);
        }

        $whmcsUser->delete();

        return response()->json(['message' => 'Whmcs User deleted successfully']);
    }
}
