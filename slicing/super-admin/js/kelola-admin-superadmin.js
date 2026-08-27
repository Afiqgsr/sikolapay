/* =========================================================
   MODAL DETAIL ADMIN
   ========================================================= */

function openAdminDetail() {

    const modal = document.getElementById("adminDetailModal");

    if (!modal) return;

    modal.style.display = "flex";

    document.body.style.overflow = "hidden";
}


function closeAdminDetail() {

    const modal = document.getElementById("adminDetailModal");

    if (!modal) return;

    modal.style.display = "none";

    document.body.style.overflow = "";
}


/* =========================================================
   MODAL EDIT ADMIN
   ========================================================= */

function openAdminEdit() {

    // Tutup modal detail terlebih dahulu
    closeAdminDetail();

    const modal = document.getElementById("adminEditModal");

    if (!modal) return;

    modal.style.display = "flex";

    document.body.style.overflow = "hidden";
}


function closeAdminEdit() {

    const modal = document.getElementById("adminEditModal");

    if (!modal) return;

    modal.style.display = "none";

    document.body.style.overflow = "";
}


/* =========================================================
   SIMPAN PERUBAHAN ADMIN
   ========================================================= */

function saveAdminEdit() {

    const nama = document.getElementById("editNama");
    const email = document.getElementById("editEmail");
    const role = document.getElementById("editRole");
    const status = document.getElementById("editStatus");
    const password = document.getElementById("editPassword");
    const passwordConfirm = document.getElementById("editPasswordConfirm");


    // Pastikan semua element tersedia
    if (
        !nama ||
        !email ||
        !role ||
        !status ||
        !password ||
        !passwordConfirm
    ) {
        return;
    }


    /* =====================================================
       VALIDASI FIELD
       ===================================================== */

    if (
        nama.value.trim() === "" ||
        email.value.trim() === "" ||
        role.value === "" ||
        status.value === "" ||
        password.value === "" ||
        passwordConfirm.value === ""
    ) {

        alert("Semua field wajib diisi.");

        return;
    }


    /* =====================================================
       VALIDASI PASSWORD
       ===================================================== */

    if (password.value.length < 8) {

        alert("Password minimal 8 karakter.");

        return;
    }


    /* =====================================================
       VALIDASI KONFIRMASI PASSWORD
       ===================================================== */

    if (password.value !== passwordConfirm.value) {

        alert("Konfirmasi password tidak sesuai.");

        return;
    }


    /* =====================================================
       SIMULASI BERHASIL
       ===================================================== */

    alert("Data admin berhasil diperbarui.");


    /* =====================================================
       TUTUP MODAL
       ===================================================== */

    closeAdminEdit();
}


/* =========================================================
   TOGGLE PASSWORD EDIT ADMIN
   ========================================================= */

function toggleEditPassword() {

    const password = document.getElementById("editPassword");
    const icon = document.getElementById("editPasswordIcon");

    if (!password || !icon) return;


    if (password.type === "password") {

        // Tampilkan password
        password.type = "text";

        icon.src = "../resource/img/View_hide_light-superadmin.svg";
        icon.alt = "Sembunyikan password";

    } else {

        // Sembunyikan password
        password.type = "password";

        icon.src = "../resource/img/View_light-superadmin.svg";
        icon.alt = "Lihat password";
    }
}


/* =========================================================
   TOGGLE KONFIRMASI PASSWORD EDIT ADMIN
   ========================================================= */

function toggleConfirmPassword() {

    const password = document.getElementById("editPasswordConfirm");
    const icon = document.getElementById("editPasswordConfirmIcon");

    if (!password || !icon) return;


    if (password.type === "password") {

        // Tampilkan password
        password.type = "text";

        icon.src = "../resource/img/View_hide_light-superadmin.svg";
        icon.alt = "Sembunyikan password";

    } else {

        // Sembunyikan password
        password.type = "password";

        icon.src = "../resource/img/View_light-superadmin.svg";
        icon.alt = "Lihat password";
    }
}


/* =========================================================
   MODAL TAMBAH ADMIN
   ========================================================= */

function openAdminAdd() {

    const modal = document.getElementById("adminAddModal");

    if (!modal) return;

    modal.style.display = "flex";

    document.body.style.overflow = "hidden";
}


function closeAdminAdd() {

    const modal = document.getElementById("adminAddModal");

    if (!modal) return;

    modal.style.display = "none";

    document.body.style.overflow = "";
}


/* =========================================================
   SIMPAN ADMIN BARU
   ========================================================= */

function saveAdminAdd() {

    const nama = document.getElementById("addNama");
    const email = document.getElementById("addEmail");
    const role = document.getElementById("addRole");
    const status = document.getElementById("addStatus");
    const password = document.getElementById("addPassword");
    const passwordConfirm = document.getElementById("addPasswordConfirm");


    // Pastikan semua element tersedia
    if (
        !nama ||
        !email ||
        !role ||
        !status ||
        !password ||
        !passwordConfirm
    ) {
        return;
    }


    /* =====================================================
       VALIDASI FIELD
       ===================================================== */

    if (
        nama.value.trim() === "" ||
        email.value.trim() === "" ||
        role.value === "" ||
        status.value === "" ||
        password.value === "" ||
        passwordConfirm.value === ""
    ) {

        alert("Semua field wajib diisi.");

        return;
    }


    /* =====================================================
       VALIDASI PASSWORD MINIMAL 8 KARAKTER
       ===================================================== */

    if (password.value.length < 8) {

        alert("Password minimal 8 karakter.");

        return;
    }


    /* =====================================================
       VALIDASI KONFIRMASI PASSWORD
       ===================================================== */

    if (password.value !== passwordConfirm.value) {

        alert("Konfirmasi password tidak sesuai.");

        return;
    }


    /* =====================================================
       SIMULASI BERHASIL
       ===================================================== */

    alert("Admin berhasil ditambahkan.");


    /* =====================================================
       TUTUP MODAL
       ===================================================== */

    closeAdminAdd();
}


/* =========================================================
   TOGGLE PASSWORD TAMBAH ADMIN
   ========================================================= */

function toggleAddPassword() {

    const password = document.getElementById("addPassword");
    const icon = document.getElementById("addPasswordIcon");

    if (!password || !icon) return;


    if (password.type === "password") {

        // Tampilkan password
        password.type = "text";

        icon.src = "../resource/img/View_hide_light-superadmin.svg";
        icon.alt = "Sembunyikan password";

    } else {

        // Sembunyikan password
        password.type = "password";

        icon.src = "../resource/img/View_light-superadmin.svg";
        icon.alt = "Lihat password";
    }
}


/* =========================================================
   TOGGLE KONFIRMASI PASSWORD TAMBAH ADMIN
   ========================================================= */

function toggleAddConfirmPassword() {

    const password = document.getElementById("addPasswordConfirm");
    const icon = document.getElementById("addPasswordConfirmIcon");

    if (!password || !icon) return;


    if (password.type === "password") {

        // Tampilkan password
        password.type = "text";

        icon.src = "../resource/img/View_hide_light-superadmin.svg";
        icon.alt = "Sembunyikan password";

    } else {

        // Sembunyikan password
        password.type = "password";

        icon.src = "../resource/img/View_light-superadmin.svg";
        icon.alt = "Lihat password";
    }
}


/* =========================================================
   MODAL HAPUS ADMIN
   ========================================================= */

function openAdminDelete() {

    const modal = document.getElementById("adminDeleteModal");

    if (!modal) {
        console.error("Modal adminDeleteModal tidak ditemukan.");
        return;
    }

    // Tutup modal lain
    closeAdminDetail();
    closeAdminEdit();
    closeAdminAdd();

    // Buka modal hapus
    modal.style.display = "flex";

    document.body.style.overflow = "hidden";
}

function closeAdminDelete() {

    const modal = document.getElementById("adminDeleteModal");

    if (!modal) return;

    modal.style.display = "none";

    document.body.style.overflow = "";
}


/* =========================================================
   KONFIRMASI HAPUS ADMIN
   ========================================================= */

function confirmAdminDelete() {

    /*
     * Untuk sementara masih simulasi.
     * Nanti bagian ini bisa diganti dengan
     * request Laravel / AJAX / form submit.
     */

    alert("Admin berhasil dihapus.");

    closeAdminDelete();
}
