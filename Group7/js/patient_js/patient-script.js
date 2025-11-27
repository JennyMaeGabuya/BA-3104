document.addEventListener("DOMContentLoaded", function () {
    console.log("Patient dashboard loaded successfully!");

    const dateInput = document.getElementById("appointmentDate");
    const sessionInput = document.getElementById("appointmentSession");
    const newDateInput = document.getElementById("newAppointmentDate");

    // Booking section (new appointment)
    if (dateInput && sessionInput) {
        dateInput.addEventListener("change", refreshAvailableSlots);
        sessionInput.addEventListener("change", refreshAvailableSlots);
    }

    // Reschedule modal
    if (newDateInput) {
        newDateInput.addEventListener("change", refreshRescheduleSlots);
    }

    initializeDashboard();
});


/* --------------------------------------------------------------
   TIME SLOT HELPERS (15-minute slots)
   AM:  8:00–11:45  (limit 12 patients / day)
   PM: 13:00–16:45  (limit 16 patients / day)
-------------------------------------------------------------- */

function getSessionConfig(session) {
    if (session === "am") {
        return {
            startHour: 8,
            endHour: 11,
            endMinute: 45,
            limit: 12,
            label: "AM"
        };
    } else if (session === "pm") {
        return {
            startHour: 13,
            endHour: 16,
            endMinute: 45,
            limit: 16,
            label: "PM"
        };
    }
    return null;
}

// Build all 15-minute slots for a given session
function buildSessionSlots(session) {
    const config = getSessionConfig(session);
    if (!config) return [];

    const slots = [];
    for (let h = config.startHour; h <= config.endHour; h++) {
        for (let m = 0; m < 60; m += 15) {
            if (h === config.endHour && m > config.endMinute) break;

            const hh = String(h).padStart(2, "0");
            const mm = String(m).padStart(2, "0");

            slots.push({
                value: `${hh}:${mm}:00`,
                label: formatTimeLabel(h, m)
            });
        }
    }
    return slots;
}

/* --------------------------------------------------------------
   REFRESH AVAILABLE SLOTS (date + AM/PM)
-------------------------------------------------------------- */
async function refreshAvailableSlots() {
    const dateInput = document.getElementById("appointmentDate");
    const sessionInput = document.getElementById("appointmentSession");
    const timeSelect = document.getElementById("appointmentTime");
    const info = document.getElementById("slotInfo");

    if (!dateInput || !sessionInput || !timeSelect || !info) return;

    const date = dateInput.value;
    const session = sessionInput.value;

    // Reset
    timeSelect.innerHTML = '<option value="">Select time</option>';
    timeSelect.disabled = true;
    info.textContent = "";

    if (!date || !session) return;

    const config = getSessionConfig(session);
    if (!config) {
        info.textContent = "Invalid session selected.";
        return;
    }

    const formData = new FormData();
    formData.append("appointmentDate", date);
    formData.append("session", session);

    try {
        const res = await fetch("../../controllers/get_slot_usage.php", {
            method: "POST",
            body: formData,
        });

        const result = await res.json();
        if (!result.success) {
            info.textContent = "Unable to load available slots.";
            console.error(result.msg);
            return;
        }

        const takenTimes = result.times || [];      // array of "HH:MM:SS"
        const bookedCount = result.count || 0;
        const limit = result.limit || config.limit;
        const remaining = Math.max(limit - bookedCount, 0);

        if (bookedCount >= limit) {
            info.textContent = `This ${config.label} session is fully booked. Please choose another date or session.`;
            return;
        }

        const allSlots = buildSessionSlots(session);
        let added = 0;

        allSlots.forEach(slot => {
            if (!takenTimes.includes(slot.value)) {
                const opt = document.createElement("option");
                opt.value = slot.value;
                opt.textContent = slot.label;
                timeSelect.appendChild(opt);
                added++;
            }
        });

        if (added === 0) {
            info.textContent = `No available time slots left in this ${config.label} session.`;
            return;
        }

        timeSelect.disabled = false;
        info.textContent = `${remaining} appointment slot(s) remaining for this ${config.label} session.`;

    } catch (err) {
        console.error("Error loading session slots:", err);
        info.textContent = "Unable to load available slots right now.";
    }
}

/* --------------------------------------------------------------
   TIME LABEL HELPERS
-------------------------------------------------------------- */

// Format 24h hour + minute to 12h label (for dropdown)
function formatTimeLabel(hour24, minute) {
    const ampm = hour24 >= 12 ? "PM" : "AM";
    let hour12 = hour24 % 12;
    if (hour12 === 0) hour12 = 12;
    const mm = String(minute).padStart(2, "0");
    return `${hour12}:${mm} ${ampm}`;
}

// Format DB time string ("HH:MM:SS") to "h:mm AM/PM" (for tables)
function formatTimeFromDB(timeString) {
    if (!timeString) return "";
    const parts = timeString.split(":");
    if (parts.length < 2) return timeString;
    const h = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10);
    if (Number.isNaN(h) || Number.isNaN(m)) return timeString;
    return formatTimeLabel(h, m);
}

/* --------------------------------------------------------------
   GLOBALS
-------------------------------------------------------------- */
let currentAppointmentId = null;
window.currentUserData = null;

/* --------------------------------------------------------------
   INITIALIZE DASHBOARD
-------------------------------------------------------------- */
function initializeDashboard() {
    console.log("Initializing patient dashboard...");

    fetch("../../controllers/get_current_user.php")
        .then((res) => res.json())
        .then((user) => {
            if (!user.logged_in) {
                alert("Session expired. Please login again.");
                window.location.href = "../auth/login.php";
                return;
            }

            window.currentUserData = user;

            document.getElementById("headerUserName").textContent = user.name;

            // Update profile section only if visible
            if (document.getElementById("profileFullName")) {
                updateProfileDisplay(user);
            }

            // Fill booking form
            fillBookingForm(user);
            loadBookedAppointments();
            loadCancelledAppointments();
        });
}

/* --------------------------------------------------------------
   AUTO-FILL BOOKING FORM FROM PROFILE
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
   BOOK APPOINTMENT
-------------------------------------------------------------- */
async function handleBooking(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append("name", document.getElementById("name").value.trim());
    formData.append("contactNo", document.getElementById("contactNo").value.trim());
    formData.append("email", document.getElementById("email").value.trim());
    formData.append("age", document.getElementById("age").value.trim());
    formData.append("gender", document.getElementById("gender").value);
    formData.append("address", document.getElementById("address").value.trim());
    formData.append("dateOfBirth", document.getElementById("dateOfBirth").value);
    formData.append("appointmentDate", document.getElementById("appointmentDate").value);
    formData.append("appointmentTime", document.getElementById("appointmentTime").value);
    formData.append("reason", document.getElementById("reason").value);

    try {
        const res = await fetch("../../controllers/booking_controller.php", {
            method: "POST",
            body: formData
        });

        const result = await res.json();
        console.log("booking result:", result);
        alert(result.msg);

        if (result.success) {
            await loadBookedAppointments?.();
            document.getElementById("appointmentTime").value = "";
            document.getElementById("reason").value = "";
            document.getElementById("appointmentSession").value = "";
            document.getElementById("slotInfo").textContent = "";
        }
    } catch (err) {
        console.error("Booking error:", err);
        alert("Booking failed.");
    }
}

/* --------------------------------------------------------------
   SWITCH SECTION (Overview / Booking / Profile)
-------------------------------------------------------------- */
function switchSection(sectionId) {
    document.querySelectorAll(".content-section").forEach(section => {
        section.classList.remove("active");
    });
    document.getElementById(sectionId).classList.add("active");

    document.querySelectorAll(".nav-item").forEach(n => n.classList.remove("active"));
    document.querySelector(`[onclick="switchSection('${sectionId}')"]`)
        .classList.add("active");

    if (sectionId === "overview") {
        loadBookedAppointments();
        loadCancelledAppointments();
    } else {
        document.getElementById("pendingTable").innerHTML = "";
        document.getElementById("approvedTable").innerHTML = "";
        document.getElementById("cancelledTable").innerHTML = "";
    }
}

/* --------------------------------------------------------------
   TOGGLE SIDEBAR
-------------------------------------------------------------- */
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("sidebarOverlay").classList.toggle("active");
}

/* --------------------------------------------------------------
   LOAD APPOINTMENTS FOR CURRENT USER
-------------------------------------------------------------- */
async function loadBookedAppointments() {
    try {
        const response = await fetch("../../controllers/get_user_appointments.php");
        const appointments = await response.json();

        console.log("Loaded appointments:", appointments);

        const pending = appointments.filter(
            (a) => a.status === "pending" || a.status === "rescheduled_pending"
        );
        const approved = appointments.filter((a) => a.status === "accepted");

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
   UPDATE APPOINTMENT TABLES
-------------------------------------------------------------- */
function updateAppointmentTable(tableId, appointments, showActions) {
    const tableBody = document.getElementById(tableId);
    tableBody.innerHTML = "";

    if (appointments.length === 0) {
        tableBody.innerHTML =
            '<tr><td colspan="10" class="empty-state">No appointments found</td></tr>';
        return;
    }

    appointments.forEach((app) => {
        if (tableId === "bookedTable") {
            tableBody.innerHTML += `
                <tr>
                    <td>${formatDate(app.appointment_date)}</td>
                    <td>${formatTimeFromDB(app.appointment_time)}</td>
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

        tableBody.innerHTML += `
            <tr>
                <td>${formatDate(app.appointment_date)}</td>
                <td>${formatTimeFromDB(app.appointment_time)}</td>
                <td>${app.reason}</td>
                <td>${app.name}</td>
                <td>${app.contact_no}</td>
                <td>${app.email}</td>
                <td>${app.age}</td>
                <td>${formatDate(app.date_of_birth)}</td>
                ${showActions
                ? `
                    <td class="action-buttons">
                        <button class="btn-action btn-reschedule" onclick="openRescheduleModal('${app.appointment_id}')">⟳</button>
                        <button class="btn-action btn-cancel" onclick="cancelAppointment('${app.appointment_id}')">✖</button>
                    </td>`
                : "<td>-</td>"
            }
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
            body: formData,
        });

        const result = await res.json();
        alert(result.msg);

        if (result.success) {
            await loadBookedAppointments();
            await loadCancelledAppointments();
        }

    } catch (err) {
        console.error(err);
        alert("Cancellation failed.");
    }
}


/* --------------------------------------------------------------
   RESCHEDULE: ONLY SHOW FREE SLOTS FOR SELECTED DATE
-------------------------------------------------------------- */
async function refreshRescheduleSlots() {
    const dateInput = document.getElementById("newAppointmentDate");
    const timeSelect = document.getElementById("newAppointmentTime");

    if (!dateInput || !timeSelect) return;

    const date = dateInput.value;

    // Reset
    timeSelect.innerHTML = '<option value="">Select time</option>';
    timeSelect.disabled = true;

    if (!date) return;

    const formData = new FormData();
    formData.append("appointmentDate", date);
    formData.append("day_only", "1"); // special mode for reschedule
    if (currentAppointmentId) {
        formData.append("exclude_appointment_id", currentAppointmentId);
    }

    try {
        const res = await fetch("../../controllers/get_slot_usage.php", {
            method: "POST",
            body: formData,
        });

        const result = await res.json();
        if (!result.success) {
            console.error("Reschedule slots error:", result.msg);
            return;
        }

        const takenTimes = result.times || []; // array of "HH:MM:SS"
        const allSlots = [
            ...buildSessionSlots("am"),
            ...buildSessionSlots("pm"),
        ];

        let added = 0;
        allSlots.forEach(slot => {
            if (!takenTimes.includes(slot.value)) {
                const opt = document.createElement("option");
                opt.value = slot.value;
                opt.textContent = slot.label;
                timeSelect.appendChild(opt);
                added++;
            }
        });

        if (added === 0) {
            const opt = document.createElement("option");
            opt.value = "";
            opt.textContent = "No available times for this date";
            timeSelect.appendChild(opt);
        } else {
            timeSelect.disabled = false;
        }

    } catch (err) {
        console.error("Error loading reschedule slots:", err);
    }
}


/* --------------------------------------------------------------
   RESCHEDULE FUNCTIONS
-------------------------------------------------------------- */
function openRescheduleModal(appointmentId) {
    currentAppointmentId = appointmentId;
    document.getElementById("rescheduleModal").style.display = "flex";

    const dateInput = document.getElementById("newAppointmentDate");
    const timeSelect = document.getElementById("newAppointmentTime");

    if (dateInput && timeSelect) {
        // Clear previous values
        // (user chooses date, then we load free slots)
        if (!dateInput.value) {
            timeSelect.innerHTML = '<option value="">Select time</option>';
            timeSelect.disabled = true;
        } else {
            // If date is already set, immediately load available slots
            refreshRescheduleSlots();
        }
    }
}


async function saveReschedule() {
    const newDate = document.getElementById("newAppointmentDate").value;
    const newTime = document.getElementById("newAppointmentTime").value;

    if (!newDate || !newTime) {
        alert("Please select both date and time.");
        return;
    }

    const formData = new FormData();
    formData.append("appointment_id", currentAppointmentId);
    formData.append("new_date", newDate);
    formData.append("new_time", newTime);

    try {
        const res = await fetch("../../controllers/reschedule_appointment.php", {
            method: "POST",
            body: formData,
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
    if (!document.getElementById("profileFullName")) return;

    document.getElementById("profileFullName").textContent = user.name;
    document.getElementById("profileName").textContent = user.name;
    document.getElementById("profileEmail").textContent = user.email;
    document.getElementById("profileContact").textContent =
        user.phone || "Not provided";
    document.getElementById("profileGender").textContent =
        user.gender || "Not provided";
    document.getElementById("profileAddress").textContent =
        user.address || "Not provided";
    document.getElementById("profileDOB").textContent = user.date_of_birth
        ? formatDate(user.date_of_birth)
        : "Not provided";

    updateAvatarInitials(user.name);
}

/* --------------------------------------------------------------
   EDIT PROFILE
-------------------------------------------------------------- */
function editProfile() {
    const u = window.currentUserData;

    const profileCard = document.querySelector(".profile-card");
    profileCard.innerHTML = `
        <div class="edit-profile-form">
            <h3>Edit Profile</h3>

            <form id="profileForm">

                <label>Full Name</label>
                <input type="text" id="editName" class="form-input" value="${u.name}" required>

                <label>Email</label>
                <input type="email" id="editEmail" class="form-input" value="${u.email}" required>

                <label>Contact No.</label>
                <input type="text" id="editPhone" class="form-input" value="${u.phone ?? ""}" required>

                <label>Gender</label>
                <select id="editGender" class="form-input">
                    <option value="">Select gender</option>
                    <option value="Male" ${u.gender === "Male" ? "selected" : ""}>Male</option>
                    <option value="Female" ${u.gender === "Female" ? "selected" : ""}>Female</option>
                    <option value="Other" ${u.gender === "Other" ? "selected" : ""}>Other</option>
                </select>

                <label>Address</label>
                <input type="text" id="editAddress" class="form-input" value="${u.address ?? ""}" required>

                <label>Date of Birth</label>
                <input type="date" id="editDOB" class="form-input" value="${u.date_of_birth ?? ""}" required>

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
        body: formData,
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
    switchSection("profile");
    document.querySelector(".profile-card").innerHTML = `
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
    if (isNaN(d)) return dateString || "";
    return d.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
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

    document
        .querySelectorAll(".avatar, .profile-avatar")
        .forEach((el) => (el.textContent = initials));
}

/* --------------------------------------------------------------
   LOGOUT
-------------------------------------------------------------- */
function logout() {
    fetch("../../controllers/logout.php")
        .then((res) => res.json())
        .then((data) => {
            alert(data.msg);
            window.location.href = "../auth/login.php";
        })
        .catch(() => {
            window.location.href = "../auth/login.php";
        });
}

/* --------------------------------------------------------------
   LOAD CANCELLED APPOINTMENTS
-------------------------------------------------------------- */
async function loadCancelledAppointments() {
    try {
        const res = await fetch("../../controllers/get_cancelled_appointments.php");
        const cancelled = await res.json();

        document.getElementById("cancelledCount").textContent = cancelled.length;

        const tbody = document.getElementById("cancelledTable");
        tbody.innerHTML = "";

        if (cancelled.length === 0) {
            tbody.innerHTML =
                `<tr><td colspan="6" class="empty-state">No cancelled appointments</td></tr>`;
            return;
        }

        cancelled.forEach(app => {
            tbody.innerHTML += `
                <tr>
                    <td>${formatDate(app.appointment_date)}</td>
                    <td>${formatTimeFromDB(app.appointment_time)}</td>
                    <td>${app.reason}</td>
                    <td>${app.name}</td>
                    <td>${app.contact_no}</td>
                    <td>${app.email}</td>
                </tr>
            `;
        });

    } catch (error) {
        console.error("Error loading cancelled appointments:", error);
    }
}
