
document.addEventListener('DOMContentLoaded', () => {

    const pieCanvas = document.getElementById('statusPieChart');
    if (pieCanvas && typeof Chart !== 'undefined') {
        const pieData = JSON.parse(pieCanvas.getAttribute('data-chart-data') || '[]');
        new Chart(pieCanvas, {
            type: 'pie',
            data: {
                labels: ['Pending', 'Approved', 'Rejected', 'Forwarded', 'Completed'],
                datasets: [{
                    data: pieData,
                    backgroundColor: ['#f59e0b', '#10b981', '#ef4444', '#3b82f6', '#6366f1'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 }, padding: 10 }
                    }
                },
                responsive: false
            }
        });
    }

  
    const barCanvas = document.getElementById('priorityBarChart');
    if (barCanvas && typeof Chart !== 'undefined') {
        const barData = JSON.parse(barCanvas.getAttribute('data-chart-data') || '[]');
        new Chart(barCanvas, {
            type: 'bar',
            data: {
                labels: ['Low', 'Medium', 'High', 'Emergency'],
                datasets: [{
                    label: 'Requests',
                    data: barData,
                    backgroundColor: ['#34d399', '#60a5fa', '#f97316', '#ef4444'],
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                },
                responsive: false
            }
        });
    }

    
    const lineCanvas = document.getElementById('monthlyLineChart');
    if (lineCanvas && typeof Chart !== 'undefined') {
        const labels = JSON.parse(lineCanvas.getAttribute('data-labels') || '[]');
        const values = JSON.parse(lineCanvas.getAttribute('data-values') || '[]');
        new Chart(lineCanvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Requests',
                    data: values,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,0.10)',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#6366f1',
                    pointRadius: 5,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                },
                responsive: false
            }
        });
    }
});
