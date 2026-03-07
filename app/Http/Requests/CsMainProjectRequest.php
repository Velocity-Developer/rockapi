<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CsMainProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return false;

        // jika user memiliki hak akses add-billing, maka bisa
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
            'jenis' => 'required|string',
            'nama_web' => 'required|string',
            'paket' => 'nullable|integer',
            'deskripsi' => 'nullable|string',
            'trf' => 'nullable|integer',
            'tgl_masuk' => 'required|string',
            'tgl_deadline' => [
                Rule::requiredIf(in_array($this->jenis, [
                    'Pembuatan apk',
                    'Pembuatan apk biasa',
                    'Pembuatan apk custom',
                    'Pembuatan',
                    'Pembuatan Tanpa Domain',
                    'Pembuatan Tanpa Hosting',
                    'Pembuatan Tanpa Domain+Hosting',
                    'Jasa Input Produk',
                    'Jasa Update Web',
                    'Jasa Buat Email',
                    'Jasa Ganti Domain',
                    'Jasa SEO',
                    'Jasa Buat Facebook',
                    'Jasa Buat Akun Sosmed',
                    'Jasa rating google maps',
                    'Jasa buat google maps',
                    'Redesign',
                    'Jasa Pembuatan Logo',
                    'Compro PDF',
                    'Pembuatan web konsep',
                ])),
                'nullable',
                'date'
            ],
            'biaya' => 'required|integer',
            'dibayar' => 'nullable|integer',
            'saldo' => 'nullable|string',
            'hp' => 'required|string',
            'telegram' => 'nullable|string',
            'hpads' => 'nullable|string',
            'wa' => 'required|string',
            'email' => 'nullable|string',
            'dikerjakan_oleh' => 'required|array',
            'kategori_web' => 'required|string',
        ];
    }
}
