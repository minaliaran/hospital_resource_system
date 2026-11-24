

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-pdf-config], [data-pdf-btn], #pdfBtn, #trPdfBtn, #oxyPdfBtn, #monthlySummaryPdfBtn, #emergencyRequestsPdfBtn, #emailPdfBtn');
        if (!btn) return;

        const rawConfig = btn.getAttribute('data-pdf-config');
        if (!rawConfig) return;

        e.preventDefault();

        let config = {};
        try {
            config = JSON.parse(rawConfig);
        } catch (err) {
            console.error('Failed to parse PDF configuration', err);
            return;
        }

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating…';

        setTimeout(() => {
            if (typeof generateReportPDF === 'function') {
                try {
                    generateReportPDF(config);
                } catch (genErr) {
                    console.error('Error during PDF generation:', genErr);
                }
            } else {
                console.error('generateReportPDF is not defined');
            }
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }, 100);
    });
});
