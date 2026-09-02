document.addEventListener('DOMContentLoaded', function () {

    const paymentForm =
        document.getElementById('guardianPaymentForm');

    if (!paymentForm) {
        return;
    }


    /* PAYMENT METHOD */

    const paymentMethodButtons =
        document.querySelectorAll('.payment-method');

    const selectedPaymentMethodId =
        document.getElementById('selectedPaymentMethodId');

    const selectedPaymentMethod =
        document.getElementById('selectedPaymentMethod');

    const confirmPaymentMethod =
        document.getElementById('confirmPaymentMethod');


    const paymentContents = {
        bank: document.getElementById('bankContent'),
        va: document.getElementById('vaContent'),
        qris: document.getElementById('qrisContent'),
        ewallet: document.getElementById('ewalletContent'),
    };


    function hidePaymentContents() {

        Object.values(paymentContents).forEach(function (content) {

            if (content) {
                content.style.display = 'none';
            }

        });

    }


    function setSummaryMethod(name) {

        if (selectedPaymentMethod) {
            selectedPaymentMethod.textContent = name;
        }

        if (confirmPaymentMethod) {
            confirmPaymentMethod.textContent = name;
        }

    }


    function selectMethodId(id) {

        if (!selectedPaymentMethodId) {
            return;
        }

        selectedPaymentMethodId.value = id || '';
    }


    function selectFirstOption(content, fallbackName) {

        if (!content) {
            return;
        }

        const firstItem =
            content.querySelector('.bank-item');

        if (!firstItem) {
            return;
        }

        content
            .querySelectorAll('.bank-item')
            .forEach(function (item) {
                item.classList.remove('active');
            });

        firstItem.classList.add('active');

        selectMethodId(
            firstItem.dataset.methodId
        );

        setSummaryMethod(
            firstItem.dataset.name
            || firstItem.textContent.trim()
            || fallbackName
        );

        updateAccountInformation(
            content.id,
            firstItem
        );
    }


    paymentMethodButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            if (button.disabled) {
                return;
            }


            paymentMethodButtons.forEach(function (item) {
                item.classList.remove('active');
            });

            button.classList.add('active');


            hidePaymentContents();


            const method =
                button.dataset.method;

            const content =
                paymentContents[method];


            if (content) {
                content.style.display = 'block';
            }


            if (method === 'bank') {

                selectFirstOption(
                    content,
                    'Transfer Bank'
                );

            } else if (method === 'va') {

                selectFirstOption(
                    content,
                    'Virtual Account'
                );

            } else if (method === 'ewallet') {

                selectFirstOption(
                    content,
                    'E-Wallet'
                );

            } else if (method === 'qris') {

                selectMethodId(
                    button.dataset.methodId
                );

                setSummaryMethod('QRIS');
            }

        });

    });


    /* ACCOUNT INFORMATION */

    function updateAccountInformation(
        contentId,
        button
    ) {

        const accountNumber =
            button.dataset.accountNumber || '-';

        const accountName =
            button.dataset.accountName || '-';

        const methodName =
            button.dataset.name
            || button.textContent.trim();


        if (contentId === 'bankContent') {

            const label =
                document.getElementById('accountLabel');

            const number =
                document.getElementById('accountNumber');

            const owner =
                document.getElementById('accountOwner');


            if (label) {
                label.textContent = methodName;
            }

            if (number) {
                number.textContent = accountNumber;
            }

            if (owner) {
                owner.textContent = accountName;
            }

        }


        if (contentId === 'vaContent') {

            const label =
                document.getElementById('vaAccountLabel');

            const number =
                document.getElementById('vaAccountNumber');

            const owner =
                document.getElementById('vaAccountOwner');


            if (label) {
                label.textContent = methodName;
            }

            if (number) {
                number.textContent = accountNumber;
            }

            if (owner) {
                owner.textContent = accountName;
            }

        }


        if (contentId === 'ewalletContent') {

            const label =
                document.getElementById('ewalletLabel');

            const number =
                document.getElementById('ewalletNumber');

            const owner =
                document.getElementById('ewalletOwner');


            if (label) {
                label.textContent = methodName;
            }

            if (number) {
                number.textContent = accountNumber;
            }

            if (owner) {
                owner.textContent = accountName;
            }

        }
    }


    document
        .querySelectorAll('.payment-option-content .bank-item')
        .forEach(function (button) {

            button.addEventListener('click', function () {

                const content =
                    button.closest(
                        '.payment-option-content'
                    );

                if (!content) {
                    return;
                }


                content
                    .querySelectorAll('.bank-item')
                    .forEach(function (item) {
                        item.classList.remove('active');
                    });


                button.classList.add('active');


                selectMethodId(
                    button.dataset.methodId
                );


                const methodName =
                    button.dataset.name
                    || button.textContent.trim();


                setSummaryMethod(methodName);


                updateAccountInformation(
                    content.id,
                    button
                );

            });

        });


    /* INITIAL METHOD */

    const activeMethodButton =
        document.querySelector(
            '.payment-method.active:not(:disabled)'
        );


    if (activeMethodButton) {

        const method =
            activeMethodButton.dataset.method;

        const content =
            paymentContents[method];


        hidePaymentContents();


        if (content) {
            content.style.display = 'block';
        }


        if (
            method === 'bank'
            || method === 'va'
            || method === 'ewallet'
        ) {

            const activeItem =
                content?.querySelector(
                    '.bank-item.active'
                );


            if (activeItem) {

                selectMethodId(
                    activeItem.dataset.methodId
                );

                setSummaryMethod(
                    activeItem.dataset.name
                    || activeItem.textContent.trim()
                );

                updateAccountInformation(
                    content.id,
                    activeItem
                );

            }

        }


        if (method === 'qris') {

            selectMethodId(
                activeMethodButton.dataset.methodId
            );

            setSummaryMethod('QRIS');
        }
    }


    /* UPLOAD */

    const uploadArea =
        document.getElementById('uploadArea');

    const uploadButton =
        document.getElementById('uploadButton');

    const proofInput =
        document.getElementById('proofOfPayment');

    const uploadContent =
        document.getElementById('uploadContent');

    const selectedFile =
        document.getElementById('selectedFile');

    const selectedFileName =
        document.getElementById('selectedFileName');

    const removeSelectedFile =
        document.getElementById('removeSelectedFile');


    const maxFileSize =
        2 * 1024 * 1024;


    const validFileTypes = [
        'image/jpeg',
        'image/png',
    ];


    function validateFile(file) {

        if (!file) {
            return false;
        }


        if (!validFileTypes.includes(file.type)) {

            alert(
                'Format file harus JPG, JPEG, atau PNG.'
            );

            return false;
        }


        if (file.size > maxFileSize) {

            alert(
                'Ukuran file maksimal 2 MB.'
            );

            return false;
        }


        return true;
    }


    function showSelectedFile(file) {

        if (!file) {
            return;
        }


        if (selectedFileName) {
            selectedFileName.textContent =
                file.name;
        }


        if (selectedFile) {
            selectedFile.style.display = 'flex';
        }


        if (uploadContent) {
            uploadContent.style.display = 'none';
        }


        if (uploadArea) {
            uploadArea.classList.add('has-file');
        }
    }


    function clearSelectedFile() {

        if (proofInput) {
            proofInput.value = '';
        }


        if (selectedFileName) {
            selectedFileName.textContent = '';
        }


        if (selectedFile) {
            selectedFile.style.display = 'none';
        }


        if (uploadContent) {
            uploadContent.style.display = '';
        }


        if (uploadArea) {
            uploadArea.classList.remove(
                'has-file'
            );
        }
    }


    if (
        uploadButton
        && proofInput
    ) {

        uploadButton.addEventListener(
            'click',
            function (event) {

                event.stopPropagation();

                proofInput.click();
            }
        );
    }


    if (uploadArea && proofInput) {

        uploadArea.addEventListener(
            'click',
            function (event) {

                if (
                    event.target.closest(
                        '#uploadButton'
                    )
                    || event.target.closest(
                        '#removeSelectedFile'
                    )
                ) {
                    return;
                }

                proofInput.click();
            }
        );
    }


    if (proofInput) {

        proofInput.addEventListener(
            'change',
            function () {

                const file =
                    proofInput.files[0];

                if (!file) {
                    return;
                }


                if (!validateFile(file)) {

                    clearSelectedFile();

                    return;
                }


                showSelectedFile(file);
            }
        );
    }


    if (removeSelectedFile) {

        removeSelectedFile.addEventListener(
            'click',
            function (event) {

                event.preventDefault();
                event.stopPropagation();

                clearSelectedFile();
            }
        );
    }


    /* DRAG AND DROP */

    if (uploadArea && proofInput) {

        [
            'dragenter',
            'dragover',
        ].forEach(function (eventName) {

            uploadArea.addEventListener(
                eventName,
                function (event) {

                    event.preventDefault();
                    event.stopPropagation();

                    uploadArea.classList.add(
                        'drag-over'
                    );
                }
            );

        });


        [
            'dragleave',
            'drop',
        ].forEach(function (eventName) {

            uploadArea.addEventListener(
                eventName,
                function (event) {

                    event.preventDefault();
                    event.stopPropagation();

                    uploadArea.classList.remove(
                        'drag-over'
                    );
                }
            );

        });


        uploadArea.addEventListener(
            'drop',
            function (event) {

                const files =
                    event.dataTransfer.files;

                if (!files.length) {
                    return;
                }


                const file = files[0];


                if (!validateFile(file)) {
                    return;
                }


                const dataTransfer =
                    new DataTransfer();

                dataTransfer.items.add(file);

                proofInput.files =
                    dataTransfer.files;


                showSelectedFile(file);
            }
        );
    }


    /* MODAL */

    const openConfirmModal =
        document.getElementById(
            'openConfirmModal'
        );

    const confirmModal =
        document.getElementById(
            'confirmModal'
        );

    const closeConfirmModal =
        document.getElementById(
            'closeConfirmModal'
        );

    const cancelConfirm =
        document.getElementById(
            'cancelConfirm'
        );

    const submitConfirm =
        document.getElementById(
            'submitConfirm'
        );


    function openModal() {

        if (!selectedPaymentMethodId?.value) {

            alert(
                'Silakan pilih metode pembayaran.'
            );

            return;
        }


        if (
            !proofInput
            || proofInput.files.length === 0
        ) {

            alert(
                'Silakan upload bukti pembayaran terlebih dahulu.'
            );

            return;
        }


        confirmModal.style.display = 'flex';
    }


    function closeModal() {

        if (confirmModal) {
            confirmModal.style.display = 'none';
        }
    }


    if (
        openConfirmModal
        && confirmModal
    ) {

        openConfirmModal.addEventListener(
            'click',
            openModal
        );
    }


    if (closeConfirmModal) {

        closeConfirmModal.addEventListener(
            'click',
            closeModal
        );
    }


    if (cancelConfirm) {

        cancelConfirm.addEventListener(
            'click',
            closeModal
        );
    }


    if (confirmModal) {

        confirmModal.addEventListener(
            'click',
            function (event) {

                if (event.target === confirmModal) {
                    closeModal();
                }
            }
        );
    }


    /* SUBMIT PAYMENT */

    if (submitConfirm) {

        submitConfirm.addEventListener(
            'click',
            function () {

                if (!selectedPaymentMethodId?.value) {

                    alert(
                        'Metode pembayaran belum dipilih.'
                    );

                    return;
                }


                if (
                    !proofInput
                    || proofInput.files.length === 0
                ) {

                    alert(
                        'Bukti pembayaran belum diupload.'
                    );

                    return;
                }


                submitConfirm.disabled = true;

                submitConfirm.textContent =
                    'Memproses...';


                paymentForm.submit();
            }
        );
    }


    /* ESC CLOSE MODAL */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
                && confirmModal
                && confirmModal.style.display === 'flex'
            ) {
                closeModal();
            }

        }
    );

});