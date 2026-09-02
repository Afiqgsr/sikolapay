document.addEventListener('DOMContentLoaded', function () {

    const addModal = document.getElementById('addStudentModal');
    const detailModal = document.getElementById('studentDetailModal');
    const editModal = document.getElementById('studentEditModal');
    const deleteModal = document.getElementById('studentDeleteModal');

    const openAddButton = document.getElementById('openAddStudentModal');

    const addClose = document.getElementById('addStudentClose');
    const addCancel = document.getElementById('addStudentCancel');

    const detailClose = document.getElementById('studentDetailClose');
    const detailBtnClose = document.getElementById('studentDetailBtnClose');
    const detailBtnEdit = document.getElementById('studentDetailBtnEdit');

    const editClose = document.getElementById('studentEditClose');
    const editCancel = document.getElementById('studentEditCancel');

    const deleteClose = document.getElementById('studentDeleteClose');
    const deleteCancel = document.getElementById('studentDeleteCancel');

    const editForm = document.getElementById('editStudentForm');
    const deleteForm = document.getElementById('deleteStudentForm');

    let selectedStudent = null;

    function openModal(modal) {
        if (!modal) return;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        if (!modal) return;

        modal.classList.remove('active');

        const activeModal = document.querySelector(
            '.student-add-overlay.active, ' +
            '.student-detail-overlay.active, ' +
            '.student-edit-overlay.active, ' +
            '.student-delete-overlay.active'
        );

        if (!activeModal) {
            document.body.style.overflow = '';
        }
    }

    function getInitials(name) {
        if (!name) {
            return '-';
        }

        const words = name
            .trim()
            .split(/\s+/);

        if (words.length === 1) {
            return words[0]
                .substring(0, 2)
                .toUpperCase();
        }

        return (
            words[0][0] +
            words[words.length - 1][0]
        ).toUpperCase();
    }

    function showValue(value) {
        return value && value.trim() !== ''
            ? value
            : '-';
    }

    function genderLabel(gender) {
        if (gender === 'L') {
            return 'Laki-laki';
        }

        if (gender === 'P') {
            return 'Perempuan';
        }

        return '-';
    }

    function statusLabel(status) {
        return status === 'active'
            ? 'Aktif'
            : 'NonAktif';
    }

    function fillDetail(data) {

        document.getElementById('detailStudentAvatar').textContent =
            getInitials(data.name);

        document.getElementById('detailStudentName').textContent =
            showValue(data.name);

        document.getElementById('detailStudentClass').textContent =
            showValue(data.class);

        document.getElementById('detailStudentNis').textContent =
            showValue(data.nis);

        document.getElementById('detailStudentNisn').textContent =
            showValue(data.nisn);

        document.getElementById('detailStudentFullName').textContent =
            showValue(data.name);

        document.getElementById('detailStudentGender').textContent =
            genderLabel(data.gender);

        document.getElementById('detailStudentStatus').textContent =
            statusLabel(data.status);

        document.getElementById('detailStudentClassValue').textContent =
            showValue(data.class);

        document.getElementById('detailStudentEntryYear').textContent =
            showValue(data.entryYear);

        document.getElementById('detailStudentEmail').textContent =
            showValue(data.email);

        document.getElementById('detailStudentGuardian').textContent =
            showValue(data.guardian);

        document.getElementById('detailStudentGuardianEmail').textContent =
            showValue(data.guardianEmail);

        document.getElementById('detailStudentPhone').textContent =
            showValue(data.phone);
    }

    function fillEdit(data) {

        document.getElementById('editStudentId').value =
            data.id || '';

        document.getElementById('editStudentNis').value =
            data.nis || '';

        document.getElementById('editStudentNisn').value =
            data.nisn || '';

        document.getElementById('editStudentName').value =
            data.name || '';

        document.getElementById('editStudentClass').value =
            data.classId || '';

        document.getElementById('editStudentEntryYear').value =
            data.entryYear || '';

        document.getElementById('editStudentGender').value =
            data.gender || 'L';

        document.getElementById('editStudentStatus').value =
            data.status || 'active';

        document.getElementById('editStudentEmail').value =
            data.email || '';

        document.getElementById('editStudentGuardian').value =
            data.guardian || '';

        document.getElementById('editStudentGuardianEmail').value =
            data.guardianEmail || '';

        document.getElementById('editStudentPhone').value =
            data.phone || '';

        if (editForm) {
            editForm.action = data.updateUrl || '';
        }
    }

    openAddButton?.addEventListener('click', function () {
        openModal(addModal);
    });

    addClose?.addEventListener('click', function () {
        closeModal(addModal);
    });

    addCancel?.addEventListener('click', function () {
        closeModal(addModal);
    });

    document
        .querySelectorAll('.action-detail')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                selectedStudent = this.dataset;

                fillDetail(this.dataset);

                openModal(detailModal);
            });

        });

    detailClose?.addEventListener('click', function () {
        closeModal(detailModal);
    });

    detailBtnClose?.addEventListener('click', function () {
        closeModal(detailModal);
    });

    document
        .querySelectorAll('.action-edit')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                selectedStudent = this.dataset;

                fillEdit(this.dataset);

                openModal(editModal);
            });

        });

    detailBtnEdit?.addEventListener('click', function () {

        if (!selectedStudent) {
            return;
        }

        const editButton = document.querySelector(
            '.action-edit[data-id="' +
            selectedStudent.id +
            '"]'
        );

        if (!editButton) {
            return;
        }

        selectedStudent = editButton.dataset;

        closeModal(detailModal);

        fillEdit(editButton.dataset);

        openModal(editModal);
    });

    editClose?.addEventListener('click', function () {
        closeModal(editModal);
    });

    editCancel?.addEventListener('click', function () {
        closeModal(editModal);
    });

    document
        .querySelectorAll('.action-delete')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                const name = this.dataset.name || '-';
                const nis = this.dataset.nis || '-';

                const identity = document.getElementById(
                    'deleteStudentIdentity'
                );

                if (identity) {
                    identity.textContent =
                        name + ' (' + nis + ')';
                }

                if (deleteForm) {
                    deleteForm.action =
                        this.dataset.deleteUrl || '';
                }

                openModal(deleteModal);
            });

        });

    deleteClose?.addEventListener('click', function () {
        closeModal(deleteModal);
    });

    deleteCancel?.addEventListener('click', function () {
        closeModal(deleteModal);
    });

    [
        addModal,
        detailModal,
        editModal,
        deleteModal
    ].forEach(function (modal) {

        modal?.addEventListener('click', function (event) {

            if (event.target === modal) {
                closeModal(modal);
            }

        });

    });

    document.addEventListener('keydown', function (event) {

        if (event.key !== 'Escape') {
            return;
        }

        closeModal(addModal);
        closeModal(detailModal);
        closeModal(editModal);
        closeModal(deleteModal);
    });

});