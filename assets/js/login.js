
function togglePassword() {
    const pw = document.getElementById('password');
    const btn = document.querySelector('.input-action-btn');
    if (pw) {
        if (pw.type === 'password') {
            pw.type = 'text';
            if (btn) btn.textContent = 'HIDE';
        } else {
            pw.type = 'password';
            if (btn) btn.textContent = 'SHOW';
        }
    }
}
