<?php

namespace App\Http\Requests\Student\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentBatchPaymentRequest extends FormRequest
{
    /**
     * Menentukan izin akses untuk request ini.
     *
     * Form Request ini fokus pada validasi input pembayaran serentak (batch).
     * Hak akses siswa dan kepemilikan seluruh tagihan diperiksa melalui middleware, Controller, dan Action.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk pembayaran seluruh tagihan eligible sekaligus oleh Siswa.
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

            // Bukti transfer bersama untuk seluruh tagihan wajib diunggah berupa file (jpg, jpeg, png, pdf)
            // dengan batas ukuran maksimal 5120 KB (5 MB). Bukti ini akan digunakan untuk semua record pembayaran di dalam batch.
            'proof_of_payment' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ];
    }
}
