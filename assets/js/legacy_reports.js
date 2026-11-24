
function downloadReportPDF(filename) {
    console.warn('downloadReportPDF() is deprecated. Use generateReportPDF(config) from pdf_report.js instead.');
    alert('PDF generation is being initialized. If this persists, please use the Print button.');
    window.print();
}
