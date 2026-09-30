<?php

namespace App\Http\Requests\Guardian\Payments;

use Illuminate\Foundation\Http\FormRequest;

class UploadGuardianPaymentProofRequest extends FormRequest
{
    /**
     * Menentukan izin akses untuk request ini.
     *
     * Form Request ini khusus untuk menangani validasi berkas upload ulang bukti pembayaran oleh Guardian.
     * Hak akses dan kepemilikan tagihan/pembayaran diverifikasi pada Controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk pengunggahan ulang bukti pembayaran oleh Wali Murid.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // File bukti pembayaran baru wajib diunggah berupa gambar (jpg, jpeg, png)
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
