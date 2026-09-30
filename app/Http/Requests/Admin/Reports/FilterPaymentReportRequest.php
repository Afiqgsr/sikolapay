<?php

namespace App\Http\Requests\Admin\Reports;

use App\Models\Bill;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterPaymentReportRequest extends FormRequest
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
     * Aturan validasi untuk filter rekapitulasi pembayaran bulanan.
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
                'required',
                'string',
                Rule::in(Bill::TYPES),
            ],
            'grade' => [
                'nullable',
                'string',
                Rule::in(['X', 'XI', 'XII', '10', '11', '12']),
            ],
        ];
    }

    /**
     * Pesan kustom untuk validasi filter rekapitulasi.
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
            'bill_name.required' => 'Jenis tagihan wajib dipilih.',
            'bill_name.in' => 'Pilihan jenis tagihan tidak valid.',
            'grade.in' => 'Pilihan tingkat kelas tidak valid (hanya Kelas X, XI, atau XII).',
        ];
    }
}
