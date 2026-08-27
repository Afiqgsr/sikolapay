const menuToggle = document.getElementById('menuToggle');
const sidebar = document.querySelector('.sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

menuToggle.addEventListener('click', function () {
    sidebar.classList.toggle('show');
    sidebarOverlay.classList.toggle('show');
});

sidebarOverlay.addEventListener('click', function () {
    sidebar.classList.remove('show');
    sidebarOverlay.classList.remove('show');
});

// PAYMENT MODAL
const confirmModal =
    document.getElementById("confirmModal");

const successModal =
    document.getElementById("successModal");

const openConfirmModal =
    document.getElementById("openConfirmModal");

if (openConfirmModal) {

    openConfirmModal.addEventListener("click", () => {

        confirmModal.classList.add("show");

    });

}

document
    .getElementById("cancelConfirm")
    ?.addEventListener("click", () => {

        confirmModal.classList.remove("show");

    });

document
    .getElementById("closeConfirmModal")
    ?.addEventListener("click", () => {

        confirmModal.classList.remove("show");

    });

document
    .getElementById("submitConfirm")
    ?.addEventListener("click", () => {

        confirmModal.classList.remove("show");

        successModal.classList.add("show");

    });

document
    .getElementById("closeSuccessModal")
    ?.addEventListener("click", () => {

        successModal.classList.remove("show");

    });

    document
    .getElementById("viewHistoryBtn")
    ?.addEventListener("click", () => {

        window.location.href =
            "riwayat-pembayaran.html";

    });
    
    document
    .getElementById("printNotaBtn")
    ?.addEventListener("click", () => {

        window.location.href =
            "Nota-pembayaran.html";

    });