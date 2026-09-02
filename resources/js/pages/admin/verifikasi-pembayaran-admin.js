document.addEventListener('DOMContentLoaded', function () {

    const paymentProofModal =
        document.getElementById('paymentProofModal');

    const paymentApproveModal =
        document.getElementById('paymentApproveModal');

    const paymentRejectModal =
        document.getElementById('paymentRejectModal');


    const paymentProofClose =
        document.getElementById('paymentProofClose');

    const paymentProofBtnClose =
        document.getElementById('paymentProofBtnClose');

    const paymentProofReject =
        document.getElementById('paymentProofReject');

    const paymentProofApprove =
        document.getElementById('paymentProofApprove');


    const paymentProofTitle =
        document.getElementById('paymentProofTitle');

    const paymentProofBill =
        document.getElementById('paymentProofBill');

    const paymentProofAmount =
        document.getElementById('paymentProofAmount');

    const paymentProofDate =
        document.getElementById('paymentProofDate');

    const paymentProofImage =
        document.getElementById('paymentProofImage');


    const paymentApproveClose =
        document.getElementById('paymentApproveClose');

    const paymentApproveCancel =
        document.getElementById('paymentApproveCancel');

    const paymentApproveForm =
        document.getElementById('paymentApproveForm');

    const approveBillName =
        document.getElementById('approveBillName');

    const approveStudentName =
        document.getElementById('approveStudentName');


    const paymentRejectClose =
        document.getElementById('paymentRejectClose');

    const paymentRejectCancel =
        document.getElementById('paymentRejectCancel');

    const paymentRejectForm =
        document.getElementById('paymentRejectForm');

    const paymentRejectReason =
        document.getElementById('paymentRejectReason');

    const rejectBillName =
        document.getElementById('rejectBillName');

    const rejectStudentName =
        document.getElementById('rejectStudentName');


    let selectedPayment = null;


    function openModal(modal) {

        if (!modal) {
            return;
        }

        closeAllModals();

        modal.classList.add('active');
    }


    function closeModal(modal) {

        if (!modal) {
            return;
        }

        modal.classList.remove('active');
    }


    function closeAllModals() {

        closeModal(paymentProofModal);
        closeModal(paymentApproveModal);
        closeModal(paymentRejectModal);
    }


    function setSelectedPayment(data) {

        selectedPayment = {
            id: data.id,
            student: data.student,
            bill: data.bill,
            amount: data.amount || '',
            date: data.date || '',
            proof: data.proof || '',
        };
    }


    function openApproveModal() {

        if (!selectedPayment) {
            return;
        }

        if (approveBillName) {
            approveBillName.textContent =
                selectedPayment.bill;
        }

        if (approveStudentName) {
            approveStudentName.textContent =
                selectedPayment.student;
        }

        if (paymentApproveForm) {
            paymentApproveForm.action =
                `/admin/payments/${selectedPayment.id}/verify`;
        }

        openModal(paymentApproveModal);
    }


    function openRejectModal() {

        if (!selectedPayment) {
            return;
        }

        if (rejectBillName) {
            rejectBillName.textContent =
                selectedPayment.bill;
        }

        if (rejectStudentName) {
            rejectStudentName.textContent =
                selectedPayment.student;
        }

        if (paymentRejectForm) {
            paymentRejectForm.action =
                `/admin/payments/${selectedPayment.id}/reject`;
        }

        if (paymentRejectReason) {
            paymentRejectReason.value = '';
            paymentRejectReason.style.borderColor = '';
        }

        openModal(paymentRejectModal);
    }


    document
        .querySelectorAll('.btn-view-proof')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    setSelectedPayment({
                        id: button.dataset.paymentId,
                        student: button.dataset.student,
                        bill: button.dataset.bill,
                        amount: button.dataset.amount,
                        date: button.dataset.date,
                        proof: button.dataset.proof,
                    });


                    if (paymentProofTitle) {
                        paymentProofTitle.textContent =
                            `Bukti Pembayaran — ${selectedPayment.student}`;
                    }

                    if (paymentProofBill) {
                        paymentProofBill.textContent =
                            selectedPayment.bill;
                    }

                    if (paymentProofAmount) {
                        paymentProofAmount.textContent =
                            selectedPayment.amount;
                    }

                    if (paymentProofDate) {
                        paymentProofDate.textContent =
                            selectedPayment.date;
                    }

                    if (paymentProofImage) {
                        paymentProofImage.src =
                            selectedPayment.proof;

                        paymentProofImage.alt =
                            `Bukti pembayaran ${selectedPayment.student}`;
                    }


                    openModal(paymentProofModal);
                }
            );

        });


    document
        .querySelectorAll('.btn-approve')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    setSelectedPayment({
                        id: button.dataset.paymentId,
                        student: button.dataset.student,
                        bill: button.dataset.bill,
                    });

                    openApproveModal();
                }
            );

        });


    document
        .querySelectorAll('.btn-reject')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    setSelectedPayment({
                        id: button.dataset.paymentId,
                        student: button.dataset.student,
                        bill: button.dataset.bill,
                    });

                    openRejectModal();
                }
            );

        });


    if (paymentProofApprove) {

        paymentProofApprove.addEventListener(
            'click',
            function () {
                openApproveModal();
            }
        );

    }


    if (paymentProofReject) {

        paymentProofReject.addEventListener(
            'click',
            function () {
                openRejectModal();
            }
        );

    }


    if (paymentProofClose) {

        paymentProofClose.addEventListener(
            'click',
            function () {
                closeModal(paymentProofModal);
            }
        );

    }


    if (paymentProofBtnClose) {

        paymentProofBtnClose.addEventListener(
            'click',
            function () {
                closeModal(paymentProofModal);
            }
        );

    }


    if (paymentApproveClose) {

        paymentApproveClose.addEventListener(
            'click',
            function () {
                closeModal(paymentApproveModal);
            }
        );

    }


    if (paymentApproveCancel) {

        paymentApproveCancel.addEventListener(
            'click',
            function () {
                closeModal(paymentApproveModal);
            }
        );

    }


    if (paymentRejectClose) {

        paymentRejectClose.addEventListener(
            'click',
            function () {
                closeModal(paymentRejectModal);
            }
        );

    }


    if (paymentRejectCancel) {

        paymentRejectCancel.addEventListener(
            'click',
            function () {
                closeModal(paymentRejectModal);
            }
        );

    }


    if (paymentRejectForm) {

        paymentRejectForm.addEventListener(
            'submit',
            function (event) {

                const reason =
                    paymentRejectReason
                        ? paymentRejectReason.value.trim()
                        : '';

                if (reason !== '') {
                    return;
                }

                event.preventDefault();

                if (paymentRejectReason) {

                    paymentRejectReason.style.borderColor =
                        'var(--error)';

                    paymentRejectReason.focus();
                }

            }
        );

    }


    if (paymentRejectReason) {

        paymentRejectReason.addEventListener(
            'input',
            function () {

                if (
                    paymentRejectReason.value.trim() !== ''
                ) {
                    paymentRejectReason.style.borderColor = '';
                }

            }
        );

    }


    if (paymentProofModal) {

        paymentProofModal.addEventListener(
            'click',
            function (event) {

                if (event.target === paymentProofModal) {
                    closeModal(paymentProofModal);
                }

            }
        );

    }


    if (paymentApproveModal) {

        paymentApproveModal.addEventListener(
            'click',
            function (event) {

                if (event.target === paymentApproveModal) {
                    closeModal(paymentApproveModal);
                }

            }
        );

    }


    if (paymentRejectModal) {

        paymentRejectModal.addEventListener(
            'click',
            function (event) {

                if (event.target === paymentRejectModal) {
                    closeModal(paymentRejectModal);
                }

            }
        );

    }


    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {
                closeAllModals();
            }

        }
    );

});