<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'customer',
            'items.webhost:id_webhost,nama_web',
        ]);

        // berdasarkan nomor invoice
        if ($request->input('search_nomor')) {
            $query->where('nomor', $request->input('search_nomor'));
        }

        // Filter berdasarkan status
        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter berdasarkan webhost_id (berdasarkan item)
        if ($request->input('webhost_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('webhost_id', $request->input('webhost_id'));
            });
        }

        // filter customer_id
        if ($request->input('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        // Filter berdasarkan tanggal
        $tanggal_start = $request->input('tanggal_start');
        $tanggal_end = $request->input('tanggal_end');
        if ($tanggal_start && $tanggal_end) {
            try {
                $start = Carbon::parse($tanggal_start)->startOfDay();
                $end = Carbon::parse($tanggal_end)->endOfDay();
                $query->whereBetween('tanggal', [$start, $end]);
            } catch (\Exception $e) {
                // abaikan filter jika tanggal invalid
            }
        }

        // Filter berdasarkan nama customer (kompatibel dengan param lama)
        if ($request->input('nama_klien')) {
            $nama = $request->input('nama_klien');
            $query->whereHas('customer', function ($q) use ($nama) {
                $q->where('nama', 'like', "%{$nama}%");
            });
        }

        // Filter berdasarkan unit
        if ($request->input('unit')) {
            $query->where('unit', 'like', '%'.$request->input('unit').'%');
        }

        // filter search_nama_web
        if ($request->input('search_nama_web') && $request->input('search_nomor') == null) {
            $search = $request->input('search_nama_web');

            $query->whereHas('items', function ($q) use ($search) {
                $q->where('nama', 'like', '%'.$search.'%')
                    ->orWhereHas('webhost', function ($q2) use ($search) {
                        $q2->where('nama_web', 'like', '%'.$search.'%');
                    });
            });
        }

        // filter search_hp
        if ($request->input('search_hp') && $request->input('search_nomor') == null) {
            $search_hp = $request->input('search_hp');
            $query->where(function ($query) use ($search_hp) {
                $query->whereHas('customer', function ($q) use ($search_hp) {
                    $q->where('hp', 'like', '%'.$search_hp.'%');
                });
            });
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
            'unit' => 'required|in:vdi,vcm',
            'customer_id' => 'required|exists:customers,id',
            'note' => 'nullable|string',
            'status' => 'required|string',
            'subtotal' => 'nullable|numeric',
            'pajak' => 'nullable|boolean',
            'nama_pajak' => 'nullable|string',
            'nominal_pajak' => 'nullable|numeric',
            'total' => 'nullable|numeric',
            'tanggal' => 'required|date',
            'jatuh_tempo' => 'nullable|date',
            'tanggal_bayar' => 'nullable|date',
            'items' => 'required|array',
            'items.*.harga' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Hitung subtotal dari items
            $subtotal = collect($request->items)->sum(function ($item) {
                return (float) ($item['harga'] ?? 0);
            });
            $nominalPajak = (float) ($request->nominal_pajak ?? 0);
            $total = (float) ($request->total ?? ($subtotal + $nominalPajak));

            // Buat invoice
            $invoice = Invoice::create([
                'unit' => $request->unit,
                'customer_id' => $request->customer_id,
                'note' => $request->note,
                'status' => $request->status,
                'subtotal' => $request->subtotal ?? $subtotal,
                'pajak' => $request->pajak,
                'nama_pajak' => $request->nama_pajak,
                'nominal_pajak' => $nominalPajak,
                'total' => $total,
                'tanggal' => $request->tanggal,
                'jatuh_tempo' => $request->jatuh_tempo,
                'tanggal_bayar' => $request->tanggal_bayar,
            ]);

            // Buat invoice items
            foreach ($request->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'webhost_id' => $item['webhost_id'],
                    'nama' => $item['nama'],
                    'jenis' => $item['jenis'],
                    'harga' => $item['harga'],
                ]);
            }

            DB::commit();

            // Load relasi
            $invoice->load(['customer', 'items.webhost:id_webhost,nama_web']);

            return response()->json($invoice, 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $invoice = Invoice::with([
            'customer',
            'items.webhost:id_webhost,nama_web',
        ])->find($id);

        if (! $invoice) {
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

        if (! $invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'unit' => 'required|in:vdi,vcm',
            'customer_id' => 'required|exists:customers,id',
            'note' => 'nullable|string',
            'status' => 'required|string',
            'subtotal' => 'nullable|numeric',
            'pajak' => 'nullable|boolean',
            'nama_pajak' => 'nullable|string',
            'nominal_pajak' => 'nullable|numeric',
            'total' => 'nullable|numeric',
            'tanggal' => 'required|date',
            'jatuh_tempo' => 'nullable|date',
            'tanggal_bayar' => 'nullable|date',
            'items' => 'required|array',
            'items.*.id' => 'nullable|exists:invoice_items,id',
            'items.*.harga' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Hitung subtotal jika tidak dikirim
            $subtotal = collect($request->items)->sum(function ($item) {
                return (float) ($item['harga'] ?? 0);
            });
            $nominalPajak = (float) ($request->nominal_pajak ?? 0);
            $total = (float) ($request->total ?? ($subtotal + $nominalPajak));

            // Update invoice
            $invoice->update([
                'unit' => $request->unit,
                'customer_id' => $request->customer_id,
                'note' => $request->note,
                'status' => $request->status,
                'subtotal' => $request->subtotal ?? $subtotal,
                'pajak' => $request->pajak,
                'nominal_pajak' => $nominalPajak,
                'nama_pajak' => $request->nama_pajak,
                'total' => $total,
                'tanggal' => $request->tanggal,
                'jatuh_tempo' => $request->jatuh_tempo,
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
                        'webhost_id' => $item['webhost_id'],
                        'nama' => $item['nama'],
                        'jenis' => $item['jenis'],
                        'harga' => $item['harga'],
                    ]);
                } else {
                    // Buat item baru
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'webhost_id' => $item['webhost_id'],
                        'nama' => $item['nama'],
                        'jenis' => $item['jenis'],
                        'harga' => $item['harga'],
                    ]);
                }
            }

            DB::commit();

            // Load relasi
            $invoice->load(['customer', 'items.webhost:id_webhost,nama_web']);

            return response()->json($invoice);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $invoice = Invoice::find($id);

        if (! $invoice) {
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

            return response()->json(['message' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
        }
    }

    /**
     * Generate PDF for the specified invoice.
     */
    public function printPdf(Request $request, string $id)
    {
        $invoice = Invoice::with([
            'customer',
            'items.webhost:id_webhost,nama_web',
        ])->find($id);

        if (! $invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        // Format tanggal
        $formattedDate = $this->formatDate($invoice->tanggal);
        $formattedPaymentDate = $this->formatDate($invoice->tanggal_bayar);
        $dueDate = $this->formatDate($invoice->jatuh_tempo);

        // Hitung total, paid amount, dan due amount
        $total = $this->calculateTotal($invoice);
        $paidAmount = $invoice->status === 'lunas' ? $total : 0;
        $dueAmount = max($total - $paidAmount, 0);

        // Data untuk view
        $data = [
            'invoice' => $invoice,
            'formattedDate' => $formattedDate,
            'formattedPaymentDate' => $formattedPaymentDate,
            'dueDate' => $dueDate,
            'total' => $total,
            'paidAmount' => $paidAmount,
            'dueAmount' => $dueAmount,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('invoice.pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        // Set filename
        $filename = 'Invoice-'.$invoice->nomor.'.pdf';

        // Check if download parameter is true
        if ($request->get('download') && $request->get('download') === 'true') {
            return $pdf->download($filename);
        }

        return $pdf->stream();
    }

    /**
     * Format date helper
     */
    private function formatDate(?string $date): string
    {
        if (! $date || $date === '0000-00-00') {
            return '-';
        }

        return Carbon::parse($date)->format('d/m/Y');
    }

    /**
     * Calculate total amount
     */
    private function calculateTotal($invoice): float
    {
        if ($invoice->total !== null && $invoice->total !== '') {
            return (float) $invoice->total;
        }

        return $invoice->items->sum(function ($item) {
            return (float) ($item->harga ?? 0);
        });
    }
}
