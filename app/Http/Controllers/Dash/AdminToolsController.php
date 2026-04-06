<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AdminToolsController extends Controller
{

    private $tools = [
        [
            'key' => 'clear:logs',
            'name' => 'Clear Logs',
            'icon' => 'lucide:trash-2',
        ],
        [
            'key' => 'migrate',
            'name' => 'Migrate Database',
            'icon' => 'lucide:database',
        ],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 🔐 Cek apakah user punya permission 'edit-settings'
        $user = auth()->user();
        if (! $user->can('edit-settings')) {
            return response()->json([
                'message' => 'You do not have permission.',
            ], 422);
        }

        return response()->json($this->tools);
    }

    /**
     * Run the specified tool.
     */
    public function run(Request $request)
    {
        // 🔐 Cek apakah user punya permission 'edit-settings'
        $user = auth()->user();
        if (! $user->can('edit-settings')) {
            return response()->json([
                'message' => 'You do not have permission.',
            ], 422);
        }

        $key = $request->input('key');

        // Jalankan artisan command
        $exitCode = Artisan::call($key);

        // Ambil output dari artisan
        $output = Artisan::output();

        return response()->json([
            'exitCode' => $exitCode,
            'output'   => $output,
        ]);
    }
}
