

function openEditUser(data) {
    if (!data) return;
    document.getElementById('edit_uid').value = data.user_id || '';
    document.getElementById('edit_fullname').value = data.full_name || '';
    document.getElementById('edit_uname_display').value = data.username || '';
    openModal('editUserModal');
}

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-edit-user');
        if (btn) {
            e.preventDefault();
            try {
                const data = JSON.parse(btn.getAttribute('data-user') || '{}');
                openEditUser(data);
            } catch (err) {
                console.error('Failed to parse user data', err);
            }
        }
    });
});
