<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RekapForm;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
}
