<h1>Detail Tagihan</h1>

<h2>{{ $bill->name }}</h2>

<p>Nama Anak: {{ $bill->student->name }}</p>

<p>NIS: {{ $bill->student->nis }}</p>

<p>NISN: {{ $bill->student->nisn }}</p>

<p>Nominal: Rp{{ number_format($bill->amount, 0, ',', '.') }}</p>

<p>Jatuh Tempo: {{ $bill->due_date }}</p>

<p>Status: {{ $bill->status }}</p>

<p>{{ $bill->description }}</p>

<a href="{{ route('guardian.bills.index') }}">
    Kembali ke Daftar Tagihan
</a>

@if ($bill->status === 'unpaid')
    <br>
    <a href="{{ route('guardian.payments.create', $bill->id) }}">
        Bayar Sekarang
    </a>
@endif
