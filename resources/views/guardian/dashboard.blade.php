<h1>Dashboard Wali Murid</h1>

<h2>Data Wali Murid</h2>

<p>Nama: {{ $guardian->name }}</p>
<p>Email: {{ $guardian->user->email }}</p>
<p>No. HP: {{ $guardian->phone }}</p>
<p>Alamat: {{ $guardian->address }}</p>

<h2>Data Anak</h2>

@foreach ($students as $student)

    <h3>{{ $student->name }}</h3>

    <p>NIS: {{ $student->nis }}</p>
    <p>NISN: {{ $student->nisn }}</p>
    <p>Kelas: {{ $student->classRoom->name }}</p>

    <h4>Tagihan</h4>

    @forelse ($student->bills as $bill)

        <p>Nama: {{ $bill->name }}</p>
        <p>Nominal: Rp{{ number_format($bill->amount, 0, ',', '.') }}</p>
        <p>Jatuh Tempo: {{ $bill->due_date }}</p>
        <p>Status: {{ $bill->status }}</p>

    @empty

        <p>Tidak ada tagihan.</p>

    @endforelse

    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit" class="logout-button">
            Logout
        </button>
    </form>

@endforeach