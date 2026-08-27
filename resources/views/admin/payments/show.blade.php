<h1>Detail Pembayaran</h1>

@if (session('success'))
    <p>
        {{ session('success') }}
    </p>
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
    NIS:
    {{ $payment->bill->student->nis }}
</p>

<p>
    NISN:
    {{ $payment->bill->student->nisn }}
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
    <p>Bukti pembayaran belum diunggah.</p>
@endif

@if ($payment->status === 'pending')

    <hr>

    <form
        action="{{ route('admin.payments.verify', $payment->id) }}"
        method="POST"
    >
        @csrf

        <button type="submit">
            Verifikasi Pembayaran
        </button>
    </form>

    <hr>

    <h3>Tolak Pembayaran</h3>

    <form
        action="{{ route('admin.payments.reject', $payment->id) }}"
        method="POST"
    >
        @csrf

        <label for="note">
            Alasan Penolakan
        </label>

        <br>

        <textarea
            id="note"
            name="note"
            rows="4"
            required
        ></textarea>

        <br>

        <button type="submit">
            Tolak Pembayaran
        </button>
    </form>

@endif

<hr>

<a href="{{ route('admin.payments.index') }}">
    Kembali ke Daftar Pembayaran
</a>