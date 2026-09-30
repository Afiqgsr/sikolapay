<?php

namespace App\Http\Requests\Guardian\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuardianPaymentRequest extends FormRequest
{
    /**
     * Menentukan izin akses untuk request ini.
     *
     * Form Request ini fokus pada validasi struktur input.
     * Hak akses Guardian dan kepemilikan tagihan anak diverifikasi di Controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk formulir pembayaran tagihan oleh Wali Murid (Guardian).
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // ID tagihan wajib diisi dan harus terdaftar di tabel bills
            'bill_id' => [
                'required',
                'exists:bills,id',
            ],

            // ID metode pembayaran wajib diisi dan harus terdaftar di tabel payment_methods
            'payment_method_id' => [
                'required',
                'exists:payment_methods,id',
            ],

            // Bukti transfer wajib diunggah berupa file gambar (jpg, jpeg, png)
            // dengan batas ukuran maksimal 2048 KB (2 MB)
            'proof_of_payment' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],
        ];
    }
}
