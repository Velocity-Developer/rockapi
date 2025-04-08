<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        return [
            [
                'value' => 'admin',
                'label' => 'Admin'
            ],
            [
                'value' => 'owner',
                'label' => 'Owner'
            ],
            [
                'value' => 'manager_project',
                'label' => 'Manager Project'
            ],
            [
                'value' => 'manager_advertising',
                'label' => 'Manager Advertising'
            ],
            [
                'value' => 'finance',
                'label' => 'Finance'
            ],
            [
                'value' => 'support',
                'label' => 'Support'
            ],
            [
                'value' => 'revisi',
                'label' => 'Revisi'
            ],
            [
                'value' => 'advertising',
                'label' => 'Advertising'
            ],
            [
                'value' => 'webdev',
                'label' => 'Web Developer'
            ],
            [
                'value' => 'advertising_internal',
                'label' => 'Advertising Internal'
            ],
            [
                'value' => 'budi',
                'label' => 'Budi'
            ]
        ];
    }
}
