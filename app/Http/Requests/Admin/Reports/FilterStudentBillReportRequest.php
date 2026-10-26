<?php

namespace App\Http\Requests\Admin\Reports;

use App\Models\Bill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterStudentBillReportRequest extends FormRequest
{
    /**
     * Tentukan apakah user memiliki izin untuk menjalankan request ini.
     * Hak akses admin diproteksi oleh middleware role:admin pada route.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk filter rekapitulasi tagihan per siswa.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'month' => [
                'required',
                'integer',
                'between:1,12',
            ],
            'year' => [
                'required',
                'integer',
                'between:2000,2100',
            ],
            'bill_name' => [
                'nullable',
                'string',
                Rule::in(array_merge(Bill::TYPES, ['all', ''])),
            ],
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Pesan kustom untuk validasi filter rekapitulasi per siswa.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'month.required' => 'Bulan periode tagihan wajib dipilih.',
            'month.between' => 'Pilihan bulan tidak valid.',
            'year.required' => 'Tahun periode tagihan wajib diisi.',
            'year.between' => 'Pilihan tahun di luar batas yang diperbolehkan.',
            'bill_name.in' => 'Pilihan jenis tagihan tidak valid.',
        ];
    }
}
