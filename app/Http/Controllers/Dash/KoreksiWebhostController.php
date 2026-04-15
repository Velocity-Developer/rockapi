<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Webhost;
use Illuminate\Support\Facades\DB;

class KoreksiWebhostController extends Controller
{
    public function index(Request $request, string $subject)
    {
        if ($subject == 'ganda') {
            $result = $this->ganda();
        } else {
            $result = $subject;
        }
        return response()->json($result);
    }

    private function ganda()
    {
        $duplicates = Webhost::select('nama_web', DB::raw('COUNT(*) as total'))
            ->groupBy('nama_web')
            ->having('total', '>', 1)
            ->orderByDesc('total')
            ->get();

        return $duplicates;
    }

    public function detail(Request $request)
    {
        $nama_web = $request->input('nama_web');
        $webhost = Webhost::with('paket', 'csMainProjects:id_webhost,jenis', 'whmcs_domain:webhost_id,expirydate')
            ->whereLike('nama_web', "%{$nama_web}%")
            ->select('id_webhost', 'nama_web', 'tgl_mulai', 'email', 'hp', 'id_paket')
            ->limit(100)
            ->get();

        if (!$webhost) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($webhost);
    }
}
