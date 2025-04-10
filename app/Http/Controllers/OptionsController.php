<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class OptionsController extends Controller
{

    public function get(string $key)
    {
        $result = [];

        switch ($key) {
            case 'roles':
                $result = $this->roles();
                break;
            default:
                $result = [];
        }

        return response()->json($result);
    }
    private function roles()
    {
        //get roles
        $roles = Role::all();

        //convert to array
        $result = [];
        foreach ($roles as $role) {
            $result[] = [
                'value' => $role->name,
                'label' => $role->name
            ];
        }

        return $result;
    }
}
