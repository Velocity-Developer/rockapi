<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FollowupAdvertiser;
use App\Models\CsMainProject;
use App\Models\Journal;
use App\Models\JournalCategory;
use App\Models\Webhost;
use Illuminate\Support\Facades\Auth;
use App\Services\Analytics\FollowupAdvertiserAnalytics;

class FollowupAdvertiserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $query = CsMainProject::with('webhost:id_webhost,nama_web,id_paket,wa', 'webhost.paket', 'webhost.followup_advertiser');
        $query->select('id', 'id_webhost', 'tgl_masuk', 'jenis');

        //jenis
        $query->whereIn('jenis', ['Pembuatan', 'Pembuatan apk', 'Pembuatan apk custom', 'Pembuatan web konsep', 'Pembuatan Tanpa Domain', 'Pembuatan Tanpa Hosting', 'Pembuatan Tanpa Domain+Hosting']);

        $orderBy = $request->query('order_by', 'tgl_masuk');
        $order = $request->query('order', 'desc');
        $query->orderBy($orderBy, $order);

        $perPage = (int) ($request->query('per_page', 20));
        $datas = $query->paginate($perPage);

        return response()->json($datas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_webhost_ads' => 'required|integer',
            'status_ads' => 'required|string',
            'update_ads' => 'required|date',
        ]);

        $user = Auth::user();
        $followup_advertiser = FollowupAdvertiser::create($validated);

        // dapatkan journal_category dengan name = 'Follow up'
        $journalCategory = JournalCategory::where('name', 'Follow up')->where('role', 'advertising')->first();
        // jika tidak ada, buat baru
        if (! $journalCategory) {
            $journalCategory = JournalCategory::create([
                'name' => 'Follow up',
                'role' => 'advertising',
                'description' => 'Kategori untuk Follow up Tim advertising',
                'icon' => '✅',
            ]);
        }

        //get webhost
        $webhost = Webhost::where('id_webhost', $followup_advertiser->id_webhost_ads)->first();

        // Create journal entry first
        $journal = Journal::create([
            'title' => 'Follow Up ' . $webhost->nama_web,
            'description' => 'Follow Up Ads untuk project pembuatan ' . $webhost->nama_web,
            'start' => now(),
            'end' => now(),
            'status' => 'completed',
            'priority' => 'medium',
            'user_id' => $user->id,
            'role' => 'advertising',
            'journal_category_id' => $journalCategory->id,
        ]);

        //foget cache
        $bln = date('Y-m', strtotime($followup_advertiser->update_ads));
        $FollowupAdvertiserAnalytics = new FollowupAdvertiserAnalytics;
        $FollowupAdvertiserAnalytics->forget_cache($bln);

        return response()->json([
            'message' => 'Data berhasil disimpan',
            'data' => $followup_advertiser,
            'journal' => $journal
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = FollowupAdvertiser::findOrFail($id);
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = FollowupAdvertiser::findOrFail($id);

        $validated = $request->validate([
            'id_webhost_ads' => 'required|integer',
            'status_ads' => 'required|string',
            'update_ads' => 'required|date',
        ]);

        $data->update($validated);

        //foget cache
        $bln = date('Y-m', strtotime($data->update_ads));
        $FollowupAdvertiserAnalytics = new FollowupAdvertiserAnalytics;
        $FollowupAdvertiserAnalytics->forget_cache($bln);

        return response()->json([
            'message' => 'Data berhasil diperbarui',
            'data' => $data
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = FollowupAdvertiser::findOrFail($id);
        $data->delete();

        return response()->json([
            'message' => 'Data berhasil dihapus'
        ]);
    }

    public function analytics()
    {
        $bln = date('Y-m');

        // Tambahkan data 'analytics'
        $FollowupAdvertiserAnalytics = new FollowupAdvertiserAnalytics;
        $blm_followup = $FollowupAdvertiserAnalytics->cs_main_project_blm_followup($bln);
        $by_status = $FollowupAdvertiserAnalytics->count_by_status($bln);

        //
        // if (isset($by_status['-'])) {
        //     $by_status['-'] = $by_status['-'] + $blm_followup;
        // };

        return response()->json([
            'bulan' => $bln,
            'blm_followup' => $blm_followup,
            'status' => $by_status,
        ]);
    }
}
