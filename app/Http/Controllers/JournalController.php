<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\JournalCategory;
use App\Models\JournalDetailSupport;
use App\Models\User;
use App\Services\Analytics\AnalyticsSupport;
use App\Services\Analytics\AnalyticsAdvertising;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Journal::with([
            'user:id,name,avatar',
            'journalCategory',
            'webhost:id_webhost,nama_web',
            'csMainProject:id,jenis',
            'detail_support',
        ]);

        // filter role - gunakan relasi ke user
        if ($request->input('role')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->whereHas('roles', function ($roleQuery) use ($request) {
                    $roleQuery->where('name', $request->input('role'));
                });
            });
        }

        // filter journal_role
        if ($request->input('journal_role')) {
            $query->where('role', $request->input('journal_role'));
        }

        // filter has_end_date
        if ($request->has('has_end_date')) {
            if ($request->boolean('has_end_date')) {
                $query->whereNotNull('end');
            } else {
                $query->whereNull('end');
            }
        }

        // filter user_id
        if ($request->input('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // filter journal_category_id
        if ($request->input('journal_category_id')) {
            $query->where('journal_category_id', $request->input('journal_category_id'));
        }

        // filter status
        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        // filter priority
        if ($request->input('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        // filter search
        if ($request->filled('search')) {
            $query->where(function ($query) use ($request) {
                $query->where('title', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhereHas('webhost', function ($q) use ($request) {
                        $q->where('nama_web', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // filter date_start & date_end
        if ($request->input('date_start') && $request->input('date_end')) {
            $start = $request->input('date_start') . ' 00:00:00';
            $end = $request->input('date_end') . ' 23:59:59';

            $query->where(function ($q) use ($start, $end) {
                // Jurnal yang dimulai dalam rentang tanggal
                $q->whereBetween('start', [$start, $end])
                    // Atau jurnal yang dimulai sebelum date_start tetapi selesai dalam rentang tanggal
                    ->orWhere(function ($subQuery) use ($start, $end) {
                        $subQuery->where('start', '<', $start)
                            ->where('end', '>=', $start)
                            ->where('end', '<=', $end);
                    });
            });
        }

        // filter by month
        if ($request->input('month')) {
            $month = $request->input('month');
            $query->whereMonth('start', date('m', strtotime($month)));
            $query->whereYear('start', date('Y', strtotime($month)));
        }

        // order by start
        $order_by = $request->input('order_by', 'start');
        $order = $request->input('order', 'asc');
        $query->orderBy($order_by, $order);

        // pagination
        $pagination = $request->input('pagination', 'true');
        if ($pagination == 'true') {
            $per_page = $request->input('per_page', 10);
            $journals = $query->paginate($per_page);
        } else {
            $get_journals = $query->get();
            $journals = [
                'data' => $get_journals,
            ];
        }

        // Hitung categoryStats dari semua data (tanpa paginasi) untuk statistik
        $allJournalsQuery = Journal::with(['journalCategory']);

        // Terapkan filter yang sama seperti query utama (kecuali pagination)
        if ($request->input('role')) {
            $allJournalsQuery->whereHas('user', function ($q) use ($request) {
                $q->whereHas('roles', function ($roleQuery) use ($request) {
                    $roleQuery->where('name', $request->input('role'));
                });
            });
        }
        if ($request->input('user_id')) {
            $allJournalsQuery->where('user_id', $request->input('user_id'));
        }
        if ($request->input('journal_category_id')) {
            $allJournalsQuery->where('journal_category_id', $request->input('journal_category_id'));
        }
        if ($request->input('status')) {
            $allJournalsQuery->where('status', $request->input('status'));
        }
        if ($request->input('priority')) {
            $allJournalsQuery->where('priority', $request->input('priority'));
        }
        if ($request->input('search')) {
            $allJournalsQuery->where('title', 'like', '%' . $request->input('search') . '%');
        }
        if ($request->input('date_start') && $request->input('date_end')) {
            $start = $request->input('date_start') . ' 00:00:00';
            $end = $request->input('date_end') . ' 23:59:59';

            $allJournalsQuery->where(function ($q) use ($start, $end) {
                // Jurnal yang dimulai dalam rentang tanggal
                $q->whereBetween('start', [$start, $end])
                    // Atau jurnal yang dimulai sebelum date_start tetapi selesai dalam rentang tanggal
                    ->orWhere(function ($subQuery) use ($start, $end) {
                        $subQuery->where('start', '<', $start)
                            ->where('end', '>=', $start)
                            ->where('end', '<=', $end);
                    });
            });
        }

        $allJournals = $allJournalsQuery->get();

        // Hitung statistik kategori
        $categoryStats = [];
        foreach ($allJournals as $journal) {
            $categoryName = $journal->journalCategory->name ?? 'Uncategorized';
            $categoryIcon = $journal->journalCategory->icon ?? '📝';
            $categoryId = $journal->journalCategory->id ?? null;

            if (! isset($categoryStats[$categoryName])) {
                $categoryStats[$categoryName] = [
                    'category_id' => $categoryId,
                    'nama' => $categoryName,
                    'jumlah' => 0,
                    'icon' => $categoryIcon,
                ];
            }

            $categoryStats[$categoryName]['jumlah']++;
        }

        // Konversi ke array numerik dan urutkan berdasarkan jumlah
        $categoryStats = array_values($categoryStats);
        usort($categoryStats, function ($a, $b) {
            return $b['jumlah'] - $a['jumlah'];
        });

        // Tambahkan categoryStats ke response
        if (is_array($journals)) {
            $journals['categoryStats'] = $categoryStats;
        } else {
            $journals = $journals->toArray();
            $journals['categoryStats'] = $categoryStats;
        }

        return response()->json($journals);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'start' => 'required|date',
            'end' => 'nullable|date',
            'status' => 'required|string',
            'priority' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'role' => 'nullable|string',
            'webhost_id' => 'nullable',
            'cs_main_project_id' => 'nullable',
            'journal_category_id' => 'nullable|exists:journal_categories,id',
        ]);

        if (! $request->input('user_id')) {
            $user_id = auth()->user()->id;
            $request->merge(['user_id' => $user_id]);
        }

        $journal = Journal::create([
            'title' => $request->title,
            'description' => $request->description,
            'start' => $request->start,
            'end' => $request->end,
            'status' => $request->status,
            'priority' => $request->priority,
            'user_id' => $request->user_id,
            'role' => $request->role,
            'webhost_id' => $request->webhost_id,
            'cs_main_project_id' => $request->cs_main_project_id,
            'journal_category_id' => $request->journal_category_id,
        ]);

        // .simpan detail_support
        if ($request->detail_support) {
            $detailSupport = $request->detail_support;

            // Cek apakah minimal salah satu field memiliki nilai
            $hasData = ! empty($detailSupport['hp']) ||
                ! empty($detailSupport['wa']) ||
                ! empty($detailSupport['email']) ||
                ! empty($detailSupport['biaya']) ||
                ! empty($detailSupport['tanggal_bayar']);

            if ($hasData) {
                // update or create
                JournalDetailSupport::updateOrCreate(
                    ['journal_id' => $journal->id],
                    [
                        'hp' => $detailSupport['hp'] ?? '',
                        'wa' => $detailSupport['wa'] ?? '',
                        'email' => $detailSupport['email'] ?? '',
                        'biaya' => $detailSupport['biaya'] ?? null,
                        'tanggal_bayar' => $detailSupport['tanggal_bayar'] ?? null,
                    ]
                );
            }
        }

        // get journal by id
        $journal = Journal::with(['user', 'journalCategory', 'detail_support'])->findOrFail($journal->id);

        return response()->json($journal);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $journal = Journal::with(['user', 'journalCategory', 'detail_support'])->findOrFail($id);

        return response()->json($journal);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'start' => 'required|date',
            'end' => 'nullable|date',
            'status' => 'required|string',
            'priority' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string',
            'webhost_id' => 'nullable',
            'cs_main_project_id' => 'nullable',
            'journal_category_id' => 'nullable|exists:journal_categories,id',
        ]);

        $journal = Journal::findOrFail($id);

        $journal->update([
            'title' => $request->title,
            'description' => $request->description,
            'start' => $request->start,
            'end' => $request->end,
            'status' => $request->status,
            'priority' => $request->priority,
            'user_id' => $request->user_id,
            'role' => $request->role,
            'webhost_id' => $request->webhost_id,
            'cs_main_project_id' => $request->cs_main_project_id,
            'journal_category_id' => $request->journal_category_id,
        ]);

        // .simpan detail_support
        if ($request->detail_support) {
            $detailSupport = $request->detail_support;

            // Cek apakah minimal salah satu field memiliki nilai
            $hasData = ! empty($detailSupport['hp']) ||
                ! empty($detailSupport['wa']) ||
                ! empty($detailSupport['email']) ||
                ! empty($detailSupport['biaya']) ||
                ! empty($detailSupport['tanggal_bayar']);

            if ($hasData) {
                // Cek apakah detail_support sudah ada
                if ($journal->detail_support) {
                    $journal->detail_support()->update([
                        'hp' => $detailSupport['hp'] ?? '',
                        'wa' => $detailSupport['wa'] ?? '',
                        'email' => $detailSupport['email'] ?? '',
                        'biaya' => $detailSupport['biaya'] ?? null,
                        'tanggal_bayar' => $detailSupport['tanggal_bayar'] ?? null,
                    ]);
                } else {
                    $journal->detail_support()->create([
                        'hp' => $detailSupport['hp'] ?? '',
                        'wa' => $detailSupport['wa'] ?? '',
                        'email' => $detailSupport['email'] ?? '',
                        'biaya' => $detailSupport['biaya'] ?? null,
                        'tanggal_bayar' => $detailSupport['tanggal_bayar'] ?? null,
                    ]);
                }
            } else {
                // Jika tidak ada data, hapus detail_support yang ada
                if ($journal->detail_support) {
                    $journal->detail_support()->delete();
                }
            }
        }

        // get journal by id
        $journal = Journal::with(['user', 'journalCategory', 'detail_support'])->findOrFail($journal->id);

        return response()->json($journal);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $journal = Journal::findOrFail($id);
            $journal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Journal deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Journal not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete journal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * timsupport_rangkuman
     */
    public function timsupport_rangkuman(Request $request): JsonResponse
    {
        $month = $request->input('month', date('Y-m'));
        $userId = $request->input('user_id', null);

        $analytic = new AnalyticsSupport;
        $data = $analytic->journal_response_time_avg($month, $userId);

        return response()->json($data);
    }

    /**
     * timads_rangkuman
     */
    public function timads_rangkuman(Request $request): JsonResponse
    {
        $month = $request->input('month', date('Y-m'));
        $userId = $request->input('user_id', null);

        //get user
        $userData = [];
        if ($userId) {
            $userData = User::select('id', 'name', 'email')->find($userId);
        }

        $analytic = new AnalyticsAdvertising;
        $bycategory = $analytic->journal_advertising_count_by_category($month, $userId);

        $dataUserAds = User::role('advertising')
            ->select('id', 'name')
            ->whereNotIn('id', [9])
            ->where('status', 'active')
            ->get();

        $categories = JournalCategory::where('role', 'advertising')->get();

        return response()->json([
            'categories' => $categories,
            'by_category' => $bycategory,
            'by_category_user' => $analytic->journal_advertising_count_by_category_and_user($month),
            'users_ads' => $dataUserAds,
            'user_data' => $userData
        ]);
    }
}
