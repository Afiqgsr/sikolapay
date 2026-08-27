<h1>Detail Pembayaran</h1>

@if (session('success')) <p>
{{ session('success') }} </p>
@endif

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

@if ($payment->latestVerification)

@if ($payment->latestVerification->status === 'rejected')

    <hr>

    <h3>Pembayaran Ditolak</h3>

    <p>
        Bukti pembayaran yang Anda kirimkan ditolak oleh admin.
    </p>

    @if ($payment->latestVerification->note)
        <p>
            <strong>Alasan Penolakan:</strong>
        </p>

        <p>
            {{ $payment->latestVerification->note }}
        </p>
    @endif

    <h3>Upload Bukti Pembayaran Baru</h3>

    <form
        action="{{ route('guardian.payments.proof', $payment->id) }}"
        method="POST"
        enctype="multipart/form-data"
    >
        @csrf

        <input
            type="file"
            name="proof_of_payment"
            accept=".jpg,.jpeg,.png"
            required
        >

        <br><br>

        <button type="submit">
            Upload Bukti Baru
        </button>
    </form>

@elseif ($payment->latestVerification->status === 'verified')

    <hr>

    <p>
        Pembayaran telah diverifikasi oleh admin.
    </p>

@endif

@endif

<hr>

<h3>Bukti Pembayaran</h3>

@if ($payment->proof_of_payment)

<p>
    <a
        href="{{ asset('storage/' . $payment->proof_of_payment) }}"
        target="_blank"
    >
        Lihat Bukti Pembayaran
    </a>
</p>

@else

<p>
    Bukti pembayaran belum diunggah.
</p>

@if ($payment->status === 'pending')

    <form
        action="{{ route('guardian.payments.proof', $payment->id) }}"
        method="POST"
        enctype="multipart/form-data"
    >
        @csrf

        <input
            type="file"
            name="proof_of_payment"
            accept=".jpg,.jpeg,.png"
            required
        >

        <br><br>

        <button type="submit">
            Upload Bukti Pembayaran
        </button>
    </form>

@endif


@endif

<hr>

<a href="{{ route('guardian.bills.index') }}">
    Kembali ke Daftar Tagihan
</a>
