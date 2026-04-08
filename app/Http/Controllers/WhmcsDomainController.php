<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WhmcsUser;
use App\Models\WhmcsDomain;
use App\Models\Webhost;
use Illuminate\Support\Str;

class WhmcsDomainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = WhmcsDomain::query();

        $query->with(['whmcs_user', 'webhost_data']);

        if ($request->input('search')) {
            $query->where('domain', 'like', '%' . $request->input('search') . '%')
                ->orWhere('user_email', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->input('uppercase_only') && $request->input('uppercase_only') === 'true') {
            $query->whereRaw("REGEXP_LIKE(domain, '[A-Z]', 'c')");
        }

        if ($request->input('webhost_disable') === 'true') {
            $query->whereNull('webhost_id');
        }

        if ($request->input('status')) {
            $query->whereStatus($request->input('status'));
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
        //validate request
        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'expirydate' => 'required|date',
            'nextduedate' => 'required|date',
            'registrationdate' => 'required|date',
            'status' => 'required|string',
            'user_email' => 'required|string',
            'whmcs_id' => 'required|integer',
            'whmcs_userid' => 'required|integer',
        ]);

        //get whmcs domain by id
        $WhmcsDomain = WhmcsDomain::findOrFail($id);

        if (!$WhmcsDomain) {
            return response()->json(['message' => 'Whmcs Domain not found'], 404);
        }

        //update whmcs domain
        $WhmcsDomain->update([
            'domain' => $validated['domain'],
            // 'expirydate' => $validated['expirydate'],
            // 'nextduedate' => $validated['nextduedate'],
            // 'registrationdate' => $validated['registrationdate'],
            // 'status' => $validated['status'],
            'user_email' => $validated['user_email'],
            // 'whmcs_id' => $validated['whmcs_id'],
            // 'whmcs_userid' => $validated['whmcs_userid'],
        ]);

        return response()->json($WhmcsDomain);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function webhost_search(Request $request)
    {

        // validasi
        $validated = $request->validate([
            'domain' => 'required|string',
            'email' => 'nullable|string',
            'id' => 'required|integer',
        ]);

        $domain = Str::lower(trim($validated['domain']));
        $email = $validated['email'] ? Str::lower(trim($validated['email'])) : null;

        // ambil data whmcs
        $whmcs = WhmcsDomain::findOrFail($validated['id']);


        // =====================
        // 1. EXACT MATCH
        // =====================
        $exact = Webhost::whereRaw('LOWER(nama_web) = ?', [$domain])
            ->when($email, fn($query) => $query->whereRaw('LOWER(email) = ?', [$email]))
            ->get();

        if ($exact->count() === 1) {

            $whmcs->update([
                'webhost_id' => $exact->first()->id_webhost,
            ]);

            return response()->json([
                'type' => 'exact',
                'auto_assigned' => true,
                'data' => $exact,
                'whmcs_domain' => $whmcs,
            ]);
        }

        // =====================
        // 2. DOMAIN ONLY
        // =====================
        $domainOnly = Webhost::whereRaw('LOWER(nama_web) = ?', [$domain])
            ->get();

        if ($domainOnly->count() === 1) {

            $whmcs->update([
                'webhost_id' => $domainOnly->first()->id_webhost
            ]);

            return response()->json([
                'type' => 'domain_only',
                'auto_assigned' => true,
                'data' => $domainOnly,
                'whmcs_domain' => $whmcs
            ]);
        }


        // =====================
        // 3. SIMILAR (NO AUTO)
        // =====================
        $similar = Webhost::where('nama_web', 'like', "%{$domain}%")
            ->limit(5)
            ->get();

        return response()->json([
            'type' => 'manual',
            'auto_assigned' => false,
            'data' => $similar,
            'whmcs_domain' => $whmcs
        ]);
    }

    public function webhost_select(Request $request)
    {
        // validasi
        $validated = $request->validate([
            'id' => 'required|integer',
            'webhost_id' => 'required|integer',
        ]);

        //get whmcs domain by id
        $whmcs = WhmcsDomain::findOrFail($validated['id']);

        if (!$whmcs) {
            return response()->json(['message' => 'Whmcs Domain not found'], 404);
        }

        //update whmcs domain
        $whmcs->update([
            'webhost_id' => $validated['webhost_id'],
        ]);

        return response()->json($whmcs);
    }
}
