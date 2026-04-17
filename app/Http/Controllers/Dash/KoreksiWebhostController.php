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
        } else if ($subject == 'xxx') {
            $result = $this->xxx();
        } else if ($subject == 'uppercase') {
            $result = $this->uppercase();
        } else if ($subject == 'kosong') {
            $result = $this->kosong();
        } else {
            $result = $subject;
        }
        return response()->json($result);
    }

    private function ganda()
    {
        $webhosts = Webhost::select('nama_web', DB::raw('COUNT(*) as total'))
            ->groupBy('nama_web')
            ->having('total', '>', 1)
            ->orderByDesc('total')
            ->paginate(100);
        $webhosts->withPath('');

        return $webhosts;
    }

    private function xxx()
    {
        // Mencari nama_web yang mengandung XXX, XX, atau X
        $webhosts = Webhost::select('nama_web', 'id_webhost')
            ->whereRaw("nama_web REGEXP 'XXX|(^.*XX[^X].*$)|X$'")
            ->paginate(100);
        $webhosts->withPath('');

        return $webhosts;
    }

    private function uppercase()
    {
        // Mencari nama_web yang mengandung huruf besar
        $webhosts = Webhost::select('nama_web', 'id_webhost')
            ->whereRaw("REGEXP_LIKE(nama_web, '[A-Z]', 'c')") // mengandung huruf besar A–Z
            ->paginate(100);
        $webhosts->withPath('');

        return $webhosts;
    }

    private function kosong()
    {
        // Mencari nama_web yang kosong
        $webhosts = Webhost::select('nama_web', 'id_webhost')
            ->where(function ($q) {
                $q->where('nama_web', '=', '')          // string kosong
                    ->orWhere('nama_web', '=', '  ')      // hanya spasi
                    ->orWhereNull('nama_web');            // NULL
            })
            ->paginate(100);
        $webhosts->withPath('');

        return $webhosts;
    }

    public function detail(Request $request)
    {
        $nama_web = $request->input('nama_web', ' ');
        $webhost = Webhost::with('paket', 'csMainProjects:id_webhost,jenis', 'whmcs_domain:webhost_id,expirydate')
            ->whereLike('nama_web', "%{$nama_web}%")
            ->select('id_webhost', 'nama_web', 'tgl_mulai', 'email', 'hp', 'id_paket')
            ->limit(50)
            ->get();

        if (!$webhost) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($webhost);
    }
}
