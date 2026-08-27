document.addEventListener("DOMContentLoaded", function () {

    /* =========================================================
       DETAIL MODAL
    ========================================================= */

    const billingModal =
        document.getElementById("billingModal");

    const billingModalClose =
        document.getElementById("billingModalClose");

    const billingBtnClose =
        document.getElementById("billingBtnClose");

    const billingBtnEdit =
        document.getElementById("billingBtnEdit");


    /* =========================================================
       EDIT MODAL
    ========================================================= */

    const editBillingModal =
        document.getElementById("editBillingModal");

    const editBillingClose =
        document.getElementById("editBillingClose");

    const editBillingCancel =
        document.getElementById("editBillingCancel");

    const editBillingSave =
        document.getElementById("editBillingSave");


    /* =========================================================
       TAMBAH TAGIHAN MODAL
    ========================================================= */

    const addBillingButton =
        document.querySelector(".billing-page .btn-primary");

    const addBillingModal =
        document.getElementById("addBillingModal");

    const addBillingClose =
        document.getElementById("addBillingClose");

    const addBillingCancel =
        document.getElementById("addBillingCancel");

    const addBillingSave =
        document.getElementById("addBillingSave");


    /* =========================================================
       HAPUS TAGIHAN MODAL
    ========================================================= */

    const deleteBillingModal =
        document.getElementById("deleteBillingModal");

    const deleteBillingClose =
        document.getElementById("deleteBillingClose");

    const deleteBillingCancel =
        document.getElementById("deleteBillingCancel");

    const deleteBillingConfirm =
        document.getElementById("deleteBillingConfirm");


    /* =========================================================
       HELPER
    ========================================================= */

    function openModal(modal) {

        if (!modal) return;

        modal.classList.add("active");

    }


    function closeModal(modal) {

        if (!modal) return;

        modal.classList.remove("active");

    }


    /* =========================================================
       DETAIL
    ========================================================= */

    /*
       Semua tombol Detail pada tabel
    */

    document.addEventListener("click", function (event) {

        const detailButton =
            event.target.closest(".action-detail");

        if (!detailButton) return;

        openModal(billingModal);

    });


    /* =========================================================
       TUTUP DETAIL - ICON X
    ========================================================= */

    if (billingModalClose) {

        billingModalClose.addEventListener("click", function () {

            closeModal(billingModal);

        });

    }


    /* =========================================================
       TUTUP DETAIL - BUTTON
    ========================================================= */

    if (billingBtnClose) {

        billingBtnClose.addEventListener("click", function () {

            closeModal(billingModal);

        });

    }


    /* =========================================================
       EDIT DARI DETAIL
    ========================================================= */

    if (billingBtnEdit) {

        billingBtnEdit.addEventListener("click", function () {

            closeModal(billingModal);

            openModal(editBillingModal);

        });

    }


    /* =========================================================
       EDIT LANGSUNG DARI TABEL
    ========================================================= */

    document.addEventListener("click", function (event) {

        const editButton =
            event.target.closest(".action-edit");

        if (!editButton) return;

        /*
           Tutup modal lain jika sedang terbuka
        */

        closeModal(billingModal);
        closeModal(deleteBillingModal);

        /*
           Buka modal edit
        */

        openModal(editBillingModal);

    });


    /* =========================================================
       TUTUP EDIT - ICON X
    ========================================================= */

    if (editBillingClose) {

        editBillingClose.addEventListener("click", function () {

            closeModal(editBillingModal);

        });

    }


    /* =========================================================
       TUTUP EDIT - BUTTON BATAL
    ========================================================= */

    if (editBillingCancel) {

        editBillingCancel.addEventListener("click", function () {

            closeModal(editBillingModal);

        });

    }


    /* =========================================================
       SIMPAN EDIT
    ========================================================= */

    if (editBillingSave) {

        editBillingSave.addEventListener("click", function () {

            /*
               Untuk sementara belum menyimpan ke database.
            */

            console.log("Perubahan tagihan disimpan");

            closeModal(editBillingModal);

        });

    }


    /* =========================================================
       TAMBAH TAGIHAN
    ========================================================= */

    if (addBillingButton) {

        addBillingButton.addEventListener("click", function () {

            openModal(addBillingModal);

        });

    }


    /* =========================================================
       TUTUP TAMBAH - ICON X
    ========================================================= */

    if (addBillingClose) {

        addBillingClose.addEventListener("click", function () {

            closeModal(addBillingModal);

        });

    }


    /* =========================================================
       TUTUP TAMBAH - BUTTON BATAL
    ========================================================= */

    if (addBillingCancel) {

        addBillingCancel.addEventListener("click", function () {

            closeModal(addBillingModal);

        });

    }


    /* =========================================================
       SIMPAN TAMBAH TAGIHAN
    ========================================================= */

    if (addBillingSave) {

        addBillingSave.addEventListener("click", function () {

            /*
               Untuk sementara belum menyimpan ke database.
            */

            console.log("Tagihan baru ditambahkan");

            closeModal(addBillingModal);

        });

    }


    /* =========================================================
       HAPUS TAGIHAN
    ========================================================= */

    document.addEventListener("click", function (event) {

        const deleteButton =
            event.target.closest(".action-delete");

        if (!deleteButton) return;

        /*
           Tutup modal lain
        */

        closeModal(billingModal);
        closeModal(editBillingModal);

        /*
           Simpan informasi baris yang dipilih
           supaya nanti bisa digunakan saat konfirmasi.
        */

        const row =
            deleteButton.closest("tr");

        if (row) {

            const studentName =
                row.children[1]?.textContent.trim();

            const billingType =
                row.children[3]?.textContent.trim();

            /*
               Isi teks pada modal hapus
               jika elemen tersedia.
            */

            const deleteStudent =
                document.getElementById("deleteStudentName");

            const deleteBillingType =
                document.getElementById("deleteBillingType");

            if (deleteStudent) {

                deleteStudent.textContent =
                    studentName;

            }

            if (deleteBillingType) {

                deleteBillingType.textContent =
                    billingType;

            }

        }


        /*
           Buka modal hapus
        */

        openModal(deleteBillingModal);

    });


    /* =========================================================
       TUTUP HAPUS - ICON X
    ========================================================= */

    if (deleteBillingClose) {

        deleteBillingClose.addEventListener("click", function () {

            closeModal(deleteBillingModal);

        });

    }


    /* =========================================================
       TUTUP HAPUS - BUTTON BATAL
    ========================================================= */

    if (deleteBillingCancel) {

        deleteBillingCancel.addEventListener("click", function () {

            closeModal(deleteBillingModal);

        });

    }


    /* =========================================================
       KONFIRMASI HAPUS
    ========================================================= */

    if (deleteBillingConfirm) {

        deleteBillingConfirm.addEventListener("click", function () {

            /*
               Untuk sementara hanya simulasi.
               Nanti bagian ini bisa diganti AJAX / Laravel.
            */

            console.log("Tagihan dihapus");

            closeModal(deleteBillingModal);

        });

    }


    /* =========================================================
       KLIK AREA LUAR MODAL
    ========================================================= */

    if (billingModal) {

        billingModal.addEventListener("click", function (event) {

            if (event.target === billingModal) {

                closeModal(billingModal);

            }

        });

    }


    if (editBillingModal) {

        editBillingModal.addEventListener("click", function (event) {

            if (event.target === editBillingModal) {

                closeModal(editBillingModal);

            }

        });

    }


    if (addBillingModal) {

        addBillingModal.addEventListener("click", function (event) {

            if (event.target === addBillingModal) {

                closeModal(addBillingModal);

            }

        });

    }


    if (deleteBillingModal) {

        deleteBillingModal.addEventListener("click", function (event) {

            if (event.target === deleteBillingModal) {

                closeModal(deleteBillingModal);

            }

        });

    }


    /* =========================================================
       TOMBOL ESCAPE
    ========================================================= */

    document.addEventListener("keydown", function (event) {

        if (event.key !== "Escape") return;

        closeModal(billingModal);
        closeModal(editBillingModal);
        closeModal(addBillingModal);
        closeModal(deleteBillingModal);

    });

});