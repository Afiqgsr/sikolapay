document.addEventListener('DOMContentLoaded', function () {

    /* PAYMENT METHOD */

    const paymentMethods =
        document.querySelectorAll('.payment-method');

    const paymentContents =
        document.querySelectorAll('.payment-option-content');

    const selectedPaymentMethod =
        document.getElementById('selectedPaymentMethod');

    const confirmPaymentMethod =
        document.getElementById('confirmPaymentMethod');

    const selectedPaymentMethodIdInput =
        document.getElementById('selectedPaymentMethodId');

    let selectedPaymentMethodId =
        selectedPaymentMethodIdInput?.value || null;


    /* PILIH METODE PEMBAYARAN */

    paymentMethods.forEach(function (button) {

        button.addEventListener('click', function () {

            if (this.disabled) {
                return;
            }

            const method = this.dataset.method;

            paymentMethods.forEach(function (item) {
                item.classList.remove('active');
            });

            this.classList.add('active');

            paymentContents.forEach(function (content) {
                content.style.display = 'none';
            });

            const selectedContent =
                document.getElementById(method + 'Content');

            if (selectedContent) {
                selectedContent.style.display = 'block';
            }

            let methodText = '-';

            if (method === 'bank') {
                methodText = 'Transfer Bank';
            } else if (method === 'va') {
                methodText = 'Virtual Account';
            } else if (method === 'qris') {
                methodText = 'QRIS';
            } else if (method === 'ewallet') {
                methodText = 'E-Wallet';
            }

            if (selectedPaymentMethod) {
                selectedPaymentMethod.textContent = methodText;
            }

            if (confirmPaymentMethod) {
                confirmPaymentMethod.textContent = methodText;
            }

            const firstItem =
                document.querySelector(
                    '#' + method + 'Content .bank-item.active'
                );

            if (firstItem) {
                selectedPaymentMethodId =
                    firstItem.dataset.methodId;
            }

            if (method === 'qris') {

                const qrisMethod =
                    document.querySelector(
                        '#qrisContent [data-method-id]'
                    );

                if (qrisMethod) {
                    selectedPaymentMethodId =
                        qrisMethod.dataset.methodId;
                }
            }

            if (selectedPaymentMethodIdInput) {
                selectedPaymentMethodIdInput.value =
                    selectedPaymentMethodId || '';
            }

        });

    });


    /* TRANSFER BANK */

    const bankItems =
        document.querySelectorAll(
            '#bankContent .bank-item'
        );

    bankItems.forEach(function (button) {

        button.addEventListener('click', function () {

            bankItems.forEach(function (item) {
                item.classList.remove('active');
            });

            this.classList.add('active');

            selectedPaymentMethodId =
                this.dataset.methodId;

            if (selectedPaymentMethodIdInput) {
                selectedPaymentMethodIdInput.value =
                    selectedPaymentMethodId;
            }

            const bankName =
                this.dataset.name;

            const accountNumber =
                this.dataset.accountNumber;

            const accountName =
                this.dataset.accountName;

            const accountLabel =
                document.getElementById('accountLabel');

            const accountNumberElement =
                document.getElementById('accountNumber');

            const accountOwner =
                document.getElementById('accountOwner');

            if (accountLabel) {
                accountLabel.textContent = bankName;
            }

            if (accountNumberElement) {
                accountNumberElement.textContent =
                    accountNumber ||
                    'Menunggu informasi rekening';
            }

            if (accountOwner) {
                accountOwner.textContent =
                    accountName || '-';
            }

        });

    });


    /* VIRTUAL ACCOUNT */

    const vaItems =
        document.querySelectorAll(
            '#vaContent .bank-item'
        );

    const vaAccountLabel =
        document.getElementById('vaAccountLabel');

    const vaAccountNumber =
        document.getElementById('vaAccountNumber');

    const vaAccountOwner =
        document.getElementById('vaAccountOwner');

    vaItems.forEach(function (button) {

        button.addEventListener('click', function () {

            vaItems.forEach(function (item) {
                item.classList.remove('active');
            });

            this.classList.add('active');

            selectedPaymentMethodId =
                this.dataset.methodId;

            if (selectedPaymentMethodIdInput) {
                selectedPaymentMethodIdInput.value =
                    selectedPaymentMethodId;
            }

            const bankName =
                this.dataset.name;

            const accountNumber =
                this.dataset.accountNumber;

            const accountName =
                this.dataset.accountName;

            if (vaAccountLabel) {
                vaAccountLabel.textContent = bankName;
            }

            if (vaAccountNumber) {
                vaAccountNumber.textContent =
                    accountNumber ||
                    'Menunggu nomor Virtual Account';
            }

            if (vaAccountOwner) {
                vaAccountOwner.textContent =
                    accountName ||
                    '-';
            }

        });

    });


    /* E-WALLET */

    const ewalletItems =
        document.querySelectorAll(
            '#ewalletContent .bank-item'
        );

    ewalletItems.forEach(function (button) {

        button.addEventListener('click', function () {

            ewalletItems.forEach(function (item) {
                item.classList.remove('active');
            });

            this.classList.add('active');

            selectedPaymentMethodId =
                this.dataset.methodId;

            if (selectedPaymentMethodIdInput) {
                selectedPaymentMethodIdInput.value =
                    selectedPaymentMethodId;
            }

            const ewalletName =
                this.dataset.name;

            const accountNumber =
                this.dataset.accountNumber;

            const accountName =
                this.dataset.accountName;

            const ewalletLabel =
                document.getElementById('ewalletLabel');

            const ewalletNumber =
                document.getElementById('ewalletNumber');

            const ewalletOwner =
                document.getElementById('ewalletOwner');

            if (ewalletLabel) {
                ewalletLabel.textContent =
                    ewalletName;
            }

            if (ewalletNumber) {
                ewalletNumber.textContent =
                    accountNumber || '-';
            }

            if (ewalletOwner) {
                ewalletOwner.textContent =
                    accountName || '-';
            }

        });

    });


    /* DEFAULT PAYMENT METHOD */

    if (!selectedPaymentMethodId) {

        const activeBank =
            document.querySelector(
                '#bankContent .bank-item.active'
            );

        const activeVA =
            document.querySelector(
                '#vaContent .bank-item.active'
            );

        const activeEwallet =
            document.querySelector(
                '#ewalletContent .bank-item.active'
            );

        const activeQris =
            document.querySelector(
                '#qrisContent [data-method-id]'
            );

        if (activeBank) {

            selectedPaymentMethodId =
                activeBank.dataset.methodId;

        } else if (activeVA) {

            selectedPaymentMethodId =
                activeVA.dataset.methodId;

        } else if (activeEwallet) {

            selectedPaymentMethodId =
                activeEwallet.dataset.methodId;

        } else if (activeQris) {

            selectedPaymentMethodId =
                activeQris.dataset.methodId;

        }

        if (selectedPaymentMethodIdInput) {
            selectedPaymentMethodIdInput.value =
                selectedPaymentMethodId || '';
        }

    }


    /* UPLOAD BUKTI PEMBAYARAN */

    const uploadArea =
        document.getElementById('uploadArea');

    const uploadButton =
        document.getElementById('uploadButton');

    const proofOfPayment =
        document.getElementById('proofOfPayment');

    const selectedFile =
        document.getElementById('selectedFile');

    const selectedFileName =
        document.getElementById('selectedFileName');


    /* BUKA FILE PICKER */

    if (uploadButton && proofOfPayment) {

        uploadButton.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();

                proofOfPayment.click();

            }
        );

    }


    /* KLIK AREA UPLOAD */

    if (uploadArea && proofOfPayment) {

        uploadArea.addEventListener(
            'click',
            function (event) {

                if (
                    event.target === uploadButton ||
                    uploadButton?.contains(event.target)
                ) {
                    return;
                }

                proofOfPayment.click();

            }
        );

    }


    /* TAMPILKAN FILE */

    function displaySelectedFile(file) {

        if (!file) {
            return false;
        }

        const maxSize =
            5 * 1024 * 1024;

        if (file.size > maxSize) {

            alert(
                'Ukuran file maksimal 5MB.'
            );

            if (proofOfPayment) {
                proofOfPayment.value = '';
            }

            return false;
        }

        const allowedTypes = [
            'image/png',
            'image/jpeg',
            'application/pdf'
        ];

        if (!allowedTypes.includes(file.type)) {

            alert(
                'Format file harus PNG, JPG, JPEG, atau PDF.'
            );

            if (proofOfPayment) {
                proofOfPayment.value = '';
            }

            return false;
        }

        if (selectedFileName) {

            selectedFileName.textContent =
                file.name;

        }

        if (selectedFile) {

            selectedFile.style.display =
                'flex';

        }

        if (uploadArea) {

            uploadArea.classList.add(
                'has-file'
            );

        }

        return true;

    }


    /* FILE DIPILIH */

    if (proofOfPayment) {

        proofOfPayment.addEventListener(
            'change',
            function () {

                const file =
                    this.files[0];

                displaySelectedFile(file);

            }
        );

    }


    /* DRAG & DROP */

    if (uploadArea && proofOfPayment) {

        uploadArea.addEventListener(
            'dragover',
            function (event) {

                event.preventDefault();

                uploadArea.classList.add(
                    'drag-over'
                );

            }
        );

        uploadArea.addEventListener(
            'dragleave',
            function () {

                uploadArea.classList.remove(
                    'drag-over'
                );

            }
        );

        uploadArea.addEventListener(
            'drop',
            function (event) {

                event.preventDefault();

                uploadArea.classList.remove(
                    'drag-over'
                );

                const files =
                    event.dataTransfer.files;

                if (!files.length) {
                    return;
                }

                try {

                    const dataTransfer =
                        new DataTransfer();

                    dataTransfer.items.add(
                        files[0]
                    );

                    proofOfPayment.files =
                        dataTransfer.files;

                    proofOfPayment.dispatchEvent(
                        new Event(
                            'change',
                            {
                                bubbles: true
                            }
                        )
                    );

                } catch (error) {

                    console.error(
                        'Upload error:',
                        error
                    );

                }

            }
        );

    }


    /* MODAL KONFIRMASI */

    const openConfirmModal =
        document.getElementById('openConfirmModal');

    const closeConfirmModal =
        document.getElementById('closeConfirmModal');

    const cancelConfirm =
        document.getElementById('cancelConfirm');

    const confirmModal =
        document.getElementById('confirmModal');


    function closeConfirmation() {

        if (confirmModal) {
            confirmModal.style.display = 'none';
        }

        document.body.style.overflow = '';

    }


    if (openConfirmModal && confirmModal) {

        openConfirmModal.addEventListener(
            'click',
            function () {

                if (!selectedPaymentMethodId) {

                    alert(
                        'Silakan pilih metode pembayaran terlebih dahulu.'
                    );

                    return;
                }

                if (
                    !proofOfPayment ||
                    !proofOfPayment.files ||
                    !proofOfPayment.files.length
                ) {

                    alert(
                        'Silakan upload bukti pembayaran terlebih dahulu.'
                    );

                    return;
                }

                confirmModal.style.display =
                    'flex';

                document.body.style.overflow =
                    'hidden';

            }
        );

    }


    if (closeConfirmModal) {

        closeConfirmModal.addEventListener(
            'click',
            closeConfirmation
        );

    }


    if (cancelConfirm) {

        cancelConfirm.addEventListener(
            'click',
            closeConfirmation
        );

    }


    /* KONFIRMASI PEMBAYARAN */

    const submitConfirm =
        document.getElementById('submitConfirm');

    const successModal =
        document.getElementById('successModal');


    if (submitConfirm && successModal) {

        submitConfirm.addEventListener(
            'click',
            async function () {

                if (!selectedPaymentMethodId) {

                    alert(
                        'Silakan pilih metode pembayaran terlebih dahulu.'
                    );

                    return;
                }

                if (
                    !proofOfPayment ||
                    !proofOfPayment.files ||
                    !proofOfPayment.files.length
                ) {

                    alert(
                        'Silakan upload bukti pembayaran terlebih dahulu.'
                    );

                    closeConfirmation();

                    return;
                }

                const confirmUrl =
                    submitConfirm.dataset.confirmUrl;

                const csrfToken =
                    document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.getAttribute('content');


                if (!confirmUrl) {

                    alert(
                        'URL konfirmasi pembayaran tidak ditemukan.'
                    );

                    return;
                }


                if (!csrfToken) {

                    alert(
                        'CSRF token tidak ditemukan.'
                    );

                    return;
                }


                submitConfirm.disabled =
                    true;

                submitConfirm.textContent =
                    'Memproses...';


                try {

                    const formData =
                        new FormData();

                    formData.append(
                        'payment_method_id',
                        selectedPaymentMethodId
                    );

                    formData.append(
                        'proof_of_payment',
                        proofOfPayment.files[0]
                    );


                    const response =
                        await fetch(
                            confirmUrl,
                            {
                                method: 'POST',

                                headers: {
                                    'Accept':
                                        'application/json',

                                    'X-CSRF-TOKEN':
                                        csrfToken,

                                    'X-Requested-With':
                                        'XMLHttpRequest'
                                },

                                body: formData
                            }
                        );


                    const data =
                        await response.json();


                    if (!response.ok || !data.success) {

                        throw new Error(
                            data.message ||
                            'Pembayaran gagal dikonfirmasi.'
                        );

                    }


                    closeConfirmation();


                    /* UPDATE NOMOR REFERENSI */

                    const paymentReference =
                        document.getElementById(
                            'paymentReference'
                        );

                    if (paymentReference) {

                        paymentReference.textContent =
                            '#' +
                            data.payment.payment_number;

                    }


                    /* UPDATE TANGGAL */

                    const paymentDate =
                        document.getElementById(
                            'paymentDate'
                        );

                    if (paymentDate) {

                        paymentDate.textContent =
                            data.payment.date;

                    }


                    /* TAMPILKAN SUCCESS */

                    successModal.style.display =
                        'flex';

                    document.body.style.overflow =
                        'hidden';

                }

                catch (error) {

                    console.error(
                        'Payment error:',
                        error
                    );

                    alert(
                        error.message ||
                        'Terjadi kesalahan saat memproses pembayaran.'
                    );

                }

                finally {

                    submitConfirm.disabled =
                        false;

                    submitConfirm.textContent =
                        'Ya, Konfirmasi';

                }

            }
        );

    }


    /* TUTUP SUCCESS MODAL */

    const closeSuccessModal =
        document.getElementById(
            'closeSuccessModal'
        );

    if (closeSuccessModal) {

        closeSuccessModal.addEventListener(
            'click',
            function () {

                if (successModal) {
                    successModal.style.display =
                        'none';
                }

                document.body.style.overflow =
                    '';

            }
        );

    }


    /* LIHAT RIWAYAT */

    const viewHistoryBtn =
        document.getElementById(
            'viewHistoryBtn'
        );

    if (viewHistoryBtn) {

        viewHistoryBtn.addEventListener(
            'click',
            function () {

                const historyUrl =
                    this.dataset.historyUrl;

                if (historyUrl) {

                    window.location.href =
                        historyUrl;

                }

            }
        );

    }


    /* KLIK OVERLAY */

    if (confirmModal) {

        confirmModal.addEventListener(
            'click',
            function (event) {

                if (event.target === confirmModal) {

                    closeConfirmation();

                }

            }
        );

    }


    if (successModal) {

        successModal.addEventListener(
            'click',
            function (event) {

                if (event.target === successModal) {

                    successModal.style.display =
                        'none';

                    document.body.style.overflow =
                        '';

                }

            }
        );

    }


    /* ESCAPE */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key !== 'Escape') {
                return;
            }

            if (
                confirmModal &&
                confirmModal.style.display === 'flex'
            ) {

                closeConfirmation();

            }

            if (
                successModal &&
                successModal.style.display === 'flex'
            ) {

                successModal.style.display =
                    'none';

                document.body.style.overflow =
                    '';

            }

        }
    );

});