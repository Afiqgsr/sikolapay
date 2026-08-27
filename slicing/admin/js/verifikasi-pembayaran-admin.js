document.addEventListener("DOMContentLoaded", function () {

    /* =========================================================
       ELEMENT MODAL
    ========================================================= */

    const paymentProofModal =
        document.getElementById("paymentProofModal");

    const paymentApproveModal =
        document.getElementById("paymentApproveModal");

    const paymentRejectModal =
        document.getElementById("paymentRejectModal");


    /* =========================================================
       ELEMENT MODAL BUKTI
    ========================================================= */

    const paymentProofClose =
        document.getElementById("paymentProofClose");

    const paymentProofBtnClose =
        document.getElementById("paymentProofBtnClose");

    const paymentProofReject =
        document.getElementById("paymentProofReject");

    const paymentProofApprove =
        document.getElementById("paymentProofApprove");


    /* =========================================================
       ELEMENT MODAL TERIMA
    ========================================================= */

    const paymentApproveClose =
        document.getElementById("paymentApproveClose");

    const paymentApproveCancel =
        document.getElementById("paymentApproveCancel");

    const paymentApproveConfirm =
        document.getElementById("paymentApproveConfirm");


    /* =========================================================
       ELEMENT MODAL TOLAK
    ========================================================= */

    const paymentRejectClose =
        document.getElementById("paymentRejectClose");

    const paymentRejectCancel =
        document.getElementById("paymentRejectCancel");

    const paymentRejectConfirm =
        document.getElementById("paymentRejectConfirm");

    const paymentRejectReason =
        document.getElementById("paymentRejectReason");


    /* =========================================================
       DEBUG
    ========================================================= */

    console.log("=== VERIFIKASI PEMBAYARAN ===");

    console.log("Modal Bukti:", paymentProofModal);
    console.log("Modal Terima:", paymentApproveModal);
    console.log("Modal Tolak:", paymentRejectModal);

    console.log(
        "Tombol Tolak:",
        document.querySelectorAll(".btn-reject").length
    );


    /* =========================================================
       FUNCTION BUKA MODAL
    ========================================================= */

    function openModal(modal) {

        if (!modal) {
            console.error("Modal tidak ditemukan.");
            return;
        }

        /* Tutup semua modal */

        if (paymentProofModal) {
            paymentProofModal.classList.remove("active");
        }

        if (paymentApproveModal) {
            paymentApproveModal.classList.remove("active");
        }

        if (paymentRejectModal) {
            paymentRejectModal.classList.remove("active");
        }


        /* Buka modal yang dipilih */

        modal.classList.add("active");

        console.log(
            "Modal dibuka:",
            modal.id,
            modal.classList.contains("active")
        );
    }


    /* =========================================================
       FUNCTION TUTUP MODAL
    ========================================================= */

    function closeModal(modal) {

        if (!modal) {
            return;
        }

        modal.classList.remove("active");
    }


    /* =========================================================
       LIHAT BUKTI
    ========================================================= */

    document
        .querySelectorAll(".btn-view-proof")
        .forEach(function (button) {

            button.addEventListener("click", function (event) {

                event.preventDefault();

                console.log("LIHAT BUKTI DIKLIK");

                openModal(paymentProofModal);

            });

        });


    /* =========================================================
       TERIMA DI TABEL
    ========================================================= */

    document
        .querySelectorAll(".btn-approve")
        .forEach(function (button) {

            button.addEventListener("click", function (event) {

                event.preventDefault();

                console.log("TERIMA DI TABEL DIKLIK");

                openModal(paymentApproveModal);

            });

        });


    /* =========================================================
       TOLAK DI TABEL
    ========================================================= */

    document
        .querySelectorAll(".btn-reject")
        .forEach(function (button) {

            button.addEventListener("click", function (event) {

                event.preventDefault();
                event.stopPropagation();

                console.log("================================");
                console.log("TOLAK DI TABEL DIKLIK");
                console.log("Modal Tolak:", paymentRejectModal);
                console.log("================================");


                /* Reset textarea */

                if (paymentRejectReason) {

                    paymentRejectReason.value = "";

                    paymentRejectReason.style.borderColor = "";

                }


                /* Buka modal */

                openModal(paymentRejectModal);

            });

        });


    /* =========================================================
       TOLAK DARI MODAL BUKTI
    ========================================================= */

    if (paymentProofReject) {

        paymentProofReject.addEventListener(
            "click",
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                console.log(
                    "TOLAK DARI MODAL BUKTI DIKLIK"
                );


                /* Reset textarea */

                if (paymentRejectReason) {

                    paymentRejectReason.value = "";

                    paymentRejectReason.style.borderColor = "";

                }


                /* Buka modal tolak */

                openModal(paymentRejectModal);

            }
        );

    }


    /* =========================================================
       TERIMA DARI MODAL BUKTI
    ========================================================= */

    if (paymentProofApprove) {

        paymentProofApprove.addEventListener(
            "click",
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                console.log(
                    "TERIMA DARI MODAL BUKTI DIKLIK"
                );

                openModal(paymentApproveModal);

            }
        );

    }


    /* =========================================================
       CLOSE MODAL BUKTI
    ========================================================= */

    if (paymentProofClose) {

        paymentProofClose.addEventListener(
            "click",
            function () {

                closeModal(paymentProofModal);

            }
        );

    }


    if (paymentProofBtnClose) {

        paymentProofBtnClose.addEventListener(
            "click",
            function () {

                closeModal(paymentProofModal);

            }
        );

    }


    /* =========================================================
       CLOSE MODAL TERIMA
    ========================================================= */

    if (paymentApproveClose) {

        paymentApproveClose.addEventListener(
            "click",
            function () {

                closeModal(paymentApproveModal);

            }
        );

    }


    if (paymentApproveCancel) {

        paymentApproveCancel.addEventListener(
            "click",
            function () {

                closeModal(paymentApproveModal);

            }
        );

    }


    /* =========================================================
       KONFIRMASI TERIMA
    ========================================================= */

    if (paymentApproveConfirm) {

        paymentApproveConfirm.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                console.log(
                    "PEMBAYARAN BERHASIL DITERIMA"
                );

                closeModal(paymentApproveModal);

                alert(
                    "Pembayaran berhasil diterima."
                );

            }
        );

    }


    /* =========================================================
       CLOSE MODAL TOLAK
    ========================================================= */

    if (paymentRejectClose) {

        paymentRejectClose.addEventListener(
            "click",
            function () {

                closeModal(paymentRejectModal);

            }
        );

    }


    if (paymentRejectCancel) {

        paymentRejectCancel.addEventListener(
            "click",
            function () {

                closeModal(paymentRejectModal);

            }
        );

    }


    /* =========================================================
       KONFIRMASI TOLAK
    ========================================================= */

    if (paymentRejectConfirm) {

        paymentRejectConfirm.addEventListener(
            "click",
            function (event) {

                event.preventDefault();

                const reason =
                    paymentRejectReason
                        ? paymentRejectReason.value.trim()
                        : "";


                /* Validasi */

                if (reason === "") {

                    alert(
                        "Silakan masukkan alasan penolakan."
                    );

                    if (paymentRejectReason) {

                        paymentRejectReason.focus();

                        paymentRejectReason.style.borderColor =
                            "var(--error)";

                    }

                    return;
                }


                /* Reset */

                if (paymentRejectReason) {

                    paymentRejectReason.style.borderColor = "";

                }


                console.log(
                    "Alasan penolakan:",
                    reason
                );


                /* Tutup */

                closeModal(paymentRejectModal);


                /* Reset textarea */

                if (paymentRejectReason) {

                    paymentRejectReason.value = "";

                }


                alert(
                    "Pembayaran berhasil ditolak."
                );

            }
        );

    }


    /* =========================================================
       KLIK OVERLAY
    ========================================================= */

    if (paymentProofModal) {

        paymentProofModal.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === paymentProofModal
                ) {

                    closeModal(paymentProofModal);

                }

            }
        );

    }


    if (paymentApproveModal) {

        paymentApproveModal.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === paymentApproveModal
                ) {

                    closeModal(paymentApproveModal);

                }

            }
        );

    }


    if (paymentRejectModal) {

        paymentRejectModal.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === paymentRejectModal
                ) {

                    closeModal(paymentRejectModal);

                }

            }
        );

    }


    /* =========================================================
       ESC
    ========================================================= */

    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key !== "Escape") {
                return;
            }

            closeModal(paymentProofModal);
            closeModal(paymentApproveModal);
            closeModal(paymentRejectModal);

        }
    );

});