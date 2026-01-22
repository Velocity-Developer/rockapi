<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RekapChat;
use Illuminate\Support\Facades\Validator;

class RekapChatController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        //query RekapChat
        $query = RekapChat::query()
            ->with('kk');

        $search    = $request->input('q');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('whatsapp', 'like', "%{$search}%")
                    ->orWhere('via', 'like', "%{$search}%")
                    ->orWhere('perangkat', 'like', "%{$search}%")
                    ->orWhere('alasan', 'like', "%{$search}%")
                    ->orWhere('detail', 'like', "%{$search}%")
                    ->orWhere('kata_kunci', 'like', "%{$search}%")
                    ->orWhereHas('kk', function ($q) use ($search) {
                        $q->where('kata_kunci', 'like', "%{$search}%");
                    });
            });
        }

        //filter tanggal chat_pertama
        $tgl_dari = $request->input('tgl_dari');
        //ubah format tgl_dari ke Y-m-d
        $tgl_dari = date('Y-m-d', strtotime($tgl_dari));
        //ubah format tgl_sampai ke Y-m-d
        $tgl_sampai = date('Y-m-d', strtotime($request->input('tgl_sampai')));

        if ($tgl_dari && $tgl_sampai) {
            $query->whereDate('chat_pertama', '>=', $tgl_dari)
                ->whereDate('chat_pertama', '<=', $tgl_sampai);
        }

        //filter whatsapp
        $whatsapp = $request->input('whatsapp');
        if ($whatsapp) {
            $query->where('whatsapp', 'like', "%{$whatsapp}%");
        }

        //filter alasan
        $alasan = $request->input('by_alasan');
        if ($alasan) {
            $query->where('alasan', $alasan);
        }

        //via
        $via = $request->input('via');
        if ($via) {
            $query->where('via', $via);
        }

        //keyword
        $keyword = $request->input('keyword');
        if ($keyword) {
            $query->where('detail', 'like', "%{$keyword}%");
        }

        $orderBy   = $request->input('order_by', 'id');
        $order     = $request->input('order', 'desc');
        $query->orderBy($orderBy, $order);

        $perPage   = (int) ($request->input('per_page', 100));
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
            'whatsapp' => 'required|string',
            'chat_pertama' => 'required|string',
            'via' => 'nullable|string',
            'perangkat' => 'nullable|string',
            'alasan' => 'required|string',
            'detail' => 'nullable|string',
            'kata_kunci' => 'nullable|string',
            'tanggal_followup' => 'nullable|date',
            'status_followup' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        //create rekap chat
        $rekapChat = RekapChat::create([
            'whatsapp' => $request->input('whatsapp'),
            'chat_pertama' => $request->input('chat_pertama'),
            'via' => $request->input('via'),
            'perangkat' => $request->input('perangkat'),
            'alasan' => $request->input('alasan'),
            'detail' => $request->input('detail') ?? null,
            'kata_kunci' => $request->input('kata_kunci') ?? null,
            'tanggal_followup' => $request->input('tanggal_followup') ?? null,
            'status_followup' => $request->input('status_followup') ?? null,
        ]);

        return response()->json($rekapChat);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //get by id
        $rekapChat = RekapChat::find($id);
        if (!$rekapChat) {
            return response()->json(['message' => 'Rekap Chat not found'], 404);
        }

        return response()->json($rekapChat);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //validate request
        $validator = Validator::make($request->all(), [
            'whatsapp' => 'required|string',
            'chat_pertama' => 'required|string',
            'via' => 'nullable|string',
            'perangkat' => 'nullable|string',
            'alasan' => 'required|string',
            'detail' => 'nullable|string',
            'kata_kunci' => 'nullable|string',
            'tanggal_followup' => 'nullable|date',
            'status_followup' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        //get by id
        $rekapChat = RekapChat::find($id);
        if (!$rekapChat) {
            return response()->json(['message' => 'Rekap Chat not found'], 404);
        }

        //update rekap chat
        $rekapChat->update([
            'whatsapp' => $request->input('whatsapp'),
            'chat_pertama' => $request->input('chat_pertama'),
            'via' => $request->input('via'),
            'perangkat' => $request->input('perangkat'),
            'alasan' => $request->input('alasan'),
            'detail' => $request->input('detail') ?? null,
            'kata_kunci' => $request->input('kata_kunci') ?? null,
            'tanggal_followup' => $request->input('tanggal_followup') ?? null,
            'status_followup' => $request->input('status_followup') ?? null,
        ]);

        return response()->json($rekapChat);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //get rekap chat by id
        $rekapChat = RekapChat::find($id);
        if (!$rekapChat) {
            return response()->json(['message' => 'Rekap Chat not found'], 404);
        }

        //delete rekap chat
        $rekapChat->delete();

        return response()->json(['message' => 'Rekap Chat deleted successfully']);
    }
}
