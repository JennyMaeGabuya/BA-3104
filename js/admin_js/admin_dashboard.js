function formatTime(timeString) {
    if (!timeString) return '';

    const [h, m] = timeString.split(':');
    const hour = parseInt(h);
    const minute = parseInt(m);

    const ampm = hour >= 12 ? "PM" : "AM";
    const hour12 = hour % 12 === 0 ? 12 : hour % 12;

    return `${hour12}:${String(minute).padStart(2, '0')} ${ampm}`;
}

function formatStatusLabel(status) {
    switch (status) {
        case "rescheduled_pending":
            return "Rescheduled";
        case "pending":
            return "Pending";
        case "completed":
            return "Completed";
        case "cancelled":
            return "Cancelled";
        default:
            return status;
    }
}

let appointments = [];
let medicalRecords = [];
let cancelledAppointments = [];
let currentAppointmentId = null;
let rescheduledAppointments = [];
// --------------------------------------------------------------
// INIT
// --------------------------------------------------------------
function init() {

    // Load appointments from DB
    fetch("/booking-management/controllers/admin_controllers/get_all_appointments.php")
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                appointments = res.data.map(row => ({
                    id: row.id,
                    date: row.date,
                    time: formatTime(row.time),
                    reason: row.reason,
                    fullName: row.fullName,
                    contactNo: row.contactNo,
                    email: row.email,
                    age: row.age,
                    gender: row.gender,
                    address: row.address,
                    dob: row.date_of_birth,
                    doctorNote: row.doctor_note || "",   // <‑‑ add this
                    status: row.status
                }));


                saveData();
                loadAppointments();
                loadNotifications();
            }
        })
        .catch(err => console.error("Error loading appointments:", err));

    // Fetch medical records from DB
    fetch("/booking-management/controllers/admin_controllers/get_medical_records.php")
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                medicalRecords = res.data;
                saveData();
                loadMedicalRecords();
            }
        });

    // Load medical records from localStorage (client-side only)
    loadMedicalRecords();

    const storedAppointments = localStorage.getItem('adminAppointments');
    if (storedAppointments) {
        try {
            appointments = JSON.parse(storedAppointments);
            loadAppointments();
            loadNotifications();
        } catch (e) {
            console.error("Failed to parse stored appointments:", e);
        }
    }

    const storedRecords = localStorage.getItem('medicalRecords');
    if (storedRecords) {
        try {
            medicalRecords = JSON.parse(storedRecords);
            loadMedicalRecords();
        } catch (e) {
            console.error("Failed to parse stored records:", e);
        }
    }
}

// Save to localStorage
function saveData() {
    localStorage.setItem('adminAppointments', JSON.stringify(appointments));
    localStorage.setItem('medicalRecords', JSON.stringify(medicalRecords));
}
function loadAppointments() {
    const container = document.getElementById("adminAppointmentCards");
    const count = document.getElementById('appointmentCount');

    container.innerHTML = "";
    count.textContent = appointments.length;

    if (appointments.length === 0) {
        container.innerHTML = `<div class="empty-state">No appointments found</div>`;
        return;
    }

    appointments.forEach(apt => {
        const dateObj = new Date(apt.date);
        const formattedDate = dateObj.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });

        container.innerHTML += `
            <div class="admin-apt-card">

                <!-- ALWAYS VISIBLE -->
                <div class="admin-apt-header">
                    <h3 class="admin-apt-name">${apt.fullName}</h3>
                    <button class="toggle-btn" onclick="toggleDetails(${apt.id})" id="toggle-${apt.id}">
                        Show More ▼
                    </button>
                </div>

                <!-- DETAILS (HIDDEN INITIALLY) -->
                <div class="admin-apt-details" id="details-${apt.id}">
                    
                    <div class="two-column-card">

                        <div class="left-info">
                            <p><strong>Date:</strong> ${formattedDate}</p>
                            <p><strong>Time:</strong> ${apt.time}</p>
                            <p><strong>Reason:</strong> ${apt.reason}</p>
                            <p><strong>Contact:</strong> ${apt.contactNo}</p>
                            <p><strong>Email:</strong> ${apt.email}</p>
                            <p><strong>Age:</strong> ${apt.age}</p>
                            <p><strong>Gender:</strong> ${apt.gender}</p>
                            <p><strong>Address:</strong> ${apt.address}</p>
                            <p><strong>DOB:</strong> ${apt.dob}</p>
                        </div>

                        <div class="right-panel">

                            <div class="status-box ${apt.status === 'completed' ? 'status-completed' : 'status-pending'}">
                                <strong>Status:</strong> ${formatStatusLabel(apt.status)}
                            </div>

                            <h4>Doctor’s Note</h4>
                           <textarea class="doctor-note-textarea" id="doctorNote-${apt.id}">${(apt.doctorNote || "").trim()}</textarea>


                            <div class="admin-actions">
                                <button class="btn-success" onclick="completeAppointment(${apt.id})">Complete</button>
                                <button class="btn-primary" onclick="addNoteFromCard(${apt.id})">Add Note</button>
                                 <button class="btn-danger" onclick="cancelAppointmentAdmin(${apt.id})">Cancel</button>
                            </div>

                        </div>

                    </div>

                </div>

            </div>
        `;
    });
}


function renderAdminAppointments() {
    const container = document.getElementById("adminAppointmentList");
    const countEl = document.getElementById("adminAppointmentCount");
    if (!container) return;

    const list = appointments.filter(a => a.status !== "completed");
    if (countEl) countEl.textContent = list.length;

    if (list.length === 0) {
        container.innerHTML = `
            <div class="admin-empty-state">
                No appointments found.
            </div>`;
        return;
    }

    container.innerHTML = list.map(apt => {
        const d = new Date(apt.date);
        const formattedDate = d.toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric"
        });

        const safeNote = (apt.doctorNote || "").replace(/</g, "&lt;");

        return `
            <div class="admin-apt-row">
                <div class="admin-apt-left">
                    <h3>${apt.fullName}</h3>

                    <p><strong>Date:</strong> ${formattedDate}</p>
                    <p><strong>Time:</strong> ${apt.time}</p>
                    <p><strong>Reason:</strong> ${apt.reason}</p>

                    <p><strong>Contact:</strong> ${apt.contactNo}</p>
                    <p><strong>Email:</strong> ${apt.email}</p>
                    <p><strong>Age:</strong> ${apt.age}</p>
                    <p><strong>Gender:</strong> ${apt.gender}</p>
                    <p><strong>Address:</strong> ${apt.address}</p>
                    <p><strong>DOB:</strong> ${apt.dob}</p>
                </div>

                <div class="admin-apt-right">
                    <div class="admin-status-chip">
                        Status: ${formatStatusLabel(apt.status)}
                    </div>

                    <h4 class="admin-note-title">Doctor’s Note</h4>
                    <textarea
                        id="doctorNote-${apt.id}"
                        class="admin-note-textarea"
                        placeholder="Add note here...">${safeNote}</textarea>

                    <div class="admin-actions">
                        <button class="btn-success" onclick="completeAppointment(${apt.id})">
                            Complete
                        </button>
                        <button class="btn-primary" onclick="saveNoteFromCard(${apt.id})">
                            Add Note
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join("");
}

async function saveNoteFromCard(id) {
    const el = document.getElementById(`doctorNote-${id}`);
    if (!el) return;
    const note = el.value.trim();

    const fd = new FormData();
    fd.append("appointment_id", id);
    fd.append("note", note);

    const res = await fetch("/booking-management/controllers/admin_controllers/add_doctor_note.php", {
        method: "POST",
        body: fd
    });
    const data = await res.json();
    alert(data.msg);

    if (data.success) {
        const apt = appointments.find(a => a.id === id);
        if (apt) apt.doctorNote = note;
        renderAdminAppointments();
    }
}

// --------------------------------------------------------------
// NOTIFICATIONS: CANCELLED + RESCHEDULED
// --------------------------------------------------------------
async function loadNotifications() {

    try {
        const res = await fetch("/booking-management/controllers/admin_controllers/get_cancelled_admin.php");
        const json = await res.json();
        cancelledAppointments = (json.success && Array.isArray(json.data))
            ? json.data.map(row => ({
                notifId: row.notif_id,
                refId: row.ref_id,
                date: row.date,
                time: formatTime(row.time),
                fullName: row.fullName,
                email: row.email,
                statusLabel: "Cancelled"
            }))
            : [];
    } catch (err) {
        console.error("Error loading cancelled notifications:", err);
        cancelledAppointments = [];
    }

    try {
        const res2 = await fetch("/booking-management/controllers/admin_controllers/get_reschedule_admin.php");
        const json2 = await res2.json();
        rescheduledAppointments = (json2.success && Array.isArray(json2.data))
            ? json2.data.map(row => ({
                notifId: row.notif_id,
                refId: row.ref_id,
                date: row.date,
                time: formatTime(row.time),
                fullName: row.fullName,
                email: row.email,
                statusLabel: "Rescheduled"
            }))
            : [];
    } catch (err) {
        console.error("Error loading rescheduled notifications:", err);
        rescheduledAppointments = [];
    }

    const canceledCount = document.getElementById("canceledCount");
    const rescheduledCount = document.getElementById("rescheduledCount");
    if (canceledCount) canceledCount.textContent = cancelledAppointments.length;
    if (rescheduledCount) rescheduledCount.textContent = rescheduledAppointments.length;

    renderNotificationCards("canceledNotifications", cancelledAppointments);
    renderNotificationCards("rescheduledNotifications", rescheduledAppointments);

    // Total notifications
    const totalAdminNotifications =
        cancelledAppointments.length + rescheduledAppointments.length;

    // Update sidebar badge
    const sidebarBadge = document.getElementById("adminNotifCount");
    if (sidebarBadge) sidebarBadge.textContent = totalAdminNotifications > 0 ? totalAdminNotifications : "";

    // Update topbar badge
    const topBadge = document.getElementById("adminNotifCountTop");
    if (topBadge) topBadge.textContent = totalAdminNotifications > 0 ? totalAdminNotifications : "";

}


// --------------------------------------------------------------
// MEDICAL RECORDS (unchanged)
// --------------------------------------------------------------
function loadMedicalRecords() {
    const tbody = document.getElementById('medicalRecordsTable');
    const count = document.getElementById('recordsCount');

    if (!tbody || !count) return;

    count.textContent = medicalRecords.length;

    if (medicalRecords.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="11" class="empty-state">No medical records found</td></tr>';
        return;
    }

    tbody.innerHTML = medicalRecords.map(r => {
        const d = new Date(r.date);
        const formattedDate = d.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });

        return `
            <tr>
                <td>${formattedDate}</td>
                <td>${r.time}</td>
                <td><strong>${r.full_name}</strong></td>
                <td>${r.contact_no || ''}</td>
                <td>${r.email || ''}</td>
                <td>${r.age ?? ''}</td>
                <td>${r.gender || ''}</td>
                <td>${r.address || ''}</td>
                <td>${r.date_of_birth || ''}</td>
                <td>${r.reason || ''}</td>
             <td onclick="openNoteView('${(r.doctor_note || '').replace(/'/g, "\\'")}')">
    ${(r.doctor_note || '').length > 40
                ? r.doctor_note.substring(0, 40) + '...'
                : r.doctor_note || ''}
</td>

            </tr>
        `;
    }).join('');
}

// --------------------------------------------------------------
// SIDEBAR + SECTIONS
// --------------------------------------------------------------
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

function switchSection(sectionName) {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    event.target.closest('.nav-item').classList.add('active');

    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(sectionName).classList.add('active');

    if (window.innerWidth <= 768) toggleSidebar();

    if (sectionName === 'notifications') loadNotifications();
}

// --------------------------------------------------------------
// NOTE MODAL + COMPLETE STATUS (client-side only)
// --------------------------------------------------------------
function openNoteModal(appointmentId) {
    currentAppointmentId = appointmentId;
    const appointment = appointments.find(apt => apt.id === appointmentId);

    if (appointment) {
        document.getElementById('notePatientName').textContent =
            `Add doctor's note for ${appointment.fullName}'s consultation`;
        document.getElementById('doctorNote').value = '';
        document.getElementById('noteModal').classList.add('active');
    }
}

function closeNoteModal() {
    document.getElementById('noteModal').classList.remove('active');
    currentAppointmentId = null;
}

async function saveDoctorNote() {
    if (!currentAppointmentId) return;
    const note = document.getElementById("doctorNote").value.trim();

    const fd = new FormData();
    fd.append("appointment_id", currentAppointmentId);
    fd.append("note", note);

    const res = await fetch("/booking-management/controllers/admin_controllers/add_doctor_note.php", {
        method: "POST",
        body: fd
    });
    const data = await res.json();
    alert(data.msg);

    if (data.success) {
        const apt = appointments.find(a => a.id === currentAppointmentId);
        if (apt) apt.doctorNote = note;
        saveData();
        loadAppointments();
    }
}

async function completeAppointment(appointmentId) {
    if (!confirm("Mark this appointment as completed?")) return;

    const fd = new FormData();
    fd.append("appointment_id", appointmentId);

    try {
        const res = await fetch("/booking-management/controllers/admin_controllers/complete_appointment.php", {
            method: "POST",
            body: fd
        });
        const data = await res.json();
        alert(data.msg);

        if (data.success) {
            appointments = appointments.filter(a => a.id !== appointmentId);
            saveData();
            loadAppointments();
            loadNotifications();
            init();
        }

    } catch (err) {
        console.error("Complete error:", err);
        alert("Error completing appointment.");
    }
}

function addNoteFromCard(id) {
    currentAppointmentId = id;
    const noteEl = document.getElementById(`doctorNote-${id}`);
    if (!noteEl) return;
    document.getElementById("doctorNote").value = noteEl.value; // if you still use modal
    saveDoctorNote();
}

// --------------------------------------------------------------
// LOGOUT
// --------------------------------------------------------------
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../auth/login.php';
    }
}

// --------------------------------------------------------------
// CLICK HANDLERS
// --------------------------------------------------------------
document.addEventListener('click', function (event) {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');

    if (window.innerWidth <= 768 &&
        sidebar.classList.contains('active') &&
        !sidebar.contains(event.target) &&
        !menuToggle.contains(event.target)) {
        toggleSidebar();
    }
});

window.addEventListener('click', function (event) {
    const noteModal = document.getElementById('noteModal');

    if (event.target === noteModal) closeNoteModal();
});

// --------------------------------------------------------------
document.addEventListener('DOMContentLoaded', init);


function toggleDetails(id) {
    const section = document.getElementById(`details-${id}`);
    const btn = document.getElementById(`toggle-${id}`);

    if (section.style.display === "block") {
        section.style.display = "none";
        btn.textContent = "Show More ▼";
    } else {
        section.style.display = "block";
        btn.textContent = "Show Less ▲";
    }
}


function openNoteView(noteText) {
    document.getElementById("noteViewText").textContent = noteText;
    document.getElementById("noteViewModal").classList.add("active");
}

function closeNoteViewModal() {
    document.getElementById("noteViewModal").classList.remove("active");
}


function renderNotificationCards(listId, items) {
    const container = document.getElementById(listId);
    if (!container) return;

    if (!items || !items.length) {
        container.innerHTML = `<div class="empty-state">No notifications</div>`;
        return;
    }

    container.innerHTML = items.map(n => {
        const d = new Date(n.date);
        const dateFormatted = d.toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric"
        });

        const icon = n.statusLabel === "Cancelled" ? " " : " ";
        const title = n.statusLabel === "Cancelled"
            ? "Appointment Cancelled"
            : "Appointment Rescheduled";

        return `
            <div class="notification-card" id="notif-${n.notifId}">
                <div class="notif-icon">${icon}</div>
                <div class="notif-content">
                    <h4>${title}</h4>
                    <p><strong>${n.fullName}</strong> ${n.statusLabel === "Cancelled" ? "cancelled" : "rescheduled"
            } their appointment.</p>
                    <span class="notif-date">${dateFormatted} • ${n.time}</span>
                </div>
                <button class="notif-delete" onclick="deleteNotification(${n.notifId}, '${n.statusLabel}')">
                    Delete
                </button>
            </div>
        `;
    }).join("");
}

async function deleteNotification(notifId, type) {
    const card = event?.target?.closest(".notification-card");
    if (card) {
        card.classList.add("removing");
        event.target.disabled = true;
    }

    const fd = new FormData();
    fd.append("id", notifId);
    fd.append("type", type);

    const res = await fetch("/booking-management/controllers/admin_controllers/delete_notification.php", {
        method: "POST",
        body: fd
    });

    let data;
    try { data = await res.json(); } catch { data = { success: false }; }

    if (!data.success) {
        if (card) card.classList.remove("removing");
        if (event?.target) event.target.disabled = false;
        alert(data.msg || "Failed to delete notification.");
        return;
    }

    // CANCELLED
    if (type === "cancelled") {
        cancelledAppointments = cancelledAppointments.filter(
            n => Number(n.notifId) !== Number(notifId)
        );

        renderNotificationCards("canceledNotifications", cancelledAppointments);

        const canceledCount = document.getElementById("canceledCount");
        if (canceledCount) canceledCount.textContent = cancelledAppointments.length;

        return;
    }

    // RESCHEDULED
    if (type === "rescheduled") {
        rescheduledAppointments = rescheduledAppointments.filter(
            n => Number(n.notifId) !== Number(notifId)
        );

        renderNotificationCards("rescheduledNotifications", rescheduledAppointments);

        const rescheduledCount = document.getElementById("rescheduledCount");
        if (rescheduledCount) rescheduledCount.textContent = rescheduledAppointments.length;

        return;
    }
}

async function cancelAppointmentAdmin(appointmentId) {
    if (!confirm("Cancel this appointment? The patient will be notified.")) return;

    const fd = new FormData();
    fd.append("appointment_id", appointmentId);

    const res = await fetch("/booking-management/controllers/admin_controllers/cancel_appointment_admin.php", {
        method: "POST",
        body: fd
    });

    let data;
    try { data = await res.json(); } catch { data = { success: false }; }

    if (!data.success) {
        alert(data.msg || "Failed to cancel appointment.");
        return;
    }

    appointments = appointments.filter(a => Number(a.id) !== Number(appointmentId));
    saveData();
    loadAppointments();
    loadNotifications();
    alert("Appointment cancelled and patient notified.");
}
window.cancelAppointmentAdmin = cancelAppointmentAdmin;
window.deleteNotification = deleteNotification;

async function deleteAllNotifications(type) {
    const label = type === "cancelled" ? "all cancelled notifications" : "all rescheduled notifications";
    if (!confirm(`Delete ${label}?`)) return;

    const fd = new FormData();
    fd.append("type", type);

    const res = await fetch(
        "/booking-management/controllers/admin_controllers/delete_all_notifications.php",
        { method: "POST", body: fd }
    );

    let data;
    try { data = await res.json(); } catch { data = { success: false }; }

    if (!data.success) {
        alert(data.msg || "Failed to delete notifications.");
        return;
    }

    if (type === "cancelled") {
        cancelledAppointments = [];
        renderNotificationCards("canceledNotifications", cancelledAppointments);
        const canceledCount = document.getElementById("canceledCount");
        if (canceledCount) canceledCount.textContent = 0;
    } else {
        rescheduledAppointments = [];
        renderNotificationCards("rescheduledNotifications", rescheduledAppointments);
        const rescheduledCount = document.getElementById("rescheduledCount");
        if (rescheduledCount) rescheduledCount.textContent = 0;
    }

    const badge = document.getElementById("sidebarNotifCount");
    if (badge) {
        const total = cancelledAppointments.length + rescheduledAppointments.length;
        badge.style.display = total > 0 ? "inline-flex" : "none";
        badge.textContent = total;
    }

    alert("Deleted successfully.");
}
window.deleteAllNotifications = deleteAllNotifications;
