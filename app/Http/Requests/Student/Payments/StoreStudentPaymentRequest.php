<?php

namespace App\Http\Requests\Student\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'payment_method_id' => [
                'required',
                'exists:payment_methods,id',
            ],
            'proof_of_payment' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
        ];
    }
}
