<?php

namespace App\Http\Controllers;

use App\Models\CsMainProject;
use App\Models\Webhost;
use App\Models\WebhostSubscription;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WebhostSubscriptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = WebhostSubscription::query();

        $with = $request->query('with');
        if ($with && $with !== 'false') {
            $with = is_string($with) ? explode(',', $with) : $with;
            $with = array_map('trim', $with);
            $query->with($with);
        } else {
            $query->with([
                'webhost:id_webhost,nama_web,kategori',
                'csMainProject:id,id_webhost,jenis,tgl_masuk,biaya,dibayar,status,lunas',
                'parent:id,webhost_id,service_type,start_date,end_date,status',
                'renewals:id,webhost_id,parent_subscription_id,service_type,start_date,end_date,status',
            ]);
        }

        if ($request->query('select')) {
            $select = $request->query('select');
            $select = is_string($select) ? explode(',', $select) : $select;
            $select = array_map('trim', $select);
            $query->select($select);
        }

        $query->when($request->query('webhost_id'), function ($q) use ($request) {
            $q->where('webhost_id', $request->query('webhost_id'));
        });

        $query->when($request->query('cs_main_project_id'), function ($q) use ($request) {
            $q->where('cs_main_project_id', $request->query('cs_main_project_id'));
        });

        $query->when($request->query('parent_subscription_id'), function ($q) use ($request) {
            $q->where('parent_subscription_id', $request->query('parent_subscription_id'));
        });

        $query->when($request->query('source_type'), function ($q) use ($request) {
            $sourceType = $request->query('source_type');
            $sourceType = is_array($sourceType) ? $sourceType : [$sourceType];
            $q->whereIn('source_type', $sourceType);
        });

        $query->when($request->query('service_type'), function ($q) use ($request) {
            $serviceType = $request->query('service_type');
            $serviceType = is_array($serviceType) ? $serviceType : [$serviceType];
            $q->whereIn('service_type', $serviceType);
        });

        $query->when($request->query('status'), function ($q) use ($request) {
            $status = $request->query('status');
            $status = is_array($status) ? $status : [$status];
            $q->whereIn('status', $status);
        });

        $query->when($request->query('payment_status'), function ($q) use ($request) {
            $paymentStatus = $request->query('payment_status');
            $paymentStatus = is_array($paymentStatus) ? $paymentStatus : [$paymentStatus];
            $q->whereIn('payment_status', $paymentStatus);
        });

        $query->when($request->query('provider_status'), function ($q) use ($request) {
            $providerStatus = $request->query('provider_status');
            $providerStatus = is_array($providerStatus) ? $providerStatus : [$providerStatus];
            $q->whereIn('provider_status', $providerStatus);
        });

        $query->when($request->query('is_whmcs_mismatch'), function ($q) use ($request) {
            $value = filter_var($request->query('is_whmcs_mismatch'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value !== null) {
                $q->where('is_whmcs_mismatch', $value);
            }
        });

        $query->when($request->query('search_nama_web'), function ($q) use ($request) {
            $keyword = trim((string) $request->query('search_nama_web'));

            $q->whereHas('webhost', function ($webhostQuery) use ($keyword) {
                $webhostQuery->where('nama_web', 'like', '%' . $keyword . '%');
            });
        });

        $query->when($request->query('search'), function ($q) use ($request) {
            $keyword = trim((string) $request->query('search'));

            $q->where(function ($subQuery) use ($keyword) {
                $subQuery->where('description', 'like', '%' . $keyword . '%')
                    ->orWhere('service_type', 'like', '%' . $keyword . '%')
                    ->orWhere('source_type', 'like', '%' . $keyword . '%')
                    ->orWhereHas('webhost', function ($webhostQuery) use ($keyword) {
                        $webhostQuery->where('nama_web', 'like', '%' . $keyword . '%');
                    });
            });
        });

        $query->when($request->query('date_start'), function ($q) use ($request) {
            $q->whereDate('start_date', '>=', $request->query('date_start'));
        });

        $query->when($request->query('date_end'), function ($q) use ($request) {
            $q->whereDate('end_date', '<=', $request->query('date_end'));
        });

        $orderBy = $request->query('order_by', 'start_date');
        $allowedOrderBy = ['id', 'start_date', 'end_date', 'created_at', 'updated_at', 'status', 'service_type', 'nominal'];
        if (! in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'start_date';
        }

        $order = strtolower((string) $request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($orderBy, $order)->orderBy('id', 'desc');

        $perPage = (int) $request->query('per_page', 20);
        $subscriptions = $query->paginate($perPage)->withPath('/webhost-subscription');

        return response()->json($subscriptions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $validated = $this->ensureRelationsAreValid($validated);

        $subscription = WebhostSubscription::create($validated);
        $subscription->load($this->defaultRelations());

        return response()->json($subscription, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $query = WebhostSubscription::query();

        $with = $request->query('with');
        if ($with && $with !== 'false') {
            $with = is_string($with) ? explode(',', $with) : $with;
            $with = array_map('trim', $with);
            $query->with($with);
        } else {
            $query->with($this->defaultRelations());
        }

        $subscription = $query->find($id);

        if (! $subscription) {
            return response()->json(['message' => 'Webhost subscription tidak ditemukan'], 404);
        }

        return response()->json($subscription);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $subscription = WebhostSubscription::find($id);

        if (! $subscription) {
            return response()->json(['message' => 'Webhost subscription tidak ditemukan'], 404);
        }

        $validated = $this->validatePayload($request, $subscription->id);
        $validated = $this->ensureRelationsAreValid($validated, $subscription->id);

        $subscription->update($validated);
        $subscription->load($this->defaultRelations());

        return response()->json($subscription);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $subscription = WebhostSubscription::withCount('renewals')->find($id);

        if (! $subscription) {
            return response()->json(['message' => 'Webhost subscription tidak ditemukan'], 404);
        }

        if ($subscription->renewals_count > 0) {
            return response()->json([
                'message' => 'Subscription tidak bisa dihapus karena masih memiliki data renewal',
            ], 422);
        }

        $subscription->delete();

        return response()->json([
            'message' => 'Webhost subscription berhasil dihapus',
            'data' => $subscription,
        ]);
    }

    private function validatePayload(Request $request, ?int $currentId = null): array
    {
        return $request->validate([
            'webhost_id' => 'required|integer|exists:tb_webhost,id_webhost',
            'cs_main_project_id' => 'nullable|integer|exists:tb_cs_main_project,id',
            'parent_subscription_id' => 'nullable|integer|exists:webhost_subscriptions,id',
            'source_type' => 'nullable|string|max:100',
            'service_type' => 'nullable|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'renewed_from_date' => 'nullable|date',
            'status' => 'nullable|string|max:100',
            'payment_status' => 'nullable|string|max:100',
            'paid_at' => 'nullable|date',
            'provider_status' => 'nullable|string|max:100',
            'provider_expiry_date' => 'nullable|date',
            'is_whmcs_mismatch' => 'nullable|boolean',
            'nominal' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);
    }

    private function ensureRelationsAreValid(array $validated, ?int $currentId = null): array
    {
        if (! empty($validated['parent_subscription_id']) && $currentId && (int) $validated['parent_subscription_id'] === $currentId) {
            throw ValidationException::withMessages([
                'parent_subscription_id' => 'Parent subscription tidak boleh merujuk dirinya sendiri.',
            ]);
        }

        $webhost = Webhost::find($validated['webhost_id']);
        if (! $webhost) {
            throw ValidationException::withMessages([
                'webhost_id' => 'Webhost tidak ditemukan.',
            ]);
        }

        if (! empty($validated['cs_main_project_id'])) {
            $project = CsMainProject::find($validated['cs_main_project_id']);

            if (! $project) {
                throw ValidationException::withMessages([
                    'cs_main_project_id' => 'CS Main Project tidak ditemukan.',
                ]);
            }

            if ((int) $project->id_webhost !== (int) $validated['webhost_id']) {
                throw ValidationException::withMessages([
                    'cs_main_project_id' => 'CS Main Project harus berasal dari webhost yang sama.',
                ]);
            }
        }

        if (! empty($validated['parent_subscription_id'])) {
            $parent = WebhostSubscription::find($validated['parent_subscription_id']);

            if (! $parent) {
                throw ValidationException::withMessages([
                    'parent_subscription_id' => 'Parent subscription tidak ditemukan.',
                ]);
            }

            if ((int) $parent->webhost_id !== (int) $validated['webhost_id']) {
                throw ValidationException::withMessages([
                    'parent_subscription_id' => 'Parent subscription harus berasal dari webhost yang sama.',
                ]);
            }
        }

        $validated['source_type'] = $validated['source_type'] ?? 'csmainproject';
        $validated['service_type'] = $validated['service_type'] ?? 'hosting';
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['payment_status'] = $validated['payment_status'] ?? 'unpaid';
        $validated['is_whmcs_mismatch'] = $validated['is_whmcs_mismatch'] ?? false;

        return $validated;
    }

    private function defaultRelations(): array
    {
        return [
            'webhost:id_webhost,nama_web,kategori',
            'csMainProject:id,id_webhost,jenis,tgl_masuk,biaya,dibayar,status,lunas',
            'parent:id,webhost_id,service_type,start_date,end_date,status',
            'renewals:id,webhost_id,parent_subscription_id,service_type,start_date,end_date,status',
        ];
    }
}
