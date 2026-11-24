
document.addEventListener('DOMContentLoaded', () => {
    const todayStr = new Date().toISOString().split('T')[0];
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
    
        const dateFields = form.querySelectorAll('input[type="date"]');
        dateFields.forEach(field => {
            field.addEventListener('change', () => {
                validateDateField(field);
            });
            field.addEventListener('input', () => {
                validateDateField(field);
            });
            field.addEventListener('blur', () => {
                validateDateField(field);
            });
        });

        form.addEventListener('submit', (e) => {
            let isValid = true;
            let firstInvalidField = null;

            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    highlightField(field, 'This field is required');
                    if (!firstInvalidField) firstInvalidField = field;
                } else {
                    resetField(field);
                }

                if (field.type === 'number') {
                    const minVal = field.hasAttribute('min') ? Number(field.getAttribute('min')) : 1;
                    if (Number(field.value) < minVal || isNaN(Number(field.value))) {
                        isValid = false;
                        highlightField(field, 'Please enter a valid quantity');
                        if (!firstInvalidField) firstInvalidField = field;
                    }
                }

                if (field.type === 'date') {
                    const isDateValid = validateDateField(field);
                    if (!isDateValid) {
                        isValid = false;
                        if (!firstInvalidField) firstInvalidField = field;
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
            }
        });
    });

    function validateDateField(field) {
        if (!field.value) return true;
        const minDate = field.getAttribute('min') || todayStr;
        if (field.value < minDate) {
            highlightField(field, 'Date is invalid');
            field.setCustomValidity('Date is invalid');
            return false;
        } else {
            resetField(field);
            field.setCustomValidity('');
            return true;
        }
    }
});

function highlightField(field, message = 'Date is invalid') {
    field.style.borderColor = '#ef4444';
    field.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.15)';
  
    let parent = field.parentElement;
    if (parent) {
        let errorEl = parent.querySelector('.field-error-text');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.className = 'field-error-text';
            errorEl.style.color = '#ef4444';
            errorEl.style.fontSize = '0.82rem';
            errorEl.style.fontWeight = '600';
            errorEl.style.marginTop = '4px';
            errorEl.style.display = 'flex';
            errorEl.style.alignItems = 'center';
            errorEl.style.gap = '4px';
            parent.appendChild(errorEl);
        }
        errorEl.innerHTML = `<i class="fa fa-exclamation-circle"></i> ${message}`;
        errorEl.style.display = 'flex';
    }
}

function resetField(field) {
    field.style.borderColor = '';
    field.style.boxShadow = '';
    
    let parent = field.parentElement;
    if (parent) {
        let errorEl = parent.querySelector('.field-error-text');
        if (errorEl) {
            errorEl.style.display = 'none';
        }
    }
}
