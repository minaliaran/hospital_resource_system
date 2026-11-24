

window.openEditBlood = function (data) {
    if (!data) return;
    const bid = document.getElementById('edit_blood_id');
    const bg = document.getElementById('edit_blood_group');
    const comp = document.getElementById('edit_component');
    const qty = document.getElementById('edit_quantity');
    const exp = document.getElementById('edit_expiry');
    if (bid) bid.value = data.blood_id || '';
    if (bg) bg.value = data.blood_group || '';
    if (comp) comp.value = data.component || '';
    if (qty) qty.value = data.quantity !== undefined ? data.quantity : 0;
    if (exp) exp.value = data.expiry_date ? String(data.expiry_date).substring(0, 10) : '';
    if (typeof openModal === 'function') {
        openModal('editModal');
    } else if (typeof window.openModal === 'function') {
        window.openModal('editModal');
    }
};

function openEditBlood(data) {
    window.openEditBlood(data);
}

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-edit-blood');
        if (btn) {
            e.preventDefault();
            try {
                const data = JSON.parse(btn.getAttribute('data-blood') || '{}');
                window.openEditBlood(data);
            } catch (err) {
                console.error('Failed to parse blood data', err);
            }
        }
    });
});
