<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhmcsHosting;

class WhmcsHostingController extends Controller
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
    public function show(Request $request, string $id)
    {
        $query = WhmcsHosting::query();

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

        $whmcsHosting = $query->first();

        if (!$whmcsHosting) {
            return response()->json(['message' => 'Whmcs Hosting not found'], 404);
        }

        return response()->json($whmcsHosting);
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
