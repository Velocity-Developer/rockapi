<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RekapForm;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RekapFormController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage   = (int) ($request->input('per_page', 100));
        $orderBy   = $request->input('order_by', 'created_at');
        $order     = $request->input('order', 'desc');
        $search    = $request->input('q');

        //query RekapForm
        $query = RekapForm::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('hp', 'like', "%{$search}%");
            });
        }

        $query->orderBy($orderBy, $order);

        $results = $query->paginate($perPage);
        return response()->json($results);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //validate request
        $validator = Validator::make($request->all(), [
            'id' => 'required|string',
            'nama' => 'required|string',
            'no_whatsapp' => 'required|string',
            'jenis_website' => 'required|string',
            'ai_result' => 'required|string',
            'via' => 'required|string',
            'utm_content' => 'required|string',
            'utm_medium' => 'required|string',
            'greeting' => 'required|string',
            'status' => 'required|string',
            'gclid' => 'nullable|string',
            'created_at' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        //create rekap form
        $rekapForm = RekapForm::updateOrCreate(
            ['id' => $request->input('id')],
            [
                'nama'          => $request->input('nama'),
                'no_whatsapp'   => $request->input('no_whatsapp'),
                'jenis_website' => $request->input('jenis_website'),
                'ai_result'     => $request->input('ai_result'),
                'via'           => $request->input('via'),
                'utm_content'   => $request->input('utm_content'),
                'utm_medium'    => $request->input('utm_medium'),
                'greeting'      => $request->input('greeting'),
                'status'        => $request->input('status'),
                'gclid'         => $request->input('gclid') ?? null,
                'created_at'    => $request->input('created_at'),
            ]
        );

        return response()->json($rekapForm);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //get by id
        $rekapForm = RekapForm::find($id);
        if (!$rekapForm) {
            return response()->json(['message' => 'Rekap Form not found'], 404);
        }

        return response()->json($rekapForm);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //validate request
        $request->validate([
            'id' => 'required|string',
            'nama' => 'required|string',
            'no_whatsapp' => 'required|string',
            'jenis_website' => 'required|string',
            'ai_result' => 'required|string',
            'via' => 'required|string',
            'utm_content' => 'required|string',
            'utm_medium' => 'required|string',
            'greeting' => 'required|string',
            'status' => 'required|string',
            'gclid' => 'nullable|string',
            'created_at' => 'required|date',
        ]);

        //get by id
        $rekapForm = RekapForm::find($id);
        if (!$rekapForm) {
            return response()->json(['message' => 'Rekap Form not found'], 404);
        }

        //update rekap form
        $rekapForm->update($request->all());

        return response()->json($rekapForm);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //get rekap form by id
        $rekapForm = RekapForm::find($id);
        if (!$rekapForm) {
            return response()->json(['message' => 'Rekap Form not found'], 404);
        }

        //delete rekap form
        $rekapForm->delete();

        return response()->json(['message' => 'Rekap Form deleted successfully']);
    }

    public function get_konversi_ads(Request $request)
    {
        //query RekapForm
        $query = RekapForm::query();

        $search    = $request->input('q');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('hp', 'like', "%{$search}%");
            });
        }

        $status     = $request->input('status', 'sesuai');
        $query->where('status', $status);

        $cek_konversi_ads = $request->input('cek_konversi_ads', 0);
        $query->where('cek_konversi_ads', $cek_konversi_ads);

        //created_at diatas 2026-01-10 00:00:00
        $query->where('created_at', '>', Carbon::create(2026, 1, 10)->startOfDay());

        //pastikan gclid tidak null
        $query->whereNotNull('gclid')
            ->where('gclid', '!=', '');

        $perPage   = (int) ($request->input('per_page', 100));
        $orderBy   = $request->input('order_by', 'created_at');
        $order     = $request->input('order', 'desc');
        $query->orderBy($orderBy, $order);

        $results = $query->paginate($perPage);
        return response()->json($results);
    }

    //update cek_konversi_ads by array of id
    public function update_cek_konversi_ads(Request $request)
    {
        //validate request
        $request->validate([
            'data' => 'required|array|min:1',
            'data.*.id' => 'required|integer',
            'data.*.cek_konversi_ads' => 'required|boolean',
        ]);

        //loop data
        $results = [];
        foreach ($request->input('data') as $item) {

            //update item cek_konversi_ads
            $rekapForm = RekapForm::find($item['id']);
            if (!$rekapForm) {

                $results[] = [
                    'id' => $item['id'],
                    'cek_konversi_ads' => $item['cek_konversi_ads'],
                    'message' => 'RekapForm not found',
                ];

                continue;
            }
            $rekapForm->update([
                'cek_konversi_ads' => $item['cek_konversi_ads'],
            ]);

            $results[] = [
                'id' => $item['id'],
                'cek_konversi_ads' => $item['cek_konversi_ads'],
                'message' => 'RekapForm update success',
            ];
        }

        //update rekap form
        return response()->json([
            'message' => 'RekapForm update success',
            'results' => $results,
        ]);
    }
}
