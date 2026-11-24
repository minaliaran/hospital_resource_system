

window.openModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('open');
        modal.style.display = 'flex';
    }
};

window.closeModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('open');
        modal.style.display = 'none';
    }
};

function openModal(modalId) {
    window.openModal(modalId);
}

function closeModal(modalId) {
    window.closeModal(modalId);
}

document.addEventListener('DOMContentLoaded', () => {
    
    document.addEventListener('click', (e) => {
        const openTrigger = e.target.closest('[data-modal-open]');
        if (openTrigger) {
            e.preventDefault();
            const modalId = openTrigger.getAttribute('data-modal-open');
            openModal(modalId);
            return;
        }

        
        const closeTrigger = e.target.closest('[data-modal-close], .modal-close');
        if (closeTrigger) {
            e.preventDefault();
            const specificId = closeTrigger.getAttribute('data-modal-close');
            if (specificId) {
                closeModal(specificId);
            } else {
                const parentModal = closeTrigger.closest('.modal-overlay');
                if (parentModal) {
                    parentModal.classList.remove('open');
                    parentModal.style.display = '';
                }
            }
            return;
        }

        
        if (e.target.classList.contains('modal-overlay')) {
            e.target.classList.remove('open');
            e.target.style.display = '';
        }
    });

    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(modal => {
                modal.classList.remove('open');
                modal.style.display = '';
            });
        }
    });
});
