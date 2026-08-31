document.addEventListener('DOMContentLoaded', function () {

    /* Payment method */

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


    function getMethodText(method) {

        if (method === 'bank') {
            return 'Transfer Bank';
        }

        if (method === 'va') {
            return 'Virtual Account';
        }

        if (method === 'qris') {
            return 'QRIS';
        }

        if (method === 'ewallet') {
            return 'E-Wallet';
        }

        return '-';
    }


    function getMethodId(method) {

        if (method === 'qris') {

            const qrisContent =
                document.getElementById('qrisContent');

            return qrisContent?.dataset.methodId || null;
        }

        const activeItem =
            document.querySelector(
                '#' + method + 'Content .bank-item.active'
            );

        return activeItem?.dataset.methodId || null;
    }


    function updatePaymentMethod(method) {

        const methodText =
            getMethodText(method);

        selectedPaymentMethodId =
            getMethodId(method);

        if (selectedPaymentMethod) {
            selectedPaymentMethod.textContent =
                methodText;
        }

        if (confirmPaymentMethod) {
            confirmPaymentMethod.textContent =
                methodText;
        }

        if (selectedPaymentMethodIdInput) {
            selectedPaymentMethodIdInput.value =
                selectedPaymentMethodId || '';
        }
    }


    paymentMethods.forEach(function (button) {

        button.addEventListener('click', function () {

            if (this.disabled) {
                return;
            }

            const method =
                this.dataset.method;

            paymentMethods.forEach(function (item) {
                item.classList.remove('active');
            });

            this.classList.add('active');

            paymentContents.forEach(function (content) {
                content.style.display = 'none';
            });

            const content =
                document.getElementById(
                    method + 'Content'
                );

            if (content) {
                content.style.display = 'block';
            }

            updatePaymentMethod(method);
        });
    });


    /* Transfer bank */

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

            const accountLabel =
                document.getElementById('accountLabel');

            const accountNumber =
                document.getElementById('accountNumber');

            const accountOwner =
                document.getElementById('accountOwner');

            if (accountLabel) {
                accountLabel.textContent =
                    this.dataset.name || '-';
            }

            if (accountNumber) {
                accountNumber.textContent =
                    this.dataset.accountNumber ||
                    'Menunggu informasi rekening';
            }

            if (accountOwner) {
                accountOwner.textContent =
                    this.dataset.accountName || '-';
            }
        });
    });


    /* Virtual account */

    const vaItems =
        document.querySelectorAll(
            '#vaContent .bank-item'
        );

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

            const label =
                document.getElementById('vaAccountLabel');

            const number =
                document.getElementById('vaAccountNumber');

            const owner =
                document.getElementById('vaAccountOwner');

            if (label) {
                label.textContent =
                    this.dataset.name || '-';
            }

            if (number) {
                number.textContent =
                    this.dataset.accountNumber ||
                    'Menunggu nomor Virtual Account';
            }

            if (owner) {
                owner.textContent =
                    this.dataset.accountName || '-';
            }
        });
    });


    /* E-wallet */

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

            const label =
                document.getElementById('ewalletLabel');

            const number =
                document.getElementById('ewalletNumber');

            const owner =
                document.getElementById('ewalletOwner');

            if (label) {
                label.textContent =
                    this.dataset.name || '-';
            }

            if (number) {
                number.textContent =
                    this.dataset.accountNumber || '-';
            }

            if (owner) {
                owner.textContent =
                    this.dataset.accountName || '-';
            }
        });
    });


    /* Upload */

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

    const uploadPreview =
        document.getElementById('uploadPreview');

    const uploadPreviewImage =
        document.getElementById('uploadPreviewImage');

    let previewUrl = null;


    function resetPreview() {

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }

        if (uploadPreviewImage) {
            uploadPreviewImage.src = '';
        }

        if (uploadPreview) {
            uploadPreview.style.display = 'none';
        }

        if (uploadArea) {
            uploadArea.classList.remove('has-preview');
        }
    }


    function displaySelectedFile(file) {

        if (!file) {
            return false;
        }

        const maxSize =
            5 * 1024 * 1024;

        const allowedTypes = [
            'image/png',
            'image/jpeg',
            'application/pdf'
        ];

        if (file.size > maxSize) {

            alert('Ukuran file maksimal 5MB.');

            proofOfPayment.value = '';

            return false;
        }

        if (!allowedTypes.includes(file.type)) {

            alert(
                'Format file harus PNG, JPG, JPEG, atau PDF.'
            );

            proofOfPayment.value = '';

            return false;
        }

        if (selectedFileName) {
            selectedFileName.textContent =
                file.name;
        }

        if (selectedFile) {
            selectedFile.style.display = 'flex';
        }

        if (uploadArea) {
            uploadArea.classList.add('has-file');
        }

        resetPreview();

        if (file.type.startsWith('image/')) {

            previewUrl =
                URL.createObjectURL(file);

            if (uploadPreviewImage) {
                uploadPreviewImage.src =
                    previewUrl;
            }

            if (uploadPreview) {
                uploadPreview.style.display =
                    'block';
            }

            if (uploadArea) {
                uploadArea.classList.add(
                    'has-preview'
                );
            }
        }

        return true;
    }


    if (uploadButton && proofOfPayment) {

        uploadButton.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();

                proofOfPayment.click();
            }
        );
    }


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


    if (proofOfPayment) {

        proofOfPayment.addEventListener(
            'change',
            function () {

                displaySelectedFile(
                    this.files[0]
                );
            }
        );
    }


    /* Drag & drop */

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

                const file =
                    event.dataTransfer.files[0];

                if (!file) {
                    return;
                }

                const transfer =
                    new DataTransfer();

                transfer.items.add(file);

                proofOfPayment.files =
                    transfer.files;

                proofOfPayment.dispatchEvent(
                    new Event('change', {
                        bubbles: true
                    })
                );
            }
        );
    }


    /* Confirmation modal */

    const openConfirmModal =
        document.getElementById('openConfirmModal');

    const confirmModal =
        document.getElementById('confirmModal');

    const closeConfirmModal =
        document.getElementById('closeConfirmModal');

    const cancelConfirm =
        document.getElementById('cancelConfirm');


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
                    !proofOfPayment.files.length
                ) {

                    alert(
                        'Silakan upload bukti pembayaran terlebih dahulu.'
                    );

                    return;
                }

                confirmModal.style.display = 'flex';

                document.body.style.overflow =
                    'hidden';
            }
        );
    }


    closeConfirmModal?.addEventListener(
        'click',
        closeConfirmation
    );

    cancelConfirm?.addEventListener(
        'click',
        closeConfirmation
    );


    /* Submit payment */

    const submitConfirm =
        document.getElementById('submitConfirm');

    const successModal =
        document.getElementById('successModal');


    if (submitConfirm && successModal) {

        submitConfirm.addEventListener(
            'click',
            async function () {

                const confirmUrl =
                    submitConfirm.dataset.confirmUrl;

                const csrfToken =
                    document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.getAttribute('content');

                if (!selectedPaymentMethodId) {
                    alert(
                        'Metode pembayaran belum dipilih.'
                    );
                    return;
                }

                if (
                    !proofOfPayment ||
                    !proofOfPayment.files.length
                ) {
                    alert(
                        'Bukti pembayaran belum dipilih.'
                    );
                    return;
                }

                if (!confirmUrl || !csrfToken) {
                    alert(
                        'Konfigurasi pembayaran tidak lengkap.'
                    );
                    return;
                }

                submitConfirm.disabled = true;
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

                    if (
                        !response.ok ||
                        !data.success
                    ) {

                        throw new Error(
                            data.message ||
                            'Pembayaran gagal dikonfirmasi.'
                        );
                    }

                    closeConfirmation();

                    const paymentReference =
                        document.getElementById(
                            'paymentReference'
                        );

                    const paymentDate =
                        document.getElementById(
                            'paymentDate'
                        );

                    if (paymentReference) {
                        paymentReference.textContent =
                            '#' +
                            data.payment.payment_number;
                    }

                    if (paymentDate) {
                        paymentDate.textContent =
                            data.payment.date;
                    }

                    successModal.style.display =
                        'flex';

                    document.body.style.overflow =
                        'hidden';

                } catch (error) {

                    console.error(
                        'Payment error:',
                        error
                    );

                    alert(
                        error.message ||
                        'Terjadi kesalahan saat memproses pembayaran.'
                    );

                } finally {

                    submitConfirm.disabled = false;

                    submitConfirm.textContent =
                        'Ya, Konfirmasi';
                }
            }
        );
    }


    /* Success modal */

    const closeSuccessModal =
        document.getElementById('closeSuccessModal');

    const viewHistoryBtn =
        document.getElementById('viewHistoryBtn');


    closeSuccessModal?.addEventListener(
        'click',
        function () {

            successModal.style.display = 'none';

            document.body.style.overflow = '';
        }
    );


    viewHistoryBtn?.addEventListener(
        'click',
        function () {

            const url =
                this.dataset.historyUrl;

            if (url) {
                window.location.href = url;
            }
        }
    );


    /* Overlay */

    confirmModal?.addEventListener(
        'click',
        function (event) {

            if (event.target === confirmModal) {
                closeConfirmation();
            }
        }
    );

    successModal?.addEventListener(
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


    /* Escape */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key !== 'Escape') {
                return;
            }

            if (
                confirmModal?.style.display ===
                'flex'
            ) {
                closeConfirmation();
            }

            if (
                successModal?.style.display ===
                'flex'
            ) {

                successModal.style.display =
                    'none';

                document.body.style.overflow =
                    '';
            }
        }
    );

});