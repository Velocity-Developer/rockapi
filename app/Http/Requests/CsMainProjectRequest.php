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
        return false;
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
            'paket'             => 'required|string',
            'deskripsi'         => 'nullable|string',
            'trf'               => 'nullable|string',
            'tgl_masuk'         => 'required|string',
            'tgl_deadline'      => 'required|string',
            'biaya'             => 'required|string',
            'dibayar'           => 'required|string',
            'saldo'             => 'nullable|string',
            'hp'                => 'required|string',
            'telegram'          => 'nullable|string',
            'hpads'             => 'nullable|string',
            'wa'                => 'required|string',
            'email'             => 'nullable|string',
            'di_kerjakan_oleh'  => 'nullable|string',
        ];
    }
}
