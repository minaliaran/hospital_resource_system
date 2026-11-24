
window.openTrackModal = function (t) {
    if (!t) return;

    document.getElementById('trk_tid').textContent = t.tid_str || ('T-' + t.transfer_id);


    const badgeEl = document.getElementById('trk_badge');
    badgeEl.textContent = t.status;
    badgeEl.className = 'badge badge-lg ' + (
        t.status === 'Completed' ? 'badge-success' : (
            t.status === 'In Transit' ? 'badge-info' : (
                t.status === 'Cancelled' ? 'badge-danger' : 'badge-warning'
            )
        )
    );


    const fromYou = t.is_from_me ? ' <span class="badge badge-info badge-sm">You (Providing)</span>' : '';
    const toYou = t.is_to_me ? ' <span class="badge badge-success badge-sm">You (Receiving)</span>' : '';
    document.getElementById('trk_from').innerHTML = t.from_name + fromYou;
    document.getElementById('trk_to').innerHTML = t.to_name + toYou;


    const resEl = document.getElementById('trk_res');
    if (t.resource_type === 'Blood') {
        resEl.innerHTML = '<span class="text-danger">' + t.blood_group + ' Blood</span>';
        document.getElementById('trk_qty').textContent = t.quantity + ' Units';
    } else {
        resEl.innerHTML = '<span class="text-info">' + (t.cylinder_type || '') + ' Oxygen</span>';
        document.getElementById('trk_qty').textContent = t.quantity + ' Cylinders';
    }

    document.getElementById('trk_driver').textContent = t.driver_name;
    document.getElementById('trk_veh').innerHTML = '<code class="code-tag">' + t.vehicle_number + '</code>';
    document.getElementById('trk_cdate').textContent = t.transfer_date;
    document.getElementById('trk_rdate').textContent = t.received_date;


    const dot1 = document.getElementById('step_dot_1');
    const lbl1 = document.getElementById('step_lbl_1');
    const line1 = document.getElementById('step_line_1');

    const dot2 = document.getElementById('step_dot_2');
    const lbl2 = document.getElementById('step_lbl_2');
    const line2 = document.getElementById('step_line_2');

    const dot3 = document.getElementById('step_dot_3');
    const lbl3 = document.getElementById('step_lbl_3');
    const line3 = document.getElementById('step_line_3');

    const dot4 = document.getElementById('step_dot_4');
    const lbl4 = document.getElementById('step_lbl_4');

    const isPending = (t.status === 'Pending');
    const isInTransit = (t.status === 'In Transit');
    const isCompleted = (t.status === 'Completed');


    if (dot1) {
        dot1.className = 'step-dot done';
        dot1.innerHTML = '<i class="fa fa-check"></i>';
    }
    if (lbl1) {
        lbl1.className = 'step-label text-success fw-600';
        lbl1.textContent = 'Accepted';
    }
    if (line1) {
        line1.className = 'step-line ' + (isInTransit || isCompleted ? 'done' : '');
    }

    if (dot2) {
        if (isPending) {
            dot2.className = 'step-dot active';
            dot2.innerHTML = '2';
        } else {
            dot2.className = 'step-dot done';
            dot2.innerHTML = '<i class="fa fa-check"></i>';
        }
    }
    if (lbl2) {
        if (isPending) {
            lbl2.className = 'step-label text-warning fw-600';
            lbl2.textContent = 'Pending Dispatch';
        } else {
            lbl2.className = 'step-label text-success fw-600';
            lbl2.textContent = 'Dispatched';
        }
    }
    if (line2) {
        line2.className = 'step-line ' + (isCompleted ? 'done' : '');
    }


    if (dot3) {
        if (isCompleted) {
            dot3.className = 'step-dot done';
            dot3.innerHTML = '<i class="fa fa-check"></i>';
        } else if (isInTransit) {
            dot3.className = 'step-dot active';
            dot3.innerHTML = '<i class="fa fa-truck"></i>';
        } else {
            dot3.className = 'step-dot pending';
            dot3.innerHTML = '3';
        }
    }
    if (lbl3) {
        if (isCompleted) {
            lbl3.className = 'step-label text-success fw-600';
            lbl3.textContent = 'In Transit';
        } else if (isInTransit) {
            lbl3.className = 'step-label text-info fw-600';
            lbl3.textContent = 'In Transit';
        } else {
            lbl3.className = 'step-label text-muted';
            lbl3.textContent = 'In Transit';
        }
    }
    if (line3) {
        line3.className = 'step-line ' + (isCompleted ? 'done' : '');
    }


    if (dot4) {
        if (isCompleted) {
            dot4.className = 'step-dot done';
            dot4.innerHTML = '<i class="fa fa-check"></i>';
        } else {
            dot4.className = 'step-dot pending';
            dot4.innerHTML = '4';
        }
    }
    if (lbl4) {
        if (isCompleted) {
            lbl4.className = 'step-label text-success fw-600';
            lbl4.textContent = 'Completed';
        } else {
            lbl4.className = 'step-label text-muted';
            lbl4.textContent = 'Confirm Receipt';
        }
    }


    const msgEl = document.getElementById('trk_footer_msg');
    const actContainer = document.getElementById('trk_action_btn_container');

    if (msgEl && actContainer) {
        if (isPending) {
            msgEl.innerHTML = '<span class="text-warning"><i class="fa fa-clock"></i> Assigned driver awaiting departure.</span>';
            if (t.is_from_me) {
                actContainer.innerHTML = `
                    <form action="../actions/update_transfer_status.php" method="POST">
                        <input type="hidden" name="transfer_id" value="${t.transfer_id}">
                        <input type="hidden" name="new_status" value="In Transit">
                        <button type="submit" class="btn btn-primary" data-confirm="Dispatch resource with vehicle ${t.vehicle_number} and mark In Transit?">
                            <i class="fa fa-truck"></i> Dispatch Resource (Mark In Transit)
                        </button>
                    </form>
                `;
            } else {
                actContainer.innerHTML = '<span class="badge badge-warning badge-lg">Awaiting Dispatch by Providing Hospital</span>';
            }
        } else if (isInTransit) {
            msgEl.innerHTML = `<span class="text-info"><i class="fa fa-truck"></i> Vehicle <code>${t.vehicle_number}</code> is en route.</span>`;
            if (t.is_to_me) {
                actContainer.innerHTML = `
                    <form action="../actions/update_transfer_status.php" method="POST">
                        <input type="hidden" name="transfer_id" value="${t.transfer_id}">
                        <input type="hidden" name="new_status" value="Completed">
                        <button type="submit" class="btn btn-success btn-lg" data-confirm="Confirm delivery receipt? Stock will be added to your inventory.">
                            <i class="fa fa-check-circle"></i> Confirm Receipt & Add to Stock
                        </button>
                    </form>
                `;
            } else {
                actContainer.innerHTML = '<span class="badge badge-info badge-lg"><i class="fa fa-spinner fa-spin"></i> Dispatched — Awaiting Partner Receipt</span>';
            }
        } else if (isCompleted) {
            msgEl.innerHTML = '<span class="text-success"><i class="fa fa-check-circle"></i> Transfer completed & stock credited.</span>';
            actContainer.innerHTML = '<span class="badge badge-success badge-lg"><i class="fa fa-check"></i> Transfer Completed</span>';
        } else {
            msgEl.innerHTML = '';
            actContainer.innerHTML = '';
        }
    }

    if (typeof openModal === 'function') {
        openModal('trackModal');
    } else if (typeof window.openModal === 'function') {
        window.openModal('trackModal');
    }
};
