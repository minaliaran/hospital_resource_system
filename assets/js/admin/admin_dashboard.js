document.addEventListener('DOMContentLoaded', () => {
    const bloodCanvas = document.getElementById('bloodGroupChart');
    if (bloodCanvas) {
        const labels = JSON.parse(bloodCanvas.getAttribute('data-labels') || '[]');
        const values = JSON.parse(bloodCanvas.getAttribute('data-values') || '[]');
        const bloodCtx = bloodCanvas.getContext('2d');
        new Chart(bloodCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Stock Quantity (Units)',
                    data: values,
                    backgroundColor: 'rgba(239, 68, 68, 0.75)',
                    borderColor: '#ef4444',
                    borderWidth: 1.5,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { precision: 0 }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }
    const oxygenCanvas = document.getElementById('oxygenTypeChart');
    if (oxygenCanvas) {
        const labels = JSON.parse(oxygenCanvas.getAttribute('data-labels') || '[]');
        const values = JSON.parse(oxygenCanvas.getAttribute('data-values') || '[]');
        const oxygenCtx = oxygenCanvas.getContext('2d');
        new Chart(oxygenCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Cylinder Count',
                    data: values,
                    backgroundColor: 'rgba(14, 165, 233, 0.75)',
                    borderColor: '#0ea5e9',
                    borderWidth: 1.5,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { precision: 0 }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }
    const statusCanvas = document.getElementById('requestStatusChart');
    if (statusCanvas) {
        const labels = JSON.parse(statusCanvas.getAttribute('data-labels') || '[]');
        const values = JSON.parse(statusCanvas.getAttribute('data-values') || '[]');
        const requestCtx = statusCanvas.getContext('2d');
        new Chart(requestCtx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: ['#f59e0b', '#10b981', '#ef4444', '#6366f1'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 8,
                            font: { size: 10 }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
});
