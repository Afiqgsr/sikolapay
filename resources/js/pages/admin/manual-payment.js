document.addEventListener('DOMContentLoaded', function () {

    const studentSelect = document.getElementById('student_id');
    const billSelect = document.getElementById('bill_id');
    const amountDisplay = document.getElementById('paymentAmount');

    if (!studentSelect || !billSelect || !amountDisplay) {
        return;
    }

    const oldStudent = studentSelect.dataset.oldStudent || '';
    const oldBill = billSelect.dataset.oldBill || '';

    const billOptions = Array.from(
        billSelect.querySelectorAll('option[data-student]')
    );

    function formatRupiah(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(value);
    }

    function filterBills() {
        const studentId = studentSelect.value;

        billSelect.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';

        if (!studentId) {
            defaultOption.textContent = 'Pilih siswa terlebih dahulu';

            billSelect.appendChild(defaultOption);
            billSelect.disabled = true;

            amountDisplay.textContent = 'Rp 0';

            return;
        }

        const availableBills = billOptions.filter(function (option) {
            return option.dataset.student === studentId;
        });

        if (availableBills.length === 0) {
            defaultOption.textContent =
                'Tidak ada tagihan yang dapat dibayar';

            billSelect.appendChild(defaultOption);
            billSelect.disabled = true;

            amountDisplay.textContent = 'Rp 0';

            return;
        }

        defaultOption.textContent = 'Pilih Tagihan';

        billSelect.appendChild(defaultOption);

        availableBills.forEach(function (option) {
            billSelect.appendChild(
                option.cloneNode(true)
            );
        });

        billSelect.disabled = false;
        amountDisplay.textContent = 'Rp 0';
    }

    function updateAmount() {
        const selectedOption =
            billSelect.options[billSelect.selectedIndex];

        if (
            !selectedOption ||
            !selectedOption.dataset.amount
        ) {
            amountDisplay.textContent = 'Rp 0';

            return;
        }

        amountDisplay.textContent = formatRupiah(
            Number(selectedOption.dataset.amount)
        );
    }

    studentSelect.addEventListener('change', function () {
        filterBills();
    });

    billSelect.addEventListener('change', function () {
        updateAmount();
    });

    if (oldStudent) {
        studentSelect.value = oldStudent;

        filterBills();

        if (oldBill) {
            billSelect.value = oldBill;

            updateAmount();
        }
    }
});