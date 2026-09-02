document.addEventListener('DOMContentLoaded', function () {

    const targetDataElement =
        document.getElementById('billingTargetData');

    const detailDataElement =
        document.getElementById('billingDetailData');

    const targetData = targetDataElement
        ? JSON.parse(targetDataElement.textContent)
        : {
            students: [],
            classes: [],
            cohorts: []
        };

    const detailData = detailDataElement
        ? JSON.parse(detailDataElement.textContent)
        : {};

    const addModal =
        document.getElementById('addBillingModal');

    const detailModal =
        document.getElementById('billingDetailModal');

    const editModal =
        document.getElementById('editBillingModal');

    const deleteModal =
        document.getElementById('deleteBillingModal');

    let selectedBatchId = null;

    function openModal(modal) {
        if (!modal) return;

        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        if (!modal) return;

        modal.classList.remove('active');

        const activeModal = document.querySelector(
            '.billing-add-overlay.active, ' +
            '.billing-modal-overlay.active, ' +
            '.billing-edit-overlay.active, ' +
            '.billing-delete-overlay.active'
        );

        if (!activeModal) {
            document.body.style.overflow = '';
        }
    }

    function rupiah(value) {
        return 'Rp ' + new Intl.NumberFormat(
            'id-ID'
        ).format(Number(value || 0));
    }

    function createOption(
        select,
        value,
        text
    ) {
        const option =
            document.createElement('option');

        option.value = value;
        option.textContent = text;

        select.appendChild(option);
    }

    function populateTarget(
        type,
        select,
        field,
        selectedValue = ''
    ) {
        if (!select || !field) {
            return;
        }

        select.innerHTML = '';

        if (type === 'school') {
            field.style.display = 'none';

            select.required = false;

            return;
        }

        field.style.display = '';
        select.required = true;

        createOption(
            select,
            '',
            '-- Pilih Target --'
        );

        if (type === 'class') {

            targetData.classes.forEach(item => {

                createOption(
                    select,
                    item.id,
                    item.name
                );

            });

        } else if (type === 'cohort') {

            targetData.cohorts.forEach(year => {

                createOption(
                    select,
                    year,
                    'Angkatan ' + year
                );

            });

        } else if (type === 'student') {

            targetData.students.forEach(item => {

                createOption(
                    select,
                    item.id,
                    item.name +
                    ' - ' +
                    (item.class || '-')
                );

            });

        }

        if (selectedValue !== '') {
            select.value =
                String(selectedValue);
        }
    }

    /* Tambah */

    const openAddButton =
        document.getElementById(
            'openAddBillingModal'
        );

    const addClose =
        document.getElementById(
            'addBillingClose'
        );

    const addCancel =
        document.getElementById(
            'addBillingCancel'
        );

    const addTargetType =
        document.getElementById(
            'billingTargetType'
        );

    const addTargetValue =
        document.getElementById(
            'billingTargetValue'
        );

    const addTargetField =
        document.getElementById(
            'billingTargetValueField'
        );

    openAddButton?.addEventListener(
        'click',
        function () {
            openModal(addModal);
        }
    );

    addClose?.addEventListener(
        'click',
        function () {
            closeModal(addModal);
        }
    );

    addCancel?.addEventListener(
        'click',
        function () {
            closeModal(addModal);
        }
    );

    addTargetType?.addEventListener(
        'change',
        function () {

            populateTarget(
                this.value,
                addTargetValue,
                addTargetField
            );

        }
    );

    if (addTargetType?.value) {

        populateTarget(
            addTargetType.value,
            addTargetValue,
            addTargetField,
            addTargetValue.dataset.oldValue || ''
        );

    }

    /* Detail */

    const detailClose =
        document.getElementById(
            'billingDetailClose'
        );

    const detailBtnClose =
        document.getElementById(
            'billingDetailBtnClose'
        );

    const detailBtnEdit =
        document.getElementById(
            'billingDetailBtnEdit'
        );

    function statusLabel(status) {

        switch (status) {

            case 'paid':
                return 'Lunas';

            case 'pending':
                return 'Menunggu';

            case 'overdue':
                return 'Terlambat';

            default:
                return 'Belum Lunas';
        }
    }

    function statusClass(status) {

        switch (status) {

            case 'paid':
                return 'billing-status-paid';

            case 'pending':
                return 'billing-status-pending';

            case 'overdue':
                return 'billing-status-overdue';

            default:
                return 'billing-status-unpaid';
        }
    }

    function fillDetail(id) {

        const data =
            detailData[id];

        if (!data) {
            return;
        }

        document.getElementById(
            'detailBillingName'
        ).textContent =
            data.name || '-';

        document.getElementById(
            'detailBillingTarget'
        ).textContent =
            data.target || '-';

        document.getElementById(
            'detailBillingSemester'
        ).textContent =
            data.semester || '-';

        document.getElementById(
            'detailBillingAmount'
        ).textContent =
            rupiah(data.amount);

        document.getElementById(
            'detailBillingDueDate'
        ).textContent =
            data.due_date || '-';

        document.getElementById(
            'detailBillingDescription'
        ).textContent =
            data.description || '-';

        const body =
            document.getElementById(
                'billingRecipientBody'
            );

        body.innerHTML = '';

        data.recipients.forEach(
            recipient => {

                const row =
                    document.createElement('tr');

                const name =
                    document.createElement('td');

                const classCell =
                    document.createElement('td');

                const statusCell =
                    document.createElement('td');

                const badge =
                    document.createElement('span');

                name.textContent =
                    recipient.name;

                classCell.textContent =
                    recipient.class;

                badge.textContent =
                    statusLabel(
                        recipient.status
                    );

                badge.className =
                    'billing-status ' +
                    statusClass(
                        recipient.status
                    );

                statusCell.appendChild(
                    badge
                );

                row.appendChild(name);
                row.appendChild(classCell);
                row.appendChild(statusCell);

                body.appendChild(row);
            }
        );
    }

    document
        .querySelectorAll(
            '.billing-action-detail'
        )
        .forEach(button => {

            button.addEventListener(
                'click',
                function () {

                    selectedBatchId =
                        this.dataset.id;

                    fillDetail(
                        selectedBatchId
                    );

                    openModal(
                        detailModal
                    );
                }
            );

        });

    detailClose?.addEventListener(
        'click',
        function () {
            closeModal(detailModal);
        }
    );

    detailBtnClose?.addEventListener(
        'click',
        function () {
            closeModal(detailModal);
        }
    );

    /* Edit */

    const editForm =
        document.getElementById(
            'editBillingForm'
        );

    const editClose =
        document.getElementById(
            'editBillingClose'
        );

    const editCancel =
        document.getElementById(
            'editBillingCancel'
        );

    const editTargetType =
        document.getElementById(
            'editBillingTargetType'
        );

    const editTargetValue =
        document.getElementById(
            'editBillingTargetValue'
        );

    const editTargetField =
        document.getElementById(
            'editBillingTargetValueField'
        );

    function fillEdit(data) {

        editTargetType.value =
            data.targetType || 'class';

        populateTarget(
            data.targetType,
            editTargetValue,
            editTargetField,
            data.targetValue || ''
        );

        document.getElementById(
            'editBillingName'
        ).value =
            data.name || '';

        document.getElementById(
            'editBillingSemester'
        ).value =
            data.semester || '';

        document.getElementById(
            'editBillingAmount'
        ).value =
            data.amount || '';

        document.getElementById(
            'editBillingDueDate'
        ).value =
            data.dueDate || '';

        document.getElementById(
            'editBillingDescription'
        ).value =
            data.description || '';

        editForm.action =
            data.updateUrl || '';
    }

    document
        .querySelectorAll(
            '.billing-action-edit'
        )
        .forEach(button => {

            button.addEventListener(
                'click',
                function () {

                    selectedBatchId =
                        this.dataset.id;

                    fillEdit(
                        this.dataset
                    );

                    openModal(
                        editModal
                    );
                }
            );

        });

    editTargetType?.addEventListener(
        'change',
        function () {

            populateTarget(
                this.value,
                editTargetValue,
                editTargetField
            );

        }
    );

    detailBtnEdit?.addEventListener(
        'click',
        function () {

            if (!selectedBatchId) {
                return;
            }

            const button =
                document.querySelector(
                    '.billing-action-edit[data-id="' +
                    selectedBatchId +
                    '"]'
                );

            if (!button) {
                return;
            }

            closeModal(detailModal);

            fillEdit(button.dataset);

            openModal(editModal);
        }
    );

    editClose?.addEventListener(
        'click',
        function () {
            closeModal(editModal);
        }
    );

    editCancel?.addEventListener(
        'click',
        function () {
            closeModal(editModal);
        }
    );

    /* Hapus */

    const deleteForm =
        document.getElementById(
            'deleteBillingForm'
        );

    const deleteClose =
        document.getElementById(
            'deleteBillingClose'
        );

    const deleteCancel =
        document.getElementById(
            'deleteBillingCancel'
        );

    document
        .querySelectorAll(
            '.billing-action-delete'
        )
        .forEach(button => {

            button.addEventListener(
                'click',
                function () {

                    document.getElementById(
                        'deleteBillingName'
                    ).textContent =
                        this.dataset.name || '-';

                    document.getElementById(
                        'deleteBillingTarget'
                    ).textContent =
                        this.dataset.target || '-';

                    deleteForm.action =
                        this.dataset.deleteUrl
                        || '';

                    openModal(
                        deleteModal
                    );
                }
            );

        });

    deleteClose?.addEventListener(
        'click',
        function () {
            closeModal(deleteModal);
        }
    );

    deleteCancel?.addEventListener(
        'click',
        function () {
            closeModal(deleteModal);
        }
    );

    /* Klik overlay */

    [
        addModal,
        detailModal,
        editModal,
        deleteModal
    ].forEach(modal => {

        modal?.addEventListener(
            'click',
            function (event) {

                if (event.target === modal) {
                    closeModal(modal);
                }

            }
        );

    });

    /* Escape */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key !== 'Escape') {
                return;
            }

            closeModal(addModal);
            closeModal(detailModal);
            closeModal(editModal);
            closeModal(deleteModal);
        }
    );

    if (
        addModal?.classList.contains(
            'active'
        )
    ) {
        document.body.style.overflow =
            'hidden';
    }

});