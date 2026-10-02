<?php

namespace App\Http\Requests\Admin\Bills;

use App\Models\AcademicYear;
use App\Models\Bill;
use App\Models\BillBatch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class StoreBillRequest extends FormRequest
{
    /**
     * Menentukan apakah user memiliki otorisasi untuk request ini.
     * Proteksi hak akses admin dikelola oleh middleware route 'role:admin'.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk pembuatan tagihan baru (Store).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validSemesters = $this->getValidSemesters();
        $targetType = $this->input('target_type');

        $targetValueRules = match ($targetType) {
            'student' => ['required', 'integer', 'exists:students,id'],
            'class' => ['required', 'integer', 'exists:class_rooms,id'],
            'grade' => ['required', 'string', Rule::in(['X', 'XI', 'XII'])],
            'school' => ['nullable', 'max:255'],
            default => ['nullable', 'max:255'],
        };

        return [
            'target_type' => [
                'required',
                Rule::in([
                    'student',
                    'class',
                    'grade',
                    'school',
                ]),
            ],

            'target_value' => $targetValueRules,

            'name' => [
                'required',
                'string',
                Rule::in(Bill::TYPES),
            ],

            'semester' => [
                'required',
                'string',
                Rule::in($validSemesters->all()),
            ],

            'amount' => [
                'required',
                'numeric',
                'min:1',
            ],

            'billing_period' => [
                'required',
                'date_format:Y-m',
            ],

            'due_date' => [
                'nullable',
                'date',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Pesan kustom untuk error validasi.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [
            'name.in' => 'Pilihan jenis tagihan tidak valid.',
            'semester.in' => 'Pilihan semester tidak valid.',
            'target_value.in' => 'Pilihan tingkat kelas tidak valid. Pilih Kelas X, Kelas XI, atau Kelas XII.',
        ];

        $targetType = $this->input('target_type');

        if ($targetType === 'student') {
            $messages['target_value.required'] = 'Siswa target tagihan wajib dipilih.';
            $messages['target_value.exists'] = 'Data siswa yang dipilih tidak ditemukan.';
        } elseif ($targetType === 'class') {
            $messages['target_value.required'] = 'Kelas target tagihan wajib dipilih.';
            $messages['target_value.exists'] = 'Data kelas yang dipilih tidak ditemukan.';
        } elseif ($targetType === 'grade') {
            $messages['target_value.required'] = 'Tingkat kelas target tagihan wajib dipilih.';
        }

        return $messages;
    }

    /**
     * Mengambil daftar semester yang sah berdasarkan Tahun Ajaran aktif atau data existing.
     *
     * @return Collection<int, string>
     */
    protected function getValidSemesters(): Collection
    {
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        if (! $activeAcademicYear) {
            $activeAcademicYear = AcademicYear::latest()->first();
        }

        if ($activeAcademicYear) {
            return collect([
                'Ganjil '.$activeAcademicYear->name,
                'Genap '.$activeAcademicYear->name,
            ]);
        }

        return BillBatch::query()
            ->whereNotNull('semester')
            ->where('semester', '!=', '')
            ->distinct()
            ->pluck('semester');
    }
}
