document.addEventListener('DOMContentLoaded', function () {

    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (!menuToggle || !sidebar || !sidebarOverlay) {
        return;
    }

    // BUKA / TUTUP SIDEBAR DARI BURGER
    menuToggle.addEventListener('click', function (event) {

        event.stopPropagation();

        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');

    });


    // TUTUP JIKA KLIK DI LUAR SIDEBAR
    document.addEventListener('click', function (event) {

        if (
            sidebar.classList.contains('show') &&
            !sidebar.contains(event.target) &&
            !menuToggle.contains(event.target)
        ) {

            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');

        }

    });


    // TUTUP KETIKA KLIK OVERLAY
    sidebarOverlay.addEventListener('click', function () {

        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');

    });

});