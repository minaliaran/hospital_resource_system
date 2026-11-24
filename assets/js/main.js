

document.addEventListener('DOMContentLoaded', () => {
    
    document.addEventListener('click', (e) => {
        const printBtn = e.target.closest('[data-action="print"], .btn-print');
        if (printBtn) {
            e.preventDefault();
            window.print();
        }
    });

    
    document.addEventListener('click', (e) => {
        const confirmEl = e.target.closest('[data-confirm]');
        if (confirmEl) {
            const message = confirmEl.getAttribute('data-confirm');
            if (message && !window.confirm(message)) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        }
    }, true);

    
    document.addEventListener('click', (e) => {
        const toggleBtn = e.target.closest('#togglePasswordBtn, [data-toggle="password"], .input-action-btn');
        if (toggleBtn) {
            e.preventDefault();
            const pwInput = document.getElementById('password') || toggleBtn.closest('.form-group, .input-with-action')?.querySelector('input[type="password"], input[type="text"]');
            if (pwInput) {
                if (pwInput.type === 'password') {
                    pwInput.type = 'text';
                    toggleBtn.textContent = 'HIDE';
                } else {
                    pwInput.type = 'password';
                    toggleBtn.textContent = 'SHOW';
                }
            }
        }
    });
});
