function formatTime(timeString) {
    if (!timeString) return '';

    const [h, m] = timeString.split(':');
    const hour = parseInt(h);
    const minute = parseInt(m);

    const ampm = hour >= 12 ? "PM" : "AM";
    const hour12 = hour % 12 === 0 ? 12 : hour % 12;

    return `${hour12}:${String(minute).padStart(2, '0')} ${ampm}`;
}

let appointments = [];
let medicalRecords = [];
let cancelledAppointments = []; // NEW: separate list for cancelled
let currentAppointmentId = null;

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
                    status: row.status
                }));

                saveData();
                loadAppointments();
                loadNotifications();
            }
        })
        .catch(err => console.error("Error loading appointments:", err));

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

// --------------------------------------------------------------
// LOAD APPOINTMENTS TABLE (NO MORE ACCEPT/DECLINE)
// --------------------------------------------------------------
function loadAppointments() {
    const tbody = document.getElementById('appointmentTable');
    const count = document.getElementById('appointmentCount');

    count.textContent = appointments.length;

    if (appointments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="empty-state">No appointments found</td></tr>';
        return;
    }

    tbody.innerHTML = appointments.map(apt => {
        const appointmentDate = new Date(apt.date);
        const formattedDate = appointmentDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });

        let statusBadge = '';
        let statusActions = '';

        // We no longer manually accept/decline.
        // Treat all non-completed as "Scheduled" / "Rescheduled".
        if (apt.status === 'completed') {
            statusBadge = `<span class="badge badge-completed">Completed</span>`;
            statusActions = '';
        } else if (apt.status === 'rescheduled_pending') {
            statusBadge = `<span class="badge badge-warning">Rescheduled</span>`;
            statusActions = `
                <button class="btn btn-sm btn-primary" onclick="openNoteModal(${apt.id})">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Add Note
                </button>
            `;
        } else {
            // pending / accepted → both treated as scheduled
            statusBadge = `<span class="badge badge-accepted">Scheduled</span>`;
            statusActions = `
                <button class="btn btn-sm btn-primary" onclick="openNoteModal(${apt.id})">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Add Note
                </button>
            `;
        }

        return `
            <tr>
                <td>${formattedDate}</td>
                <td>${apt.time}</td>
                <td>${apt.reason}</td>
                <td><strong>${apt.fullName}</strong></td>
                <td>${apt.contactNo}</td>
                <td>${apt.email}</td>
                <td>${apt.age}</td>
                <td>${apt.gender}</td>
                <td>
                    <div class="status-cell">
                        ${statusBadge}
                        ${statusActions}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// --------------------------------------------------------------
// NOTIFICATIONS: CANCELLED + RESCHEDULED
// --------------------------------------------------------------
async function loadNotifications() {
    try {
        const res = await fetch("/booking-management/controllers/admin_controllers/get_cancelled_admin.php");

        const json = await res.json();

        if (json.success && Array.isArray(json.data)) {
            cancelledAppointments = json.data.map(row => ({
                id: row.id,
                date: row.date,
                time: formatTime(row.time),
                fullName: row.fullName,
                email: row.email,
                statusLabel: 'Cancelled'
            }));
        } else {
            cancelledAppointments = [];
        }
    } catch (err) {
        console.error("Error loading cancelled appointments:", err);
        cancelledAppointments = [];
    }

    // 2) Rescheduled appointments: from active appointments list
    const rescheduledAppointments = appointments
        .filter(apt => apt.status === 'rescheduled_pending')
        .map(apt => ({
            ...apt,
            statusLabel: 'Rescheduled'
        }));

    const canceledCount = document.getElementById('canceledCount');
    const rescheduledCount = document.getElementById('rescheduledCount');

    if (canceledCount) canceledCount.textContent = cancelledAppointments.length;
    if (rescheduledCount) rescheduledCount.textContent = rescheduledAppointments.length;

    updateNotificationTable('canceledTable', cancelledAppointments);
    updateNotificationTable('rescheduledTable', rescheduledAppointments);
}

// Update notification table
function updateNotificationTable(tableId, rows) {
    const tbody = document.getElementById(tableId);
    if (!tbody) return;

    if (!rows || rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No appointments found</td></tr>';
        return;
    }

    tbody.innerHTML = rows.map(apt => {
        const appointmentDate = new Date(apt.date);
        const formattedDate = appointmentDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });

        const label = apt.statusLabel || 'Notification';
        const isCancelled = label === 'Cancelled';
        const badgeClass = isCancelled ? 'badge-declined' : 'badge-warning';

        return `
            <tr>
                <td><strong>${apt.fullName}</strong></td>
                <td>${formattedDate}</td>
                <td>${apt.time}</td>
                <td>${apt.email}</td>
                <td>
                    <span class="badge ${badgeClass}">
                        ${label}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="deleteNotification(${apt.id}, '${label}')">
                        ✖ Delete
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// --------------------------------------------------------------
// MEDICAL RECORDS (unchanged)
// --------------------------------------------------------------
function loadMedicalRecords() {
    const tbody = document.getElementById('medicalRecordsTable');
    const count = document.getElementById('recordsCount');

    count.textContent = medicalRecords.length;

    if (medicalRecords.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No medical records found</td></tr>';
        return;
    }

    tbody.innerHTML = medicalRecords.map(record => {
        const recordDate = new Date(record.date);
        const formattedDate = recordDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });

        return `
            <tr>
                <td>${formattedDate}</td>
                <td>${record.time}</td>
                <td><strong>${record.fullName}</strong></td>
                <td>${record.reason}</td>
                <td>${record.doctorNote}</td>
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

function saveDoctorNote() {
    if (!currentAppointmentId) return;

    const appointment = appointments.find(apt => apt.id === currentAppointmentId);
    const note = document.getElementById('doctorNote').value.trim();

    if (!note) {
        alert('Please enter a doctor\'s note.');
        return;
    }

    if (appointment) {
        const medicalRecord = {
            id: medicalRecords.length + 1,
            date: appointment.date,
            time: appointment.time,
            fullName: appointment.fullName,
            reason: appointment.reason,
            doctorNote: note
        };

        medicalRecords.push(medicalRecord);

        // Mark as completed (local only)
        appointment.status = 'completed';

        saveData();
        loadAppointments();
        loadMedicalRecords();
        loadNotifications();
        closeNoteModal();

        alert(`Medical record created for ${appointment.fullName}.`);
    }
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
// DELETE NOTIFICATION (ONLY FROM VIEW, NOT DB)
// --------------------------------------------------------------
function deleteNotification(id, type) {
    if (!confirm("Delete this notification from the list?")) return;

    if (type === 'Cancelled') {
        cancelledAppointments = cancelledAppointments.filter(a => a.id !== id);
    }
    // For rescheduled, we don't remove from DB or appointments; just refresh list
    loadNotifications();
    alert("Notification removed.");
}

// --------------------------------------------------------------
document.addEventListener('DOMContentLoaded', init);
