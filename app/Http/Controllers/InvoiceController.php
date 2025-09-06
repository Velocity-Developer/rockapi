<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with([
            'webhost:id_webhost,nama_web',
            'items'
        ]);

        // Filter berdasarkan status
        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter berdasarkan webhost_id
        if ($request->input('webhost_id')) {
            $query->where('webhost_id', $request->input('webhost_id'));
        }

        // Filter berdasarkan tanggal
        $tanggal_start = $request->input('tanggal_start');
        $tanggal_end = $request->input('tanggal_end');
        if ($tanggal_start && $tanggal_end) {
            $query->whereBetween('tanggal', [$tanggal_start, $tanggal_end]);
        }

        // Filter berdasarkan nama klien
        if ($request->input('nama_klien')) {
            $query->where('nama_klien', 'like', '%' . $request->input('nama_klien') . '%');
        }

        // Filter berdasarkan unit
        if ($request->input('unit')) {
            $query->where('unit', 'like', '%' . $request->input('unit') . '%');
        }

        // Pengurutan
        $order_by = $request->input('order_by', 'tanggal');
        $order = $request->input('order', 'desc');
        $query->orderBy($order_by, $order);

        // Paginasi
        $per_page = $request->input('per_page', 20);
        $invoices = $query->paginate($per_page);

        return response()->json($invoices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'unit' => 'required|in:vd,vcm',
            'nama_klien' => 'required|string',
            'alamat_klien' => 'nullable|string',
            'webhost_id' => 'required|exists:tb_webhost,id_webhost',
            'note' => 'nullable|string',
            'status' => 'required|string',
            'tanggal' => 'required|date',
            'tanggal_bayar' => 'nullable|date',
            'items' => 'required|array',
            'items.*.nama' => 'required|string',
            'items.*.jenis' => 'required|string',
            'items.*.harga' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Buat invoice
            $invoice = Invoice::create([
                'unit' => $request->unit,
                'nama_klien' => $request->nama_klien,
                'alamat_klien' => $request->alamat_klien,
                'webhost_id' => $request->webhost_id,
                'note' => $request->note,
                'status' => $request->status,
                'tanggal' => $request->tanggal,
                'tanggal_bayar' => $request->tanggal_bayar,
            ]);

            // Buat invoice items
            foreach ($request->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'nama' => $item['nama'],
                    'jenis' => $item['jenis'],
                    'harga' => $item['harga'],
                ]);
            }

            DB::commit();

            // Load relasi
            $invoice->load(['webhost:id_webhost,nama_web', 'items']);

            return response()->json($invoice, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $invoice = Invoice::with([
            'webhost:id_webhost,nama_web',
            'items'
        ])->find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        return response()->json($invoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'unit' => 'required|in:vd,vcm',
            'nama_klien' => 'required|string',
            'alamat_klien' => 'nullable|string',
            'webhost_id' => 'required|exists:tb_webhost,id_webhost',
            'note' => 'nullable|string',
            'status' => 'required|string',
            'tanggal' => 'required|date',
            'tanggal_bayar' => 'nullable|date',
            'items' => 'required|array',
            'items.*.id' => 'nullable|exists:invoice_items,id',
            'items.*.nama' => 'required|string',
            'items.*.jenis' => 'required|string',
            'items.*.harga' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Update invoice
            $invoice->update([
                'unit' => $request->unit,
                'nama_klien' => $request->nama_klien,
                'alamat_klien' => $request->alamat_klien,
                'webhost_id' => $request->webhost_id,
                'note' => $request->note,
                'status' => $request->status,
                'tanggal' => $request->tanggal,
                'tanggal_bayar' => $request->tanggal_bayar,
            ]);

            // Hapus item yang tidak ada di request
            $existingItemIds = collect($request->items)
                ->filter(function ($item) {
                    return isset($item['id']);
                })
                ->pluck('id')
                ->toArray();

            $invoice->items()->whereNotIn('id', $existingItemIds)->delete();

            // Update atau buat item baru
            foreach ($request->items as $item) {
                if (isset($item['id'])) {
                    // Update item yang sudah ada
                    InvoiceItem::where('id', $item['id'])->update([
                        'nama' => $item['nama'],
                        'jenis' => $item['jenis'],
                        'harga' => $item['harga'],
                    ]);
                } else {
                    // Buat item baru
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'nama' => $item['nama'],
                        'jenis' => $item['jenis'],
                        'harga' => $item['harga'],
                    ]);
                }
            }

            DB::commit();

            // Load relasi
            $invoice->load(['webhost:id_webhost,nama_web', 'items']);

            return response()->json($invoice);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        try {
            DB::beginTransaction();
            
            // Hapus semua item invoice
            $invoice->items()->delete();
            
            // Hapus invoice
            $invoice->delete();
            
            DB::commit();
            
            return response()->json(['message' => 'Invoice berhasil dihapus']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}
