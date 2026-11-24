

window.showEmailPreview = function (email) {
    if (!email) return;
    const toEl = document.getElementById('prev_to');
    const subjEl = document.getElementById('prev_subject');
    const dateEl = document.getElementById('prev_date');
    const bodyEl = document.getElementById('prev_body');
    if (toEl) toEl.textContent = email.to || '';
    if (subjEl) subjEl.textContent = email.subject || '';
    if (dateEl) dateEl.textContent = email.date || '';
    if (bodyEl) bodyEl.textContent = email.body || '';
    if (typeof openModal === 'function') {
        openModal('emailPreviewModal');
    } else if (typeof window.openModal === 'function') {
        window.openModal('emailPreviewModal');
    }
};

function showEmailPreview(email) {
    window.showEmailPreview(email);
}

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-preview-email');
        if (btn) {
            e.preventDefault();
            try {
                const data = JSON.parse(btn.getAttribute('data-email') || '{}');
                window.showEmailPreview(data);
            } catch (err) {
                console.error('Failed to parse email preview data', err);
            }
        }
    });
});
