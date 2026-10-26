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

    /* ==========================================================================
       Custom Searchable Dropdown for Student Select (Progressive Enhancement)
       ========================================================================== */
    function initSearchableStudentSelect() {
        const studentOptions = Array.from(studentSelect.options).filter(function (opt) {
            return opt.value !== '';
        }).map(function (opt) {
            return {
                value: opt.value,
                name: opt.dataset.name || opt.textContent.trim(),
                className: opt.dataset.class || '',
                nis: opt.dataset.nis || '',
                rawText: opt.textContent.trim(),
            };
        });

        // Visually hide native select behind trigger without breaking browser focus/HTML5 validation
        studentSelect.style.position = 'absolute';
        studentSelect.style.opacity = '0';
        studentSelect.style.width = '100%';
        studentSelect.style.height = '42px';
        studentSelect.style.left = '0';
        studentSelect.style.top = '0';
        studentSelect.style.zIndex = '-1';
        studentSelect.style.pointerEvents = 'none';
        studentSelect.removeAttribute('tabindex');

        // Create container wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'sikola-searchable-select';

        const listboxId = 'sikola-student-listbox';
        const searchInputId = 'sikola-student-search';
        const triggerId = 'sikola-student-trigger';

        // Native button trigger
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.id = triggerId;
        trigger.className = 'sikola-searchable-trigger';
        trigger.setAttribute('role', 'combobox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-controls', listboxId);
        trigger.setAttribute('aria-labelledby', 'student-label ' + triggerId);

        // Connect label click to custom trigger for better accessibility
        const studentLabel = document.querySelector('label[for="student_id"]');
        if (studentLabel) {
            studentLabel.id = 'student-label';
            studentLabel.addEventListener('click', function (e) {
                e.preventDefault();
                trigger.focus();
            });
        }

        const triggerText = document.createElement('span');
        triggerText.className = 'sikola-searchable-trigger-text placeholder';
        triggerText.textContent = 'Cari atau pilih siswa...';

        const arrowSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        arrowSvg.setAttribute('class', 'sikola-searchable-arrow');
        arrowSvg.setAttribute('viewBox', '0 0 24 24');
        arrowSvg.setAttribute('fill', 'none');
        arrowSvg.setAttribute('stroke', 'currentColor');
        arrowSvg.setAttribute('stroke-width', '2');
        arrowSvg.setAttribute('aria-hidden', 'true');
        arrowSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>';

        trigger.appendChild(triggerText);
        trigger.appendChild(arrowSvg);

        // Dropdown menu
        const dropdown = document.createElement('div');
        dropdown.className = 'sikola-searchable-dropdown';

        // Search input box
        const searchWrapper = document.createElement('div');
        searchWrapper.className = 'sikola-searchable-search-wrapper';

        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.id = searchInputId;
        searchInput.className = 'sikola-searchable-search-input';
        searchInput.placeholder = 'Ketik nama, NIS, atau kelas...';
        searchInput.autocomplete = 'off';
        searchInput.setAttribute('aria-autocomplete', 'list');
        searchInput.setAttribute('aria-controls', listboxId);

        searchWrapper.appendChild(searchInput);
        dropdown.appendChild(searchWrapper);

        // Options list
        const optionsList = document.createElement('ul');
        optionsList.id = listboxId;
        optionsList.className = 'sikola-searchable-options';
        optionsList.setAttribute('role', 'listbox');
        optionsList.setAttribute('aria-label', 'Daftar Siswa');
        dropdown.appendChild(optionsList);

        wrapper.appendChild(trigger);
        wrapper.appendChild(dropdown);
        studentSelect.parentNode.insertBefore(wrapper, studentSelect.nextSibling);

        let highlightedIndex = -1;
        let filteredOptions = studentOptions.slice();

        function updateTriggerDisplay() {
            const selectedVal = studentSelect.value;
            const selectedItem = studentOptions.find(function (item) {
                return item.value === selectedVal;
            });

            if (selectedItem) {
                triggerText.className = 'sikola-searchable-trigger-text';
                triggerText.textContent = selectedItem.name + ' - ' + selectedItem.className + ' - NIS ' + selectedItem.nis;
                trigger.classList.remove('has-error');
            } else {
                triggerText.className = 'sikola-searchable-trigger-text placeholder';
                triggerText.textContent = 'Cari atau pilih siswa...';
            }
        }

        function renderOptions() {
            optionsList.innerHTML = '';
            highlightedIndex = -1;
            searchInput.removeAttribute('aria-activedescendant');

            if (filteredOptions.length === 0) {
                const emptyLi = document.createElement('li');
                emptyLi.className = 'sikola-searchable-empty';
                emptyLi.textContent = 'Tidak ada siswa yang cocok';
                optionsList.appendChild(emptyLi);
                return;
            }

            const currentVal = studentSelect.value;

            filteredOptions.forEach(function (item, index) {
                const li = document.createElement('li');
                li.id = 'sikola-student-option-' + index;
                li.className = 'sikola-searchable-option';
                li.setAttribute('role', 'option');
                li.setAttribute('data-value', item.value);

                if (item.value === currentVal) {
                    li.classList.add('is-selected');
                    li.setAttribute('aria-selected', 'true');
                } else {
                    li.setAttribute('aria-selected', 'false');
                }

                // Main row (Name + Class badge)
                const mainRow = document.createElement('div');
                mainRow.className = 'sikola-searchable-option-main';

                const nameSpan = document.createElement('span');
                nameSpan.className = 'sikola-searchable-option-name';
                nameSpan.textContent = item.name;

                const classBadge = document.createElement('span');
                classBadge.className = 'sikola-searchable-option-badge';
                classBadge.textContent = item.className;

                mainRow.appendChild(nameSpan);
                mainRow.appendChild(classBadge);

                // Sub row (NIS)
                const subRow = document.createElement('div');
                subRow.className = 'sikola-searchable-option-sub';
                subRow.textContent = 'NIS: ' + item.nis;

                li.appendChild(mainRow);
                li.appendChild(subRow);

                li.addEventListener('click', function () {
                    selectOption(item);
                });

                li.addEventListener('mouseenter', function () {
                    setHighlightedIndex(index);
                });

                optionsList.appendChild(li);
            });
        }

        function setHighlightedIndex(index) {
            const items = optionsList.querySelectorAll('.sikola-searchable-option');
            items.forEach(function (el) {
                el.classList.remove('is-highlighted');
            });

            if (index >= 0 && index < items.length) {
                highlightedIndex = index;
                items[highlightedIndex].classList.add('is-highlighted');
                items[highlightedIndex].scrollIntoView({ block: 'nearest' });
                searchInput.setAttribute('aria-activedescendant', items[highlightedIndex].id);
            } else {
                highlightedIndex = -1;
                searchInput.removeAttribute('aria-activedescendant');
            }
        }

        function openDropdown() {
            wrapper.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');
            filteredOptions = studentOptions.slice();
            searchInput.value = '';
            renderOptions();

            // Set initial highlight to currently selected student if any, otherwise first item
            const currentVal = studentSelect.value;
            const currentIdx = filteredOptions.findIndex(function (it) {
                return it.value === currentVal;
            });
            setHighlightedIndex(currentIdx >= 0 ? currentIdx : (filteredOptions.length > 0 ? 0 : -1));

            setTimeout(function () {
                searchInput.focus();
            }, 30);
        }

        function closeDropdown() {
            wrapper.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            highlightedIndex = -1;
            searchInput.removeAttribute('aria-activedescendant');
        }

        function selectOption(item) {
            studentSelect.value = item.value;
            updateTriggerDisplay();
            closeDropdown();
            trigger.focus();

            // Trigger change event to invoke filterBills()
            studentSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Search filtering logic (Name, NIS, and Class)
        searchInput.addEventListener('input', function () {
            const query = searchInput.value.trim().toLowerCase();

            if (!query) {
                filteredOptions = studentOptions.slice();
            } else {
                filteredOptions = studentOptions.filter(function (item) {
                    const nameMatch = item.name.toLowerCase().includes(query);
                    const nisMatch = item.nis.toLowerCase().includes(query);
                    const classMatch = item.className.toLowerCase().includes(query);

                    return nameMatch || nisMatch || classMatch;
                });
            }

            renderOptions();
            setHighlightedIndex(filteredOptions.length > 0 ? 0 : -1);
        });

        // Trigger Click & Keydown
        trigger.addEventListener('click', function () {
            if (wrapper.classList.contains('is-open')) {
                closeDropdown();
            } else {
                openDropdown();
            }
        });

        trigger.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openDropdown();
            }
        });

        // Search Input Keydown navigation
        searchInput.addEventListener('keydown', function (e) {
            const items = optionsList.querySelectorAll('.sikola-searchable-option');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (items.length === 0) return;
                const nextIndex = (highlightedIndex + 1) % items.length;
                setHighlightedIndex(nextIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (items.length === 0) return;
                const prevIndex = highlightedIndex <= 0 ? items.length - 1 : highlightedIndex - 1;
                setHighlightedIndex(prevIndex);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (highlightedIndex >= 0 && filteredOptions[highlightedIndex]) {
                    selectOption(filteredOptions[highlightedIndex]);
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                closeDropdown();
                trigger.focus();
            } else if (e.key === 'Tab') {
                closeDropdown();
            }
        });

        // Close on click outside
        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) {
                closeDropdown();
            }
        });

        // Sync display if studentSelect changed externally
        studentSelect.addEventListener('change', function () {
            updateTriggerDisplay();
        });

        // Handle HTML5 validation: focus custom trigger and highlight on invalid
        studentSelect.addEventListener('invalid', function () {
            trigger.classList.add('has-error');
            trigger.focus();
        });

        // Initial display update
        updateTriggerDisplay();
    }

    initSearchableStudentSelect();

    if (oldStudent) {
        studentSelect.value = oldStudent;

        filterBills();

        if (oldBill) {
            const matchingBillOption = Array.from(billSelect.options).find(function (opt) {
                return opt.value === oldBill;
            });

            if (matchingBillOption) {
                billSelect.value = oldBill;
                updateAmount();
            }
        }

        // Trigger change to sync searchable select display
        studentSelect.dispatchEvent(new Event('change'));
    }
});