<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhmcsUser;
use App\Models\WhmcsDomain;

class WhmcsDomainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = WhmcsDomain::query();

        if ($request->input('search')) {
            $query->where('domain', 'like', '%' . $request->input('search') . '%')
                ->orWhere('user_email', 'like', '%' . $request->input('search') . '%');
        }

        $per_page = $request->input('per_page', 20);
        $results = $query->paginate($per_page);

        $results->withPath('/whmcs-domain');

        return response()->json($results);
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
    public function show(Request $request, string $id)
    {
        $query = WhmcsDomain::query();

        //check if with parameter is domains or hostings
        $with = $request->input('with', '');
        if ($with) {
            //explode with parameter
            $with = explode(',', $with);
            //trim each item in with array
            $with = array_map('trim', $with);
            $query->with($with);
        }

        //get whmcs domain by id
        $query->where('id', $id);

        $whmcsDomain = $query->first();

        if (!$whmcsDomain) {
            return response()->json(['message' => 'Whmcs Domain not found'], 404);
        }

        return response()->json($whmcsDomain);
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
