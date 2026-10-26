<?php

namespace App\Actions\Admin\Reports;

use App\Models\Bill;
use App\Models\School;
use App\Models\Student;
use Carbon\Carbon;

class BuildStudentBillRecapAction
{
    /**
     * Membangun data rekapitulasi tagihan untuk seorang siswa
     * berdasarkan periode tagihan (Bulan & Tahun) dan opsi Jenis Tagihan (bills.name).
     *
     * @param  Student  $student  Data siswa yang direkap
     * @param  int  $month  Bulan periode tagihan (1-12)
     * @param  int  $year  Tahun periode tagihan (misal: 2026)
     * @param  string|null  $billName  Nama jenis tagihan atau null/'all' untuk semua jenis tagihan
     * @return array<string, mixed>
     */
    public function execute(Student $student, int $month, int $year, ?string $billName = null): array
    {
        // Ambil data sekolah untuk KOP laporan
        $school = School::first();

        // Load relasi kelas siswa
        $student->loadMissing('classRoom');

        $billingPeriodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $billingPeriodEnd = $billingPeriodStart->copy()->addMonth();

        $query = Bill::query()
            ->where('student_id', $student->id)
            ->where('billing_period', '>=', $billingPeriodStart)
            ->where('billing_period', '<', $billingPeriodEnd);

        // Jika filter jenis tagihan spesifik dipilih (bukan 'all' atau kosong)
        if (! empty($billName) && $billName !== 'all') {
            $query->where('name', $billName);
        }

        $bills = $query->with([
            'latestPayment.latestVerification',
            'latestPayment.paymentMethod',
        ])
            ->orderBy('due_date')
            ->orderBy('name')
            ->get();

        $billRows = [];
        $totalAmount = 0.0;
        $totalPaid = 0.0;
        $paidCount = 0;
        $unpaidCount = 0;

        foreach ($bills as $bill) {
            $payment = $bill->latestPayment;
            $latestVerification = $payment?->latestVerification;
            $hasRejectedVerification = $latestVerification?->status === 'rejected';
            $isResubmitted = $hasRejectedVerification
                && $payment?->proof_uploaded_at
                && $latestVerification?->processed_at
                && $payment->proof_uploaded_at->gt($latestVerification->processed_at);

            if ($bill->status === 'paid' || $payment?->status === 'paid') {
                $status = 'paid';
                $statusLabel = 'Lunas';
            } elseif (
                $payment?->status === 'pending'
                && $payment?->proof_of_payment
                && (! $hasRejectedVerification || $isResubmitted)
            ) {
                $status = 'pending';
                $statusLabel = 'Menunggu';
            } else {
                $status = 'unpaid';
                $statusLabel = 'Belum Lunas';
            }

            $amount = (float) $bill->amount;
            $totalAmount += $amount;

            if ($status === 'paid') {
                $totalPaid += $amount;
                $paidCount++;
            } else {
                $unpaidCount++;
            }

            $paidDate = '-';
            if ($status === 'paid') {
                $paidDateRaw = $payment?->paid_at ?? $payment?->created_at;
                if ($paidDateRaw) {
                    $paidDate = $paidDateRaw->translatedFormat('d F Y');
                }
            }

            $billRows[] = [
                'id' => $bill->id,
                'name' => $bill->name,
                'description' => $bill->description,
                'semester' => $bill->semester,
                'amount' => $amount,
                'formatted_amount' => 'Rp '.number_format($amount, 0, ',', '.'),
                'due_date' => $bill->due_date ? $bill->due_date->translatedFormat('d F Y') : '-',
                'status' => $status,
                'status_label' => $statusLabel,
                'paid_date' => $paidDate,
                'payment_method' => $payment?->paymentMethod?->name ?? '-',
            ];
        }

        $totalUnpaid = $totalAmount - $totalPaid;
        $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F');
        $billNameLabel = (! empty($billName) && $billName !== 'all') ? $billName : 'Semua Jenis Tagihan';

        return [
            'school' => $school,
            'student' => $student,
            'period' => [
                'month' => $month,
                'month_name' => $monthName,
                'year' => $year,
                'bill_name' => $billName,
                'bill_name_label' => $billNameLabel,
                'formatted_period' => $monthName.' '.$year,
                'generated_at' => Carbon::now()->translatedFormat('d F Y'),
            ],
            'bills' => $billRows,
            'summary' => [
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaid,
                'total_unpaid' => $totalUnpaid,
                'bill_count' => count($bills),
                'paid_count' => $paidCount,
                'unpaid_count' => $unpaidCount,
            ],
            'has_data' => $bills->isNotEmpty(),
        ];
    }
}
