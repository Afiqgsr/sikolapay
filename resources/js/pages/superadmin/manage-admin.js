document.addEventListener('DOMContentLoaded', () => {

    const detailModal =
        document.getElementById('adminDetailModal');

    const editModal =
        document.getElementById('adminEditModal');

    const addModal =
        document.getElementById('adminAddModal');

    const deleteModal =
        document.getElementById('adminDeleteModal');


    const editForm =
        document.getElementById('adminEditForm');

    const deleteForm =
        document.getElementById('adminDeleteForm');


    const dataElement =
        document.getElementById('adminDetailData');


    let adminData = {};

    if (dataElement) {

        try {

            adminData = JSON.parse(
                dataElement.textContent
            );

        } catch (error) {

            adminData = {};

        }
    }


    let currentAdminId = null;


    const openModal = (modal) => {

        if (!modal) {
            return;
        }

        modal.classList.add('active');

        document.body.style.overflow =
            'hidden';
    };


    const closeModal = (modal) => {

        if (!modal) {
            return;
        }

        modal.classList.remove('active');

        if (
            !document.querySelector(
                '.admin-detail-overlay.active, ' +
                '.admin-edit-overlay.active, ' +
                '.admin-add-overlay.active, ' +
                '.admin-delete-overlay.active'
            )
        ) {
            document.body.style.overflow = '';
        }
    };


    // Tambah Admin

    document
        .getElementById('openAdminAddButton')
        ?.addEventListener('click', () => {

            openModal(addModal);

        });


    document
        .querySelectorAll('.admin-add-close')
        .forEach((button) => {

            button.addEventListener(
                'click',
                () => closeModal(addModal)
            );

        });


    // Detail Admin

    document
        .querySelectorAll('.admin-action.detail')
        .forEach((button) => {

            button.addEventListener(
                'click',
                () => {

                    const id =
                        button.dataset.id;

                    const admin =
                        adminData[id];

                    if (!admin) {
                        return;
                    }


                    currentAdminId = id;


                    document.getElementById(
                        'detailAdminInitial'
                    ).textContent =
                        admin.initials;


                    document.getElementById(
                        'detailAdminProfileName'
                    ).textContent =
                        admin.name;


                    document.getElementById(
                        'detailAdminProfileRole'
                    ).textContent =
                        admin.role_label;


                    document.getElementById(
                        'detailAdminName'
                    ).textContent =
                        admin.name;


                    document.getElementById(
                        'detailAdminEmail'
                    ).textContent =
                        admin.email;


                    document.getElementById(
                        'detailAdminRole'
                    ).textContent =
                        admin.role_label;


                    document.getElementById(
                        'detailAdminStatus'
                    ).textContent =
                        admin.status_label;


                    document.getElementById(
                        'detailAdminCreated'
                    ).textContent =
                        admin.created_at;


                    openModal(detailModal);

                }
            );

        });


    document
        .querySelectorAll('.admin-detail-close')
        .forEach((button) => {

            button.addEventListener(
                'click',
                () => closeModal(detailModal)
            );

        });


    // Edit Admin

    const openEdit = (admin) => {

        if (!admin || !editForm) {
            return;
        }


        currentAdminId =
            admin.id;


        editForm.action =
            admin.update_url;


        document.getElementById(
            'editNama'
        ).value =
            admin.name;


        document.getElementById(
            'editEmail'
        ).value =
            admin.email;


        document.getElementById(
            'editRole'
        ).value =
            admin.role;


        document.getElementById(
            'editStatus'
        ).value =
            admin.status;


        document.getElementById(
            'editPassword'
        ).value = '';


        document.getElementById(
            'editPasswordConfirm'
        ).value = '';


        openModal(editModal);
    };


    document
        .querySelectorAll('.admin-action.edit')
        .forEach((button) => {

            button.addEventListener(
                'click',
                () => {

                    const id =
                        button.dataset.id;

                    openEdit(
                        adminData[id]
                    );

                }
            );

        });


    document
        .getElementById('detailAdminEditButton')
        ?.addEventListener('click', () => {

            if (!currentAdminId) {
                return;
            }


            const admin =
                adminData[currentAdminId];


            closeModal(detailModal);

            openEdit(admin);

        });


    document
        .querySelectorAll('.admin-edit-close')
        .forEach((button) => {

            button.addEventListener(
                'click',
                () => closeModal(editModal)
            );

        });


    // Hapus Admin

    document
        .querySelectorAll('.admin-action.delete')
        .forEach((button) => {

            button.addEventListener(
                'click',
                () => {

                    const name =
                        button.dataset.name;

                    const email =
                        button.dataset.email;

                    const deleteUrl =
                        button.dataset.deleteUrl;


                    document.getElementById(
                        'deleteAdminIdentity'
                    ).textContent =
                        `${name} (${email})`;


                    deleteForm.action =
                        deleteUrl;


                    openModal(deleteModal);

                }
            );

        });


    document
        .querySelectorAll('.admin-delete-close')
        .forEach((button) => {

            button.addEventListener(
                'click',
                () => closeModal(deleteModal)
            );

        });


    // Toggle Password

    document
        .querySelectorAll(
            '[data-password-target]'
        )
        .forEach((button) => {

            button.addEventListener(
                'click',
                () => {

                    const input =
                        document.getElementById(
                            button.dataset.passwordTarget
                        );


                    if (!input) {
                        return;
                    }


                    input.type =
                        input.type === 'password'
                            ? 'text'
                            : 'password';

                }
            );

        });


    // Klik area luar modal

    [
        detailModal,
        editModal,
        addModal,
        deleteModal,
    ].forEach((modal) => {

        if (!modal) {
            return;
        }


        modal.addEventListener(
            'click',
            (event) => {

                if (event.target === modal) {
                    closeModal(modal);
                }

            }
        );

    });


    // Escape

    document.addEventListener(
        'keydown',
        (event) => {

            if (event.key !== 'Escape') {
                return;
            }


            closeModal(detailModal);
            closeModal(editModal);
            closeModal(addModal);
            closeModal(deleteModal);

        }
    );

});