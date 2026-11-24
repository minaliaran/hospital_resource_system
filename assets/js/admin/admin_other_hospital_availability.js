

window.openSendReq = function (hospId, hospName, resTypeVal = '') {
    const recvId = document.getElementById('recv_hosp_id');
    const recvName = document.getElementById('recv_hosp_name');
    const resType = document.getElementById('req_res_type');
    const bloodRow = document.getElementById('blood_group_row');
    const oxyRow = document.getElementById('cylinder_type_row');

    if (recvId) recvId.value = hospId;
    if (recvName) recvName.value = hospName;

    if (resTypeVal && resType) {
        resType.value = resTypeVal;
        if (resTypeVal === 'Blood') {
            if (bloodRow) bloodRow.classList.remove('d-none');
            if (oxyRow) oxyRow.classList.add('d-none');
        } else if (resTypeVal === 'Oxygen') {
            if (bloodRow) bloodRow.classList.add('d-none');
            if (oxyRow) oxyRow.classList.remove('d-none');
        }
    }

    if (typeof openModal === 'function') {
        openModal('sendReqModal');
    } else if (typeof window.openModal === 'function') {
        window.openModal('sendReqModal');
    }
};

function openSendReq(hospId, hospName, resTypeVal = '') {
    window.openSendReq(hospId, hospName, resTypeVal);
}

document.addEventListener('DOMContentLoaded', function () {
    // 1. Tab Switching Logic
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-content-panel');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-tab');
            if (!targetId) return;

            // Remove active class from all buttons
            tabButtons.forEach(b => b.classList.remove('active'));
            // Add active to clicked button
            this.classList.add('active');

            // Hide all tab panels
            tabPanels.forEach(panel => panel.classList.add('d-none'));

            // Show selected panel
            const activePanel = document.getElementById(targetId);
            if (activePanel) {
                activePanel.classList.remove('d-none');
            }
        });
    });

    // 2. Resource Type Toggle in Modal
    const resType = document.getElementById('req_res_type');
    const bloodRow = document.getElementById('blood_group_row');
    const oxyRow = document.getElementById('cylinder_type_row');

    if (resType && bloodRow && oxyRow) {
        resType.addEventListener('change', function () {
            if (this.value === 'Blood') {
                bloodRow.classList.remove('d-none');
                oxyRow.classList.add('d-none');
            } else if (this.value === 'Oxygen') {
                bloodRow.classList.add('d-none');
                oxyRow.classList.remove('d-none');
            } else {
                bloodRow.classList.remove('d-none');
                oxyRow.classList.add('d-none');
            }
        });
    }

    // 3. Send Request Button Handler
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-send-req');
        if (btn) {
            e.preventDefault();
            const hospId = btn.getAttribute('data-hosp-id');
            const hospName = btn.getAttribute('data-hosp-name');
            const resTypeVal = btn.getAttribute('data-res-type') || '';
            openSendReq(hospId, hospName, resTypeVal);
        }
    });
});

