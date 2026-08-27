@foreach ($students as $student)

    <h2>{{ $student->name }}</h2>

    <p>NIS: {{ $student->nis }}</p>
    <p>NISN: {{ $student->nisn }}</p>

    <h3>Tagihan</h3>

    @forelse ($student->bills as $bill)

        @php
            $payment = $bill->payments->sortByDesc('created_at')->first();
        @endphp

        <div>
            <p>Nama: {{ $bill->name }}</p>

            <p>
                Nominal:
                Rp{{ number_format($bill->amount, 0, ',', '.') }}
            </p>

            <p>
                Jatuh Tempo:
                {{ $bill->due_date }}
            </p>

            @if ($payment && $payment->status === 'paid')

                <p>Status: paid</p>

                <a href="{{ route('guardian.payments.show', $payment->id) }}">
                    Lihat Pembayaran
                </a>

            @elseif ($payment && $payment->status === 'pending')

                @php
                    $isRejected = $payment->latestVerification?->status === 'rejected';

                    $hasNewProof = $payment->proof_uploaded_at
                        && $payment->latestVerification?->processed_at
                        && $payment->proof_uploaded_at > $payment->latestVerification->processed_at;
                @endphp

                @if ($isRejected && !$hasNewProof)

                    <p>Status: Pembayaran Ditolak</p>

                    <a href="{{ route('guardian.payments.show', $payment->id) }}">
                        Lihat Pembayaran
                    </a>

                @else

                    <p>Status: Menunggu Verifikasi</p>

                    <a href="{{ route('guardian.payments.show', $payment->id) }}">
                        Lihat Pembayaran
                    </a>

                @endif
            @else

                <p>Status: unpaid</p>

                <a href="{{ route('guardian.payments.create', $bill->id) }}">
                    Bayar Sekarang
                </a>

            @endif

        </div>

    @empty

        <p>Belum ada tagihan.</p>

    @endforelse

@endforeach