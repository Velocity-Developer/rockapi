<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhmcsHosting;
use App\Services\WHMCSCustomService;

class WhmcsHostingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = WhmcsHosting::query();

        $with = $this->parseWith($request->input('with', ''));
        if ($with) {
            $query->with($with);
        }

        if ($request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($query) use ($search) {
                $query->where('domain', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('package_name', 'like', "%{$search}%")
                    ->orWhere('package_servertype', 'like', "%{$search}%");
            });
        }

        if ($request->input('domain')) {
            $query->where('domain', 'like', '%' . $request->input('domain') . '%');
        }

        if ($request->input('domainstatus')) {
            $query->where('domainstatus', $request->input('domainstatus'));
        }

        if ($request->input('billingcycle')) {
            $query->where('billingcycle', $request->input('billingcycle'));
        }

        if ($request->input('package_name')) {
            $query->where('package_name', 'like', '%' . $request->input('package_name') . '%');
        }

        if ($request->input('whmcs_userid')) {
            $query->where('whmcs_userid', $request->input('whmcs_userid'));
        }

        if ($request->input('regdate_from')) {
            $query->whereDate('regdate', '>=', $request->input('regdate_from'));
        }

        if ($request->input('regdate_to')) {
            $query->whereDate('regdate', '<=', $request->input('regdate_to'));
        }

        if ($request->input('nextduedate_from')) {
            $query->whereDate('nextduedate', '>=', $request->input('nextduedate_from'));
        }

        if ($request->input('nextduedate_to')) {
            $query->whereDate('nextduedate', '<=', $request->input('nextduedate_to'));
        }

        $query->orderBy('nextduedate')->orderBy('domain');

        $per_page = $request->input('per_page', 20);
        $results = $query->paginate($per_page);

        $results->withPath('/whmcs-hosting');

        return response()->json($results);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateHosting($request);

        $whmcsHosting = WhmcsHosting::create($validated);

        return response()->json($whmcsHosting, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $query = WhmcsHosting::query();

        //check if with parameter is whmcs_user, whmcs_domain, or webhost
        $with = $this->parseWith($request->input('with', ''));
        if ($with) {
            $query->with($with);
        }

        //get whmcs hosting by id
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
        $validated = $this->validateHosting($request, true);

        $whmcsHosting = WhmcsHosting::find($id);

        if (!$whmcsHosting) {
            return response()->json(['message' => 'Whmcs Hosting not found'], 404);
        }

        $whmcsHosting->update($validated);

        return response()->json($whmcsHosting);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $whmcsHosting = WhmcsHosting::find($id);

        if (!$whmcsHosting) {
            return response()->json(['message' => 'Whmcs Hosting not found'], 404);
        }

        $whmcsHosting->delete();

        return response()->json(['message' => 'Whmcs Hosting deleted successfully']);
    }

    private function parseWith(?string $with): array
    {
        if (!$with) {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,;]/', $with))));
    }

    private function validateHosting(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'nullable';

        return $request->validate([
            'whmcs_id' => [$required, 'nullable', 'integer'],
            'whmcs_userid' => [$required, 'nullable', 'integer'],
            'domain' => [$required, 'nullable', 'string', 'max:255'],
            'regdate' => [$required, 'nullable', 'date'],
            'nextduedate' => [$required, 'nullable', 'date'],
            'billingcycle' => [$required, 'nullable', 'string', 'max:255'],
            'domainstatus' => [$required, 'nullable', 'string', 'max:255'],
            'username' => [$required, 'nullable', 'string', 'max:255'],
            'diskusage' => [$required, 'nullable', 'integer'],
            'disklimit' => [$required, 'nullable', 'integer'],
            'bwusage' => [$required, 'nullable', 'integer'],
            'bwlimit' => [$required, 'nullable', 'integer'],
            'package_name' => [$required, 'nullable', 'string', 'max:255'],
            'package_servertype' => [$required, 'nullable', 'string', 'max:255'],
            'package_name_id' => [$required, 'nullable', 'string', 'max:255'],
        ]);
    }


    public function getHostingWhmcs(string $id, WHMCSCustomService $whmcsCustomService)
    {
        $whmcsHosting = WhmcsHosting::find($id);

        if (!$whmcsHosting) {
            return response()->json(['message' => 'Whmcs Hosting not found'], 404);
        }

        $data = $whmcsCustomService->getHosting($whmcsHosting->whmcs_id);

        //if false, return error
        if (!$data['success']) {
            return response()->json(['message' => 'Whmcs Hosting not found'], 404);
        }

        //update hosting
        $whmcsHosting->update([
            'diskusage' => $data['data'][0]['diskusage'],
            'disklimit' => $data['data'][0]['disklimit'],
            'bwusage' => $data['data'][0]['bwusage'],
            'bwlimit' => $data['data'][0]['bwlimit'],
            'username' => $data['data'][0]['username'],
        ]);

        return response()->json($whmcsHosting);
    }
}
