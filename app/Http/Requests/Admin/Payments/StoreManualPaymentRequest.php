<?php

namespace App\Http\Requests\Admin\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualPaymentRequest extends FormRequest
{
    /**
     * Menentukan izin akses untuk request ini.
     *
     * Form Request ini fokus pada validasi struktur input.
     * Hak akses Admin sudah dibatasi dan diamankan oleh middleware route ('role:admin').
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk pencatatan pembayaran manual oleh Admin.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // ID Siswa wajib diisi dan harus benar-benar terdaftar di tabel students
            'student_id' => [
                'required',
                'exists:students,id',
            ],

            // ID Tagihan wajib diisi dan harus ada di tabel bills
            // Kepemilikan tagihan terhadap siswa tetap diverifikasi ulang pada Action
            'bill_id' => [
                'required',
                'exists:bills,id',
            ],

            // ID Metode Pembayaran wajib diisi dan harus terdaftar di tabel payment_methods
            // Status aktif metode pembayaran akan diperiksa lebih lanjut di Action
            'payment_method_id' => [
                'required',
                'exists:payment_methods,id',
            ],

            // Tanggal pembayaran wajib diisi dengan format tanggal valid
            // before_or_equal:now memastikan tanggal pembayaran manual tidak boleh di masa depan
            'paid_at' => [
                'required',
                'date',
                'before_or_equal:now',
            ],
        ];
    }
}
