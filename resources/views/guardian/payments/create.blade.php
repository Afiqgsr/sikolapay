<h1>Pembayaran</h1>

<h2>{{ $bill->name }}</h2>

<p>Nama Anak: {{ $bill->student->name }}</p>

<p>
    Nominal:
    Rp{{ number_format($bill->amount, 0, ',', '.') }}
</p>

<p>Status: {{ $bill->status }}</p>

<hr>

<h3>Metode Pembayaran</h3>

@if (session('success'))
    <p>{{ session('success') }}</p>
@endif

<form method="POST" action="{{ route('guardian.payments.store') }}">
    @csrf

    <input
        type="hidden"
        name="bill_id"
        value="{{ $bill->id }}"
    >

    @forelse ($paymentMethods as $method)

        <div>
            <input
                type="radio"
                name="payment_method_id"
                value="{{ $method->id }}"
                id="method-{{ $method->id }}"
                required
            >

            <label for="method-{{ $method->id }}">
                {{ $method->name }}
            </label>
        </div>

    @empty

        <p>Belum ada metode pembayaran yang tersedia.</p>

    @endforelse

    <br>

    <button type="submit">
        Lanjutkan Pembayaran
    </button>
</form>
