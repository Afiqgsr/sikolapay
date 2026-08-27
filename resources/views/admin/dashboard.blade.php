<h1>Dashboard Admin SikolaPay</h1>

<p>Selamat datang, {{ auth()->user()->name }}</p>

<form method="POST" action="{{ route('logout') }}">
    @csrf

    <button type="submit" class="logout-button">
        Logout
    </button>
</form>