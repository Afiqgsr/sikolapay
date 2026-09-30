<?php

namespace App\Http\Requests\Admin\Payments;

use Illuminate\Foundation\Http\FormRequest;

class RejectPaymentRequest extends FormRequest
{
    /**
     * Menentukan izin akses untuk request ini.
     *
     * Form Request ini fokus pada validasi input catatan penolakan.
     * Hak akses Admin sudah diamankan oleh middleware route ('role:admin').
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk form penolakan bukti pembayaran.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Catatan alasan penolakan wajib diisi berupa teks string maksimal 1000 karakter
            'note' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }
}
