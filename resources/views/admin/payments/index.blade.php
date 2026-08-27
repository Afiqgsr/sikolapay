<h1>Pembayaran Menunggu Verifikasi</h1>

@if ($payments->isEmpty())
    <p>Tidak ada pembayaran yang menunggu verifikasi.</p>
@else

    @foreach ($payments as $payment)

        <hr>

        <h2>{{ $payment->bill->name }}</h2>

        <p>
            Nomor Pembayaran:
            {{ $payment->payment_number }}
        </p>

        <p>
            Nama Anak:
            {{ $payment->bill->student->name }}
        </p>

        <p>
            Nominal:
            Rp{{ number_format($payment->amount, 0, ',', '.') }}
        </p>

        <p>
            Metode Pembayaran:
            {{ $payment->paymentMethod->name }}
        </p>

        <p>
            Status:
            {{ $payment->status }}
        </p>

        <p>
            Bukti:
            <a
                href="{{ asset('storage/' . $payment->proof_of_payment) }}"
                target="_blank"
            >
                Lihat Bukti Pembayaran
            </a>
        </p>

        <a href="{{ route('admin.payments.show', $payment->id) }}">
            Detail Pembayaran
        </a>

    @endforeach

@endif
