(function () {
    const titleInput = document.getElementById('title');
    const studioInput = document.getElementById('studio');
    const descriptionInput = document.getElementById('short_description');
    const feeInput = document.getElementById('publication_fee');
    const accentInput = document.getElementById('accent_color');

    const previewTitle = document.querySelector('.preview-card__title');
    const previewMeta = document.querySelector('.preview-card__meta');
    const previewText = document.querySelector('.preview-card__text');
    const previewCard = document.querySelector('.preview-card');
    const previewAvatar = document.querySelector('.preview-card__avatar');

    const setState = (el, state) => {
        if (!el) return;
        el.classList.remove('input--error', 'input--success');
        if (state) {
            el.classList.add(state);
        }
    };

    const isTextValid = (value, min) => value.trim().length >= min;
    const isNumberValid = (value) => value !== '' && !Number.isNaN(Number(value)) && Number(value) >= 0;

    const syncPreview = () => {
        if (previewTitle && titleInput) {
            previewTitle.textContent = titleInput.value.trim() || 'Game title preview';
        }

        if (previewMeta && studioInput) {
            previewMeta.textContent = studioInput.value.trim() || 'Studio name';
        }

        if (previewText && descriptionInput) {
            previewText.textContent =
            descriptionInput.value.trim() ||
            'The short description will appear here as a storefront summary.';
        }

        if (previewCard && accentInput) {
            previewCard.style.setProperty('--accent', accentInput.value);
        }

        if (previewAvatar && titleInput) {
            const letters = titleInput.value.trim().slice(0, 1).toUpperCase() || 'G';
            previewAvatar.textContent = letters;
        }
    };

    const validateInput = (el, kind, min = 0) => {
        if (!el) return;

        const value = el.value;

        let valid = true;

        if (kind === 'text') {
            valid = isTextValid(value, min);
        } else if (kind === 'number') {
            valid = isNumberValid(value);
        } else if (kind === 'date') {
            valid = value.trim().length > 0;
        }

        setState(el, valid ? 'input--success' : 'input--error');
    };

    const attachValidation = () => {
        if (titleInput) {
            titleInput.addEventListener('input', () => {
                validateInput(titleInput, 'text', 3);
                syncPreview();
            });
            titleInput.addEventListener('blur', () => validateInput(titleInput, 'text', 3));
        }

        if (studioInput) {
            studioInput.addEventListener('input', () => {
                validateInput(studioInput, 'text', 2);
                syncPreview();
            });
            studioInput.addEventListener('blur', () => validateInput(studioInput, 'text', 2));
        }

        if (descriptionInput) {
            descriptionInput.addEventListener('input', () => {
                validateInput(descriptionInput, 'text', 20);
                syncPreview();
            });
            descriptionInput.addEventListener('blur', () => validateInput(descriptionInput, 'text', 20));
        }

        if (feeInput) {
            feeInput.addEventListener('input', () => {
                validateInput(feeInput, 'number');
                syncPreview();
            });
            feeInput.addEventListener('blur', () => validateInput(feeInput, 'number'));
        }

        if (accentInput) {
            accentInput.addEventListener('input', syncPreview);
        }
    };

    const enhanceCheckboxChips = () => {
        document.querySelectorAll('.chip input[type="checkbox"]').forEach((input) => {
            const updateChip = () => {
                const chip = input.closest('.chip');
                if (!chip) return;
                chip.style.borderColor = input.checked ? 'rgba(102, 192, 244, 0.65)' : 'rgba(255,255,255,0.10)';
                chip.style.background = input.checked ? 'rgba(102, 192, 244, 0.12)' : 'rgba(0, 0, 0, 0.18)';
            };

            input.addEventListener('change', updateChip);
            updateChip();
        });
    };

    const attachFileHints = () => {
        document.querySelectorAll('input[type="file"]').forEach((input) => {
            input.addEventListener('change', () => {
                const label = input.parentElement?.querySelector('.field__hint');
                if (!label) return;

                if (input.files && input.files[0]) {
                    const file = input.files[0];
                    label.textContent = `Selected: ${file.name}`;
                }
            });
        });
    };

    syncPreview();
    attachValidation();
    enhanceCheckboxChips();
    attachFileHints();
})();
