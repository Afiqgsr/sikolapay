<?php

namespace App\Http\Requests\Student\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentPaymentRequest extends FormRequest
{
    /**
     * Menentukan izin akses untuk request ini.
     *
     * Form Request ini fokus pada validasi input pembayaran.
     * Hak akses siswa dan kepemilikan Bill diperiksa melalui middleware, Controller, dan Action.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk konfirmasi pembayaran satu tagihan oleh Siswa.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // ID metode pembayaran wajib dikirim dan harus terdaftar di tabel payment_methods
            'payment_method_id' => [
                'required',
                'exists:payment_methods,id',
            ],

            // Bukti pembayaran wajib diunggah berupa file dokumen/gambar (jpg, jpeg, png, pdf)
            // dengan ukuran file maksimal 5120 KB (sekitar 5 MB)
            'proof_of_payment' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ];
    }
}
