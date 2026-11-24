

window.openEditOxy = function(stock) {
    if (!stock) return;
    const oid = document.getElementById('edit_oid');
    const otype = document.getElementById('edit_otype');
    const oqty = document.getElementById('edit_oqty');
    if (oid) oid.value = stock.oxygen_id || '';
    if (otype) otype.value = stock.cylinder_type || '';
    if (oqty) oqty.value = stock.quantity !== undefined ? stock.quantity : 0;
    if (typeof openModal === 'function') {
        openModal('editOxyModal');
    } else if (typeof window.openModal === 'function') {
        window.openModal('editOxyModal');
    }
};

function openEditOxy(stock) {
    window.openEditOxy(stock);
}

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-edit-stock');
        if (btn) {
            e.preventDefault();
            try {
                const data = JSON.parse(btn.getAttribute('data-stock') || '{}');
                window.openEditOxy(data);
            } catch (err) {
                console.error('Failed to parse stock data', err);
            }
        }
    });
});
