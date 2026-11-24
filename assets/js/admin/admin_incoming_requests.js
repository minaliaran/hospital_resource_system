

window.openRespondModal = function (id, action, sender, qty, resource) {
    const extId = document.getElementById('resp_ext_id');
    const act = document.getElementById('resp_action');
    if (extId) extId.value = id;
    if (act) act.value = action;
    const isAccept = action === 'Accept';
    const titleEl = document.getElementById('respondTitle');
    const infoEl = document.getElementById('respondInfo');
    if (titleEl) {
        titleEl.innerHTML = isAccept
            ? '<i class="fa fa-check-circle text-success"></i> Accept External Request #' + id
            : '<i class="fa fa-times-circle text-danger"></i> Reject External Request #' + id;
    }
    if (infoEl) {
        infoEl.innerHTML =
            '<strong>Request from:</strong> ' + sender + '<br>' +
            '<strong>Resource:</strong> ' + resource + ' &times; ' + qty + ' units<br>' +
            '<strong>Action:</strong> ' + (isAccept ? '<span class="text-success fw-600">Accept & Dispatch</span>' : '<span class="text-danger fw-600">Reject</span>');
    }

    const driverFields = document.getElementById('driver_fields');
    const driverName = document.getElementById('driver_name');
    const vehicleNum = document.getElementById('vehicle_number');

    if (isAccept) {
        driverFields.classList.remove('d-none');
        driverName.required = true;
        vehicleNum.required = true;
    } else {
        driverFields.classList.add('d-none');
        driverName.required = false;
        vehicleNum.required = false;
    }

    const btn = document.getElementById('respondSubmitBtn');
    btn.className = isAccept ? 'btn btn-success' : 'btn btn-danger';
    btn.innerHTML = isAccept ? '<i class="fa fa-check"></i> Confirm Accept & Transfer' : '<i class="fa fa-times"></i> Confirm Reject';

    openModal('respondModal');
}

document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-respond');
        if (btn) {
            e.preventDefault();
            const id = btn.getAttribute('data-ext-id');
            const action = btn.getAttribute('data-action');
            const sender = btn.getAttribute('data-sender');
            const qty = btn.getAttribute('data-qty');
            const resource = btn.getAttribute('data-resource');
            openRespondModal(id, action, sender, qty, resource);
        }
    });
});
