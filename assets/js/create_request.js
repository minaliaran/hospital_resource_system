
document.addEventListener('DOMContentLoaded', () => {
    const stockSelectors = document.querySelectorAll('select[data-stock]');
    stockSelectors.forEach(selectEl => {
        let stockData = {};
        try {
            stockData = JSON.parse(selectEl.getAttribute('data-stock') || '{}');
        } catch (e) {
            console.error('Failed to parse stock data from data-stock attribute', e);
            return;
        }
        const unitLabel = selectEl.getAttribute('data-unit') || 'units';
        const targetId = selectEl.getAttribute('data-target-stock') || 'current_stock';
        const targetEl = document.getElementById(targetId);
        selectEl.addEventListener('change', (e) => {
            const key = e.target.value;
            if (!targetEl) return;
            if (key && stockData[key] !== undefined) {
                const count = parseInt(stockData[key], 10);
                targetEl.textContent = `${count} ${unitLabel}`;
                if (count < 5) {
                    targetEl.classList.add('text-danger');
                    targetEl.classList.remove('text-success');
                } else {
                    targetEl.classList.add('text-success');
                    targetEl.classList.remove('text-danger');
                }
            } else {
                targetEl.textContent = '—';
                targetEl.classList.remove('text-danger', 'text-success');
            }
        });
    });
});
