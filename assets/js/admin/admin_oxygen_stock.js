

window.openEditOxy = function (data) {
    if (!data) return;
    const oid = document.getElementById('edit_oxy_id');
    const otype = document.getElementById('edit_oxy_type');
    const oqty = document.getElementById('edit_oxy_qty');
    if (oid) oid.value = data.oxygen_id || '';
    if (otype) otype.value = data.cylinder_type || '';
    if (oqty) oqty.value = data.quantity !== undefined ? data.quantity : 0;
    if (typeof openModal === 'function') {
        openModal('editOxyModal');
    } else if (typeof window.openModal === 'function') {
        window.openModal('editOxyModal');
    }
};

function openEditOxy(data) {
    window.openEditOxy(data);
}

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-edit-oxy');
        if (btn) {
            e.preventDefault();
            try {
                const data = JSON.parse(btn.getAttribute('data-oxy') || '{}');
                window.openEditOxy(data);
            } catch (err) {
                console.error('Failed to parse oxygen data', err);
            }
        }
    });
});
