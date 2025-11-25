// Empty appointments array - ready for backend integration
let appointments = [];

// Empty medical records array - ready for backend integration
let medicalRecords = [];

let currentAppointmentId = null;

// Initialize the application
function init() {
    loadAppointments();
    loadMedicalRecords();
    loadNotifications();

    // Load from localStorage if available
    const storedAppointments = localStorage.getItem('adminAppointments');
    if (storedAppointments) {
        appointments = JSON.parse(storedAppointments);
        loadAppointments();
        loadNotifications();
    }

    const storedRecords = localStorage.getItem('medicalRecords');
    if (storedRecords) {
        medicalRecords = JSON.parse(storedRecords);
        loadMedicalRecords();
    }
}

// Save to localStorage
function saveData() {
    localStorage.setItem('adminAppointments', JSON.stringify(appointments));
    localStorage.setItem('medicalRecords', JSON.stringify(medicalRecords));
}

// Load appointments into table
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

        if (apt.status === 'pending') {
            statusBadge = `<span class="badge badge-pending">Pending</span>`;
            statusActions = `
                <div class="status-actions">
                    <button class="btn btn-sm btn-success" onclick="openStatusModal(${apt.id})">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Review
                    </button>
                </div>
            `;
        } else if (apt.status === 'accepted') {
            statusBadge = `<span class="badge badge-accepted">Accepted</span>`;
            statusActions = `
                <button class="btn btn-sm btn-primary" onclick="openNoteModal(${apt.id})">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Add Note
                </button>
            `;
        } else if (apt.status === 'declined') {
            statusBadge = `<span class="badge badge-declined">Declined</span>`;
        } else if (apt.status === 'completed') {
            statusBadge = `<span class="badge badge-completed">Completed</span>`;
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

// Load notifications data
function loadNotifications() {
    const canceledAppointments = appointments.filter(apt => apt.status === 'declined');
    const rescheduledAppointments = appointments.filter(apt => apt.status === 'rescheduled');

    const canceledCount = document.getElementById('canceledCount');
    const rescheduledCount = document.getElementById('rescheduledCount');

    if (canceledCount) canceledCount.textContent = canceledAppointments.length;
    if (rescheduledCount) rescheduledCount.textContent = rescheduledAppointments.length;

    updateNotificationTable('canceledTable', canceledAppointments);
    updateNotificationTable('rescheduledTable', rescheduledAppointments);
}

// Update notification table
function updateNotificationTable(tableId, appointments) {
    const tbody = document.getElementById(tableId);
    if (!tbody) return;

    if (appointments.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="empty-state">No appointments found</td></tr>';
        return;
    }

    tbody.innerHTML = appointments.map(apt => {
        const appointmentDate = new Date(apt.date);
        const formattedDate = appointmentDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });

        return `
            <tr>
                <td><strong>${apt.fullName}</strong></td>
                <td>${formattedDate}</td>
                <td>${apt.time}</td>
                <td>${apt.email}</td>
                <td>
                    <span class="badge ${apt.status === 'declined' ? 'badge-declined' : 'badge-warning'}">
                        ${apt.status === 'declined' ? 'Canceled' : 'Rescheduled'}
                    </span>
                </td>
            </tr>
        `;
    }).join('');
}

// Load medical records into table
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

// Toggle sidebar for mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Switch between sections
function switchSection(sectionName) {
    // Update navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    event.target.closest('.nav-item').classList.add('active');

    // Update content
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(sectionName).classList.add('active');

    // Close sidebar on mobile after selection
    if (window.innerWidth <= 768) {
        toggleSidebar();
    }

    // Load section-specific data
    if (sectionName === 'notifications') {
        loadNotifications();
    }
}

// Open status modal
function openStatusModal(appointmentId) {
    currentAppointmentId = appointmentId;
    const appointment = appointments.find(apt => apt.id === appointmentId);

    if (appointment) {
        document.getElementById('modalPatientName').textContent =
            `Update status for ${appointment.fullName}'s appointment on ${appointment.date} at ${appointment.time}`;
        document.getElementById('statusModal').classList.add('active');
    }
}

// Close status modal
function closeModal() {
    document.getElementById('statusModal').classList.remove('active');
    currentAppointmentId = null;
}

// Update appointment status
function updateStatus(status) {
    if (currentAppointmentId) {
        const appointment = appointments.find(apt => apt.id === currentAppointmentId);

        if (appointment) {
            appointment.status = status;
            saveData();
            loadAppointments();
            loadNotifications();
            closeModal();

            const statusText = status === 'accepted' ? 'accepted' : 'declined';
            alert(`Appointment for ${appointment.fullName} has been ${statusText}.`);
        }
    }
}

// Open note modal
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

// Close note modal
function closeNoteModal() {
    document.getElementById('noteModal').classList.remove('active');
    currentAppointmentId = null;
}

// Save doctor's note
function saveDoctorNote() {
    if (currentAppointmentId) {
        const appointment = appointments.find(apt => apt.id === currentAppointmentId);
        const note = document.getElementById('doctorNote').value.trim();

        if (!note) {
            alert('Please enter a doctor\'s note.');
            return;
        }

        if (appointment) {
            // Create medical record
            const medicalRecord = {
                id: medicalRecords.length + 1,
                date: appointment.date,
                time: appointment.time,
                fullName: appointment.fullName,
                reason: appointment.reason,
                doctorNote: note
            };

            medicalRecords.push(medicalRecord);

            // Update appointment status to completed
            appointment.status = 'completed';

            saveData();
            loadAppointments();
            loadMedicalRecords();
            loadNotifications();
            closeNoteModal();

            alert(`Medical record created for ${appointment.fullName}.`);
        }
    }
}

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../auth/login.php';
    }
}

// Close sidebar when clicking outside on mobile
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

// Close modals when clicking outside
window.addEventListener('click', function (event) {
    const statusModal = document.getElementById('statusModal');
    const noteModal = document.getElementById('noteModal');

    if (event.target === statusModal) {
        closeModal();
    }

    if (event.target === noteModal) {
        closeNoteModal();
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', init);