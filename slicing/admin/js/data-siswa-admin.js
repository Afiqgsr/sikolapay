document.addEventListener("DOMContentLoaded", function () {

    /* =========================================================
       TAMBAH SISWA
    ========================================================= */

    const addStudentButton =
        document.querySelector(".btn-primary");

    const addStudentModal =
        document.getElementById("addStudentModal");

    const addStudentClose =
        document.getElementById("addStudentClose");

    const addStudentCancel =
        document.getElementById("addStudentCancel");

    const addStudentSave =
        document.getElementById("addStudentSave");


    if (addStudentButton && addStudentModal) {

        addStudentButton.addEventListener("click", function () {

            addStudentModal.classList.add("active");

        });

    }


    if (addStudentClose && addStudentModal) {

        addStudentClose.addEventListener("click", function () {

            addStudentModal.classList.remove("active");

        });

    }


    if (addStudentCancel && addStudentModal) {

        addStudentCancel.addEventListener("click", function () {

            addStudentModal.classList.remove("active");

        });

    }


    if (addStudentSave && addStudentModal) {

        addStudentSave.addEventListener("click", function () {

            console.log("Data siswa ditambahkan");

            addStudentModal.classList.remove("active");

        });

    }


    if (addStudentModal) {

        addStudentModal.addEventListener("click", function (event) {

            if (event.target === addStudentModal) {

                addStudentModal.classList.remove("active");

            }

        });

    }


    /* =========================================================
       DETAIL DATA SISWA
    ========================================================= */

    const detailModal =
        document.getElementById("studentDetailModal");

    const detailClose =
        document.getElementById("studentDetailClose");

    const detailBtnClose =
        document.getElementById("studentDetailBtnClose");

    const detailBtnEdit =
        document.getElementById("studentDetailBtnEdit");

    const detailButtons =
        document.querySelectorAll(".action-detail");


    /* =========================================================
       BUKA DETAIL
    ========================================================= */

    detailButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            if (detailModal) {

                detailModal.classList.add("active");

            }

        });

    });


    /* =========================================================
       TUTUP DETAIL - ICON X
    ========================================================= */

    if (detailClose && detailModal) {

        detailClose.addEventListener("click", function () {

            detailModal.classList.remove("active");

        });

    }


    /* =========================================================
       TUTUP DETAIL - BUTTON
    ========================================================= */

    if (detailBtnClose && detailModal) {

        detailBtnClose.addEventListener("click", function () {

            detailModal.classList.remove("active");

        });

    }


    /* =========================================================
       EDIT DATA SISWA
    ========================================================= */

    const editModal =
        document.getElementById("studentEditModal");

    const editClose =
        document.getElementById("studentEditClose");

    const editCancel =
        document.getElementById("studentEditCancel");

    const editSave =
        document.getElementById("studentEditSave");

    const editButtons =
        document.querySelectorAll(".action-edit");


    /* =========================================================
       BUKA EDIT DARI TABEL
    ========================================================= */

    editButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            if (editModal) {

                editModal.classList.add("active");

            }

        });

    });


    /* =========================================================
       BUKA EDIT DARI DETAIL
    ========================================================= */

    if (detailBtnEdit) {

        detailBtnEdit.addEventListener("click", function () {

            if (detailModal) {

                detailModal.classList.remove("active");

            }

            if (editModal) {

                editModal.classList.add("active");

            }

        });

    }


    /* =========================================================
       TUTUP EDIT - ICON X
    ========================================================= */

    if (editClose && editModal) {

        editClose.addEventListener("click", function () {

            editModal.classList.remove("active");

        });

    }


    /* =========================================================
       TUTUP EDIT - BATAL
    ========================================================= */

    if (editCancel && editModal) {

        editCancel.addEventListener("click", function () {

            editModal.classList.remove("active");

        });

    }


    /* =========================================================
       SIMPAN PERUBAHAN
    ========================================================= */

    if (editSave && editModal) {

        editSave.addEventListener("click", function () {

            console.log("Data siswa diperbarui");

            editModal.classList.remove("active");

        });

    }


    /* =========================================================
       KLIK AREA LUAR DETAIL
    ========================================================= */

    if (detailModal) {

        detailModal.addEventListener("click", function (event) {

            if (event.target === detailModal) {

                detailModal.classList.remove("active");

            }

        });

    }


    /* =========================================================
       KLIK AREA LUAR EDIT
    ========================================================= */

    if (editModal) {

        editModal.addEventListener("click", function (event) {

            if (event.target === editModal) {

                editModal.classList.remove("active");

            }

        });

    }


    /* =========================================================
       ESCAPE
    ========================================================= */

    document.addEventListener("keydown", function (event) {

        if (event.key !== "Escape") {
            return;
        }


        if (
            addStudentModal &&
            addStudentModal.classList.contains("active")
        ) {

            addStudentModal.classList.remove("active");

        }


        if (
            detailModal &&
            detailModal.classList.contains("active")
        ) {

            detailModal.classList.remove("active");

        }


        if (
            editModal &&
            editModal.classList.contains("active")
        ) {

            editModal.classList.remove("active");

        }

    });

});

document.addEventListener("DOMContentLoaded", function () {

    /* =========================================================
       HAPUS DATA SISWA
    ========================================================= */

    const deleteModal =
        document.getElementById("studentDeleteModal");

    const deleteClose =
        document.getElementById("studentDeleteClose");

    const deleteCancel =
        document.getElementById("studentDeleteCancel");

    const deleteConfirm =
        document.getElementById("studentDeleteConfirm");

    const deleteButtons =
        document.querySelectorAll(".action-delete");


    /* =========================================================
       BUKA MODAL HAPUS
    ========================================================= */

    deleteButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            if (deleteModal) {

                deleteModal.classList.add("active");

            }

        });

    });


    /* =========================================================
       TUTUP - ICON X
    ========================================================= */

    if (deleteClose && deleteModal) {

        deleteClose.addEventListener("click", function () {

            deleteModal.classList.remove("active");

        });

    }


    /* =========================================================
       TUTUP - BATAL
    ========================================================= */

    if (deleteCancel && deleteModal) {

        deleteCancel.addEventListener("click", function () {

            deleteModal.classList.remove("active");

        });

    }


    /* =========================================================
       KONFIRMASI HAPUS
    ========================================================= */

    if (deleteConfirm && deleteModal) {

        deleteConfirm.addEventListener("click", function () {

            console.log("Data siswa dihapus");

            deleteModal.classList.remove("active");

        });

    }


    /* =========================================================
       KLIK AREA LUAR MODAL
    ========================================================= */

    if (deleteModal) {

        deleteModal.addEventListener("click", function (event) {

            if (event.target === deleteModal) {

                deleteModal.classList.remove("active");

            }

        });

    }

});