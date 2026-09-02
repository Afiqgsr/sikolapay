document.addEventListener('DOMContentLoaded', () => {

    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');

    const dateInputs = [
        startDate,
        endDate,
    ];


    dateInputs.forEach((input) => {

        if (!input) {
            return;
        }


        // Buka kalender ketika input diklik
        input.addEventListener('click', () => {

            if (typeof input.showPicker === 'function') {

                try {
                    input.showPicker();
                } catch (error) {
                    // Browser tetap dapat menggunakan
                    // date picker bawaan.
                }

            }

        });


        // Buka kalender ketika input mendapat fokus
        input.addEventListener('focus', () => {

            if (typeof input.showPicker === 'function') {

                try {
                    input.showPicker();
                } catch (error) {
                    // Abaikan jika browser
                    // tidak mendukung showPicker.
                }

            }

        });


        // Cegah user mengetik tanggal manual
        input.addEventListener('keydown', (event) => {

            const allowedKeys = [
                'Tab',
                'Escape',
            ];

            if (!allowedKeys.includes(event.key)) {
                event.preventDefault();
            }

        });


        // Cegah paste
        input.addEventListener('paste', (event) => {
            event.preventDefault();
        });


        // Cegah drop text ke input
        input.addEventListener('drop', (event) => {
            event.preventDefault();
        });

    });


    // Tanggal akhir tidak boleh sebelum tanggal awal
    if (startDate && endDate) {

        const updateEndDateMinimum = () => {

            if (startDate.value) {

                endDate.min = startDate.value;

                if (
                    endDate.value
                    && endDate.value < startDate.value
                ) {
                    endDate.value = '';
                }

            } else {

                endDate.removeAttribute('min');

            }

        };


        startDate.addEventListener(
            'change',
            updateEndDateMinimum
        );


        updateEndDateMinimum();
    }

});