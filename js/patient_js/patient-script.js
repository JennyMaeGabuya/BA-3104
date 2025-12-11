document.addEventListener("DOMContentLoaded", function () {
    console.log("Patient dashboard loaded successfully!");

    const dateInput = document.getElementById("appointmentDate");
    const sessionInput = document.getElementById("appointmentSession");
    const newDateInput = document.getElementById("newAppointmentDate");

    if (dateInput && sessionInput) {
        dateInput.addEventListener("change", refreshAvailableSlots);
        sessionInput.addEventListener("change", refreshAvailableSlots);
    }

    if (newDateInput) {
        newDateInput.addEventListener("change", refreshRescheduleSlots);
    }

    /* --------------------------------------------------------------
       AUTO UPDATE AGE (Booking Form)
    -------------------------------------------------------------- */
    const dobBooking = document.getElementById("dateOfBirth");
    const ageBooking = document.getElementById("age");

    if (dobBooking && ageBooking) {
        dobBooking.addEventListener("change", function () {
            ageBooking.value = calculateAge(dobBooking.value);
        });
    }

    /* --------------------------------------------------------------
       AUTO UPDATE AGE (Edit Profile - Dynamic DOM)
    -------------------------------------------------------------- */
    const observer = new MutationObserver(() => {
        const dobProfile = document.getElementById("editDOB");
        const ageProfile = document.getElementById("editAge");

        if (dobProfile && ageProfile) {
            dobProfile.addEventListener("change", function () {
                ageProfile.value = calculateAge(dobProfile.value);
            });
            observer.disconnect();
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
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

        const takenTimes = result.times || [];
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
            const headerUser = document.getElementById("headerUserName");
            if (headerUser) headerUser.textContent = user.name;

            if (document.getElementById("profileFullName")) {
                updateProfileDisplay(user);
            }

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

    let reasonValue = document.getElementById("reason").value;
    if (reasonValue === "other") {
        reasonValue = document.getElementById("otherReasonInput").value.trim();

        if (!reasonValue) {
            alert("Please specify your reason.");
            return;
        }
    }

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
    formData.append("reason", reasonValue);

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
            document.getElementById("reason").value = "";
            document.getElementById("otherReasonInput").value = "";
            document.getElementById("otherReasonGroup").style.display = "none";

            document.getElementById("appointmentTime").value = "";
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
    document.querySelectorAll(".content-section").forEach(sec => {
        sec.classList.toggle("active", sec.id === sectionId);
    });
    document.querySelectorAll(".nav-item[data-section]").forEach(btn => {
        btn.classList.toggle("active", btn.dataset.section === sectionId);
    });
}

document.addEventListener("DOMContentLoaded", () => {
    switchSection("overview");
});

/* --------------------------------------------------------------
   TOGGLE SIDEBAR
-------------------------------------------------------------- */
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("active");
    document.getElementById("sidebarOverlay").classList.toggle("active");
}

function closeSidebar() {
    document.getElementById("sidebar").classList.remove("active");
    document.getElementById("sidebarOverlay").classList.remove("active");
}


/* --------------------------------------------------------------
   UPDATE APPOINTMENT TABLES
-------------------------------------------------------------- */
async function loadBookedAppointments() {
    const response = await fetch("../../controllers/get_user_appointments.php");
    const appointments = await response.json();

    const pending = appointments.filter(a => a.status === "pending" || a.status === "rescheduled_pending");
    renderCardList("pendingList", pending, true);

    document.getElementById("pendingCount").textContent = pending.length;
}

function renderCardList(containerId, items, withActions = false) {
    const container = document.getElementById(containerId);
    container.innerHTML = "";

    if (items.length === 0) {
        container.innerHTML = `<p class="empty-state">No appointments found</p>`;
        return;
    }

    items.forEach(app => {

        const card = document.createElement("div");
        card.classList.add("appointment-item");

        card.innerHTML = `
            <div class="appointment-summary-row">
                <div class="appointment-title">Your Appointment</div>
                <div class="appointment-date-time">
                    <strong>${formatDate(app.appointment_date)}</strong>
                    <span>${formatTimeFromDB(app.appointment_time)}</span>
                </div>
            </div>

            <div class="appointment-details" style="display: none;">
                <p><strong>Reason:</strong> ${app.reason}</p>
                <p><strong>Name:</strong> ${app.name}</p>
                <p><strong>Contact:</strong> ${app.contact_no}</p>
                <p><strong>Email:</strong> ${app.email}</p>
                <p><strong>Age:</strong> ${app.age}</p>
                <p><strong>Gender:</strong> ${app.gender}</p>
                <p><strong>Address:</strong> ${app.address}</p>
                <p><strong>DOB:</strong> ${formatDate(app.date_of_birth)}</p>

                ${withActions ? `
                <div class="appointment-actions">
                    <button class="btn-action btn-reschedule"
                        onclick="openRescheduleModal('${app.appointment_id}')">Reschedule</button>
                    <button class="btn-action btn-cancel"
                        onclick="cancelAppointment('${app.appointment_id}')">Cancel</button>
                </div>` : ""}
            </div>

            <button class="show-more-btn" onclick="toggleDetails(this)">Show More ▼</button>
        `;

        container.appendChild(card);
    });
}

function toggleDetails(btn) {
    const details = btn.parentElement.querySelector(".appointment-details");

    if (details.style.display === "none") {
        details.style.display = "block";
        btn.textContent = "Show Less ▲";
    } else {
        details.style.display = "none";
        btn.textContent = "Show More ▼";
    }
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
    timeSelect.innerHTML = '<option value="">Select time</option>';
    timeSelect.disabled = true;

    if (!date) return;

    const formData = new FormData();
    formData.append("appointmentDate", date);
    formData.append("day_only", "1");
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

        const takenTimes = result.times || [];
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
        if (!dateInput.value) {
            timeSelect.innerHTML = '<option value="">Select time</option>';
            timeSelect.disabled = true;
        } else {
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
            await initializeDashboard();
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
                <label>Age</label>
<input type="number" id="editAge" class="form-input" value="${calculateAge(u.date_of_birth)}" readonly>

                

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
    updateProfileDisplay(window.currentUserData);
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
   LOAD CANCELLED APPOINTMENTS (FULL CARD + RESCHEDULE ONLY)
-------------------------------------------------------------- */
async function loadCancelledAppointments() {
    try {
        const res = await fetch("../../controllers/get_cancelled_appointments.php");
        const cancelled = await res.json();

        document.getElementById("cancelledCount").textContent = cancelled.length;

        const list = document.getElementById("cancelledList");
        if (!list) return;

        list.innerHTML = "";

        if (cancelled.length === 0) {
            list.innerHTML = `<p class="empty-state">No cancelled appointments</p>`;
            return;
        }

        cancelled.forEach(app => {

            const card = document.createElement("div");
            card.classList.add("appointment-item");

            card.innerHTML = `
                <div class="appointment-summary-row">
                    <div class="appointment-title">Cancelled Appointment</div>
                    <div class="appointment-date-time">
                        <strong>${formatDate(app.appointment_date)}</strong>
                        <span>${formatTimeFromDB(app.appointment_time)}</span>
                    </div>
                </div>

                <div class="appointment-details" style="display: none;">
                    <p><strong>Reason:</strong> ${app.reason}</p>
                    <p><strong>Name:</strong> ${app.name}</p>
                    <p><strong>Contact:</strong> ${app.contact_no}</p>
                    <p><strong>Email:</strong> ${app.email}</p>
                    <p><strong>Age:</strong> ${app.age}</p>
                    <p><strong>Gender:</strong> ${app.gender}</p>
                    <p><strong>Address:</strong> ${app.address}</p>
                    <p><strong>DOB:</strong> ${formatDate(app.date_of_birth)}</p>

                    <!-- RESCHEDULE     -->
                    <div class="appointment-actions">
                        <button class="btn-action btn-reschedule"
                            onclick="openRescheduleModal('${app.appointment_id}')">Reschedule</button>
                          <button class="btn-action btn-cancel"
        onclick="deleteAppointment('${app.appointment_id}')">Delete</button>

                            
                    </div>
                </div>

                <button class="show-more-btn" onclick="toggleDetails(this)">Show More ▼</button>
            `;

            list.appendChild(card);
        });

    } catch (error) {
        console.error("Error loading cancelled appointments:", error);
    }
}

function showToast(message) {
    const t = document.getElementById("toast");
    t.innerText = message;
    t.style.display = "block";
    setTimeout(() => t.style.display = "none", 3000);
}

function toggleOtherReason() {
    const reasonSelect = document.getElementById("reason");
    const otherGroup = document.getElementById("otherReasonGroup");

    if (reasonSelect.value === "other") {
        otherGroup.style.display = "block";
    } else {
        otherGroup.style.display = "none";
        document.getElementById("otherReasonInput").value = "";
    }
}

function deleteAppointment(appointmentId) {
    if (!confirm("Are you sure you want to delete this cancelled appointment?")) {
        return;
    }

    fetch("../../controllers/delete_cancelled_appointment.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: "appointment_id=" + appointmentId
    })
        .then(response => response.json())
        .then(data => {
            console.log(data);

            if (data.success) {
                alert("Appointment deleted successfully.");
                loadCancelledAppointments();
            } else {
                alert(data.msg);
            }
        })
        .catch(error => console.error("Error:", error));
}

async function fetchNotifications() {
    const res = await fetch("../../controllers/get_notifications.php");
    return res.json();
}

async function refreshNotificationBadge() {
    try {
        const data = await fetchNotifications();
        const badge = document.getElementById("notifCount");
        if (!badge || !data.success) return;

        const count = data.count || 0;
        badge.textContent = count > 0 ? count : "";
        badge.style.display = count > 0 ? "inline-block" : "none";
        window._latestNotifications = data.notifications || [];
    } catch (e) {
        console.error("refreshNotificationBadge error:", e);
    }
}

function renderNotificationPanel() {
    const panel = document.getElementById("notifPanel");
    const listEl = document.getElementById("notifList");
    if (!panel || !listEl) return;

    const notifications = window._latestNotifications || [];

    if (!notifications.length) {
        listEl.innerHTML = '<p class="notif-empty">No new notifications.</p>';
        return;
    }

    listEl.innerHTML = notifications.map(n => {
        const date = new Date(n.created_at);
        const timeStr = date.toLocaleString();
        const title = n.type === "appointment_completed"
            ? "Appointment completed"
            : "Notification";

        return `
            <div class="notif-item">
                <div class="notif-item-title">${title}</div>
                <div class="notif-item-body">${n.message}</div>
                <div class="notif-item-time">${timeStr}</div>
            </div>
        `;
    }).join("");
}

async function onNotifClick() {
    const panel = document.getElementById("notifPanel");
    if (!panel) return;

    if (!panel.classList.contains("open")) {
        await refreshNotificationBadge();
        renderNotificationPanel();
    }

    panel.classList.toggle("open");
}

async function markAllNotificationsRead() {
    try {
        await fetch("../../controllers/mark_notifications_read.php", { method: "POST" });
        window._latestNotifications = [];
        renderNotificationPanel();
        refreshNotificationBadge();
    } catch (e) {
        console.error("markAllNotificationsRead error:", e);
    }
}

document.addEventListener("DOMContentLoaded", () => {

    refreshNotificationBadge();

    const btn = document.getElementById("notifBtn");
    if (btn) btn.addEventListener("click", onNotifClick);

    const markReadBtn = document.getElementById("notifMarkRead");
    if (markReadBtn) markReadBtn.addEventListener("click", markAllNotificationsRead);

    document.addEventListener("click", (e) => {
        const panel = document.getElementById("notifPanel");
        const btn = document.getElementById("notifBtn");
        if (!panel || !btn) return;

        if (!panel.contains(e.target) && !btn.contains(e.target)) {
            panel.classList.remove("open");
        }
    });
});

async function deleteAllPatientNotifications() {
    if (!confirm("Delete all notifications?")) return;

    const btns = document.querySelectorAll(".link-btn");
    btns.forEach(b => b.disabled = true);

    const res = await fetch("/booking-management/controllers/patient_controllers/delete_all_notifications.php", {
        method: "POST"
    });

    let data;
    try { data = await res.json(); } catch { data = { success: false }; }

    btns.forEach(b => b.disabled = false);

    if (!data.success) {
        alert(data.msg || "Failed to delete notifications.");
        return;
    }

    const list = document.getElementById("notificationList");
    if (list) list.innerHTML = `<li class="empty">No new notifications.</li>`;
    const badge = document.getElementById("notifCount");
    if (badge) { badge.textContent = ""; badge.style.display = "none"; }
}

let calState = { month: new Date().getMonth(), year: new Date().getFullYear() };
let patientAppointments = [];

async function loadCalendarAppointments() {
    try {
        const res = await fetch("/booking-management/controllers/get_calendar_appointments.php");
        const json = await res.json();
        if (json.success && Array.isArray(json.data)) {
            patientAppointments = json.data;
        } else {
            patientAppointments = [];
        }
    } catch (e) {
        console.error("Calendar fetch failed", e);
        patientAppointments = [];
    }
    renderCalendar();
}

function renderCalendar() {
    const grid = document.getElementById("calendarGrid");
    if (!grid) return;

    const { month, year } = calState;
    const firstDay = new Date(year, month, 1);
    const startDay = firstDay.getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const today = new Date();
    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
    const apptDates = new Set(patientAppointments.map(a => a.date));
    grid.innerHTML = "";

    ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].forEach(d => {
        const h = document.createElement("div");
        h.className = "cal-head";
        h.textContent = d;
        grid.appendChild(h);
    });

    for (let i = 0; i < startDay; i++) {
        const e = document.createElement("div");
        e.className = "cal-empty";
        grid.appendChild(e);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
        const hasAppt = apptDates.has(dateStr);
        const isToday = dateStr === todayStr;
        const cell = document.createElement("div");
        cell.className = `cal-day${hasAppt ? " has-appt" : ""}${isToday ? " cal-today" : ""}`;
        cell.textContent = day;
        if (hasAppt) cell.title = "You have an appointment";
        if (isToday) cell.title = (cell.title ? cell.title + " • " : "") + "Today";
        grid.appendChild(cell);
    }

    const label = document.getElementById("calMonthLabel");
    if (label) label.textContent = firstDay.toLocaleString("default", { month: "long", year: "numeric" });
}


function prevMonth() {
    calState.month--;
    if (calState.month < 0) { calState.month = 11; calState.year--; }
    renderCalendar();
}
function nextMonth() {
    calState.month++;
    if (calState.month > 11) { calState.month = 0; calState.year++; }
    renderCalendar();
}

document.addEventListener("DOMContentLoaded", () => {
    loadCalendarAppointments();
});



