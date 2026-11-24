

window.openEdit = function(stock) {
    if (!stock) return;
    const bid = document.getElementById('edit_bid');
    const bg = document.getElementById('edit_bg');
    const comp = document.getElementById('edit_comp');
    const qty = document.getElementById('edit_qty');
    const exp = document.getElementById('edit_exp');
    if (bid) bid.value = stock.blood_id || '';
    if (bg) bg.value = stock.blood_group || '';
    if (comp) comp.value = stock.component || '';
    if (qty) qty.value = stock.quantity !== undefined ? stock.quantity : 0;
    if (exp) exp.value = stock.expiry_date ? String(stock.expiry_date).substring(0, 10) : '';
    if (typeof openModal === 'function') {
        openModal('editBloodModal');
    } else if (typeof window.openModal === 'function') {
        window.openModal('editBloodModal');
    }
};

function openEdit(stock) {
    window.openEdit(stock);
}

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-edit-stock');
        if (btn) {
            e.preventDefault();
            try {
                const data = JSON.parse(btn.getAttribute('data-stock') || '{}');
                window.openEdit(data);
            } catch (err) {
                console.error('Failed to parse stock data', err);
            }
        }
    });
});
