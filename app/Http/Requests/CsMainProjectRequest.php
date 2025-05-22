<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CsMainProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        //return false;

        //jika user memiliki hak akses add-billing, maka bisa
        return auth()->check() && auth()->user()->can('add-billing');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'jenis'             => 'required|string',
            'nama_web'          => 'required|string',
            'paket'             => 'nullable|integer',
            'deskripsi'         => 'nullable|string',
            'trf'               => 'nullable|integer',
            'tgl_masuk'         => 'required|string',
            'tgl_deadline'      => 'required|string',
            'biaya'             => 'required|integer',
            'dibayar'           => 'required|integer',
            'saldo'             => 'nullable|string',
            'hp'                => 'required|string',
            'telegram'          => 'nullable|string',
            'hpads'             => 'nullable|string',
            'wa'                => 'required|string',
            'email'             => 'nullable|string',
            'dikerjakan_oleh'   => 'required|array',
        ];
    }
}
