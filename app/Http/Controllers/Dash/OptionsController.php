<?php

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Cache;

class OptionsController extends Controller
{
    public function get(string $key)
    {
        $result = [];

        switch ($key) {
            case 'roles':
                $result = $this->roles();
                break;
            case 'permissions':
                $result = $this->permissions();
                break;
            default:
                $result = [];
        }

        return response()->json($result);
    }

    private function roles()
    {
        return Cache::remember('options.roles', now()->addHours(6), function () {
            return Role::query()
                ->select('id', 'name')
                ->get()
                ->map(function ($role) {
                    return [
                        'value' => $role->name,
                        'label' => $role->name,
                        'id'    => $role->id,
                    ];
                })
                ->toArray();
        });
    }

    private function permissions()
    {
        return Cache::remember('options.permissions', now()->addHours(6), function () {

            return Permission::query()
                ->select('name')
                ->orderBy('name')
                ->get()
                ->map(fn($permission) => [
                    'value' => $permission->name,
                    'label' => $permission->name,
                ])
                ->toArray();
        });
    }
}
