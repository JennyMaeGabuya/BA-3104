// Patient Dashboard Script
document.addEventListener('DOMContentLoaded', function () {
    console.log('Patient dashboard loaded successfully!');

    initializeDashboard();
    loadBookedAppointments();
});

let currentAppointmentId = null;
window.currentUserData = null;

/* --------------------------------------------------------------
   INITIALIZE DASHBOARD
-------------------------------------------------------------- */
function initializeDashboard() {
    console.log('Initializing patient dashboard...');

    fetch("../../controllers/get_current_user.php")
        .then(res => res.json())
        .then(user => {

            if (!user.logged_in) {
                alert("Session expired. Please login again.");
                window.location.href = "../auth/login.php";
                return;
            }

            window.currentUserData = user;

            document.getElementById('headerUserName').textContent = user.name;

            // Update profile section only if visible
            if (document.getElementById('profileFullName')) {
                updateProfileDisplay(user);
            }

            // Fill booking form
            fillBookingForm(user);
        });
}

/* --------------------------------------------------------------
   AUTO-FILL BOOKING FORM
-------------------------------------------------------------- */
function fillBookingForm(user) {
    if (document.getElementById("name")) {
        document.getElementById("name").value = user.name;
        document.getElementById("email").value = user.email;
        document.getElementById("contactNo").value = user.phone || "";
        document.getElementById("gender").value = user.gender || "";
        document.getElementById("address").value = user.address || "";
        document.getElementById("dateOfBirth").value = user.date_of_birth || "";
        document.getElementById("age").value = calculateAge(user.date_of_birth);
    }
}

/* --------------------------------------------------------------
   BOOK APPOINTMENT  (FIXED)
-------------------------------------------------------------- */
async function handleBooking(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append("name", document.getElementById("name").value);
    formData.append("contactNo", document.getElementById("contactNo").value);
    formData.append("email", document.getElementById("email").value);
    formData.append("age", document.getElementById("age").value);
    formData.append("gender", document.getElementById("gender").value);
    formData.append("address", document.getElementById("address").value);
    formData.append("dateOfBirth", document.getElementById("dateOfBirth").value);
    formData.append("appointmentDate", document.getElementById("appointmentDate").value);
    formData.append("reason", document.getElementById("reason").value);

    const res = await fetch("../../controllers/booking_controller.php", {
        method: "POST",
        body: formData
    });

    const result = await res.json();
    alert(result.msg);

    if (result.success) {

        // 🔥 Reload appointments
        loadBookedAppointments();

        // 🔥 Reload user data after booking
        await initializeDashboard();

        // 🔥 Auto-fill booking form again after updating user data
        fillBookingForm(window.currentUserData);

        // Clear only appointment-specific fields (NOT user data)
        document.getElementById("appointmentDate").value = "";
        document.getElementById("reason").value = "";
    }
}


/* --------------------------------------------------------------
   SWITCH SECTION
-------------------------------------------------------------- */
function switchSection(sectionId) {
    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

    document.getElementById(sectionId).classList.add('active');
    document.querySelector(`[onclick="switchSection('${sectionId}')"]`).classList.add('active');

    if (sectionId === "overview") {
        loadBookedAppointments();
    }
}

/* --------------------------------------------------------------
   TOGGLE SIDEBAR
-------------------------------------------------------------- */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}

/* --------------------------------------------------------------
   LOAD APPOINTMENTS
-------------------------------------------------------------- */
async function loadBookedAppointments() {
    try {
        const response = await fetch("../../controllers/get_user_appointments.php");
        const appointments = await response.json();

        console.log("Loaded appointments:", appointments);

        const pending = appointments.filter(a => a.status === "pending");
        const approved = appointments.filter(a => a.status === "approved");

        document.getElementById("pendingCount").textContent = pending.length;
        document.getElementById("approvedCount").textContent = approved.length;

        updateAppointmentTable("pendingTable", pending, true);
        updateAppointmentTable("approvedTable", approved, true);
        updateAppointmentTable("bookedTable", appointments, false);

    } catch (error) {
        console.error("Error loading appointments:", error);
    }
}

/* --------------------------------------------------------------
   UPDATE APPOINTMENT TABLE  (FIXED)
-------------------------------------------------------------- */
function updateAppointmentTable(tableId, appointments, showActions) {
    const tableBody = document.getElementById(tableId);
    tableBody.innerHTML = "";

    if (appointments.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="10" class="empty-state">No appointments found</td></tr>`;
        return;
    }

    appointments.forEach(app => {

        // FULL TABLE FOR bookedTable
        if (tableId === "bookedTable") {
            tableBody.innerHTML += `
                <tr>
                    <td>${formatDate(app.appointment_date)}</td>
                    <td>${app.reason}</td>
                    <td>${app.name}</td>
                    <td>${app.contact_no}</td>
                    <td>${app.email}</td>
                    <td>${app.gender}</td>
                    <td>${app.age}</td>
                    <td>${formatDate(app.date_of_birth)}</td>
                    <td>${app.address}</td>
                </tr>
            `;
            return;
        }

        // PENDING + APPROVED TABLES
        tableBody.innerHTML += `
            <tr>
                <td>${formatDate(app.appointment_date)}</td>
                <td>${app.reason}</td>
                <td>${app.name}</td>
                <td>${app.contact_no}</td>
                <td>${app.email}</td>
                <td>${app.age}</td>
                <td>${formatDate(app.date_of_birth)}</td>

                ${showActions ? `
                    <td class="action-buttons">
                        <button class="btn-action btn-reschedule" onclick="openRescheduleModal('${app.appointment_id}')">⟳</button>
                        <button class="btn-action btn-cancel" onclick="cancelAppointment('${app.appointment_id}')">✖</button>
                    </td>
                ` : "<td>-</td>"}
            </tr>
        `;
    });
}

/* --------------------------------------------------------------
   CANCEL APPOINTMENT
-------------------------------------------------------------- */
async function cancelAppointment(appointmentId) {
    if (!confirm("Cancel this appointment?")) return;

    const formData = new FormData();
    formData.append("appointment_id", appointmentId);

    try {
        const res = await fetch("../../controllers/cancel_appointment.php", {
            method: "POST",
            body: formData
        });

        const result = await res.json();
        alert(result.msg);

        if (result.success) loadBookedAppointments();

    } catch (err) {
        console.error(err);
        alert("Cancellation failed.");
    }
}

/* --------------------------------------------------------------
   RESCHEDULE FUNCTIONS
-------------------------------------------------------------- */
function openRescheduleModal(appointmentId) {
    currentAppointmentId = appointmentId;
    document.getElementById("rescheduleModal").style.display = "flex";
}

async function saveReschedule() {
    const newDate = document.getElementById("newAppointmentDate").value;

    if (!newDate) {
        alert("Please select a date.");
        return;
    }

    const formData = new FormData();
    formData.append("appointment_id", currentAppointmentId);
    formData.append("new_date", newDate);

    try {
        const res = await fetch("../../controllers/reschedule_appointment.php", {
            method: "POST",
            body: formData
        });

        const result = await res.json();
        alert(result.msg);

        if (result.success) {
            closeRescheduleModal();
            loadBookedAppointments();
        }

    } catch (err) {
        console.error(err);
        alert("Reschedule failed.");
    }
}

function closeRescheduleModal() {
    document.getElementById("rescheduleModal").style.display = "none";
    document.getElementById("newAppointmentDate").value = "";
}

/* --------------------------------------------------------------
   PROFILE DISPLAY
-------------------------------------------------------------- */
function updateProfileDisplay(user) {
    if (!document.getElementById('profileFullName')) return;

    document.getElementById('profileFullName').textContent = user.name;
    document.getElementById('profileName').textContent = user.name;
    document.getElementById('profileEmail').textContent = user.email;
    document.getElementById('profileContact').textContent = user.phone || "Not provided";
    document.getElementById('profileGender').textContent = user.gender || "Not provided";
    document.getElementById('profileAddress').textContent = user.address || "Not provided";
    document.getElementById('profileDOB').textContent =
        user.date_of_birth ? formatDate(user.date_of_birth) : "Not provided";

    updateAvatarInitials(user.name);
}

/* --------------------------------------------------------------
   EDIT PROFILE
-------------------------------------------------------------- */
function editProfile() {
    const u = window.currentUserData;

    const profileCard = document.querySelector('.profile-card');
    profileCard.innerHTML = `
        <div class="edit-profile-form">
            <h3>Edit Profile</h3>

            <form id="profileForm">

                <label>Full Name</label>
                <input type="text" id="editName" class="form-input" value="${u.name}" required>

                <label>Email</label>
                <input type="email" id="editEmail" class="form-input" value="${u.email}" required>

                <label>Contact No.</label>
                <input type="text" id="editPhone" class="form-input" value="${u.phone ?? ''}" required>

                <label>Gender</label>
                <select id="editGender" class="form-input">
                    <option value="">Select gender</option>
                    <option value="Male" ${u.gender === "Male" ? "selected" : ""}>Male</option>
                    <option value="Female" ${u.gender === "Female" ? "selected" : ""}>Female</option>
                    <option value="Other" ${u.gender === "Other" ? "selected" : ""}>Other</option>
                </select>

                <label>Address</label>
                <input type="text" id="editAddress" class="form-input" value="${u.address ?? ''}" required>

                <label>Date of Birth</label>
                <input type="date" id="editDOB" class="form-input" value="${u.date_of_birth ?? ''}" required>

                <div class="modal-actions">
                    <button type="button" onclick="loadProfileSection()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    `;

    document.getElementById("profileForm").addEventListener("submit", saveProfile);
}

/* --------------------------------------------------------------
   SAVE PROFILE
-------------------------------------------------------------- */
async function saveProfile(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append("name", document.getElementById("editName").value);
    formData.append("email", document.getElementById("editEmail").value);
    formData.append("phone", document.getElementById("editPhone").value);
    formData.append("gender", document.getElementById("editGender").value);
    formData.append("address", document.getElementById("editAddress").value);
    formData.append("date_of_birth", document.getElementById("editDOB").value);

    const response = await fetch("../../controllers/update_profile.php", {
        method: "POST",
        body: formData
    });

    const result = await response.json();
    alert(result.msg);

    if (result.success) {
        await initializeDashboard();
        loadProfileSection();
    }
}

/* --------------------------------------------------------------
   RESTORE PROFILE LAYOUT
-------------------------------------------------------------- */
function loadProfileSection() {
    switchSection('profile');
    document.querySelector('.profile-card').innerHTML = `
        <div class="profile-header">
            <div class="profile-avatar-section">
                <div class="profile-avatar"></div>
                <div class="profile-user-info">
                    <h3 id="profileFullName"></h3>
                    <p class="profile-role">Patient</p>
                </div>
            </div>
            <button class="btn btn-primary" onclick="editProfile()">Edit Profile</button>
        </div>

        <div class="profile-info-grid">
            <div class="profile-info-item">
                <span class="info-title">Full Name</span>
                <p id="profileName"></p>
            </div>
            <div class="profile-info-item">
                <span class="info-title">Email</span>
                <p id="profileEmail"></p>
            </div>
            <div class="profile-info-item">
                <span class="info-title">Gender</span>
                <p id="profileGender"></p>
            </div>
            <div class="profile-info-item">
                <span class="info-title">Contact No.</span>
                <p id="profileContact"></p>
            </div>
            <div class="profile-info-item">
                <span class="info-title">Address</span>
                <p id="profileAddress"></p>
            </div>
            <div class="profile-info-item">
                <span class="info-title">Date of Birth</span>
                <p id="profileDOB"></p>
            </div>
        </div>
    `;

    initializeDashboard();
}

/* --------------------------------------------------------------
   HELPERS
-------------------------------------------------------------- */
function calculateAge(dob) {
    if (!dob) return "";
    const d = new Date(dob);
    const now = new Date();
    return now.getFullYear() - d.getFullYear();
}

function formatDate(dateString) {
    const d = new Date(dateString);
    return d.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric"
    });
}

/* --------------------------------------------------------------
   AVATAR INITIALS
-------------------------------------------------------------- */
function updateAvatarInitials(fullName) {
    if (!fullName) return;

    const parts = fullName.trim().split(" ");
    let initials = parts[0][0];

    if (parts.length > 1) {
        initials += parts[1][0];
    }

    initials = initials.toUpperCase();

    document.querySelectorAll('.avatar, .profile-avatar')
        .forEach(el => el.textContent = initials);
}

function logout() {
    fetch("../../controllers/logout.php")
        .then(res => res.json())
        .then(data => {
            alert(data.msg);
            window.location.href = "../auth/login.php"; // redirect after logout
        })
        .catch(() => {
            window.location.href = "../auth/login.php";
        });
}
