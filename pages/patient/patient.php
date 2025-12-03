<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - BatStateU Clinic</title>
    <link rel="stylesheet" href="../../css/patient_css/patient_style.css">
</head>

<body>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Header -->
    <header class="header">
        <div class="header-content">

            <div class="logo-section">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <div class="logo">
                    <div class="image">
                        <img src="/booking-management/image/bat.png" alt="BSU Logo">
                    </div>
                </div>
                <div class="logo-text">
                    <h1>BatStateU Clinic</h1>
                </div>
            </div>

            <div class="header-actions">

                <button class="btn-icon" title="Notifications">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </button>

                <div class="user-info">
                    <div class="avatar">PN</div>
                    <div class="user-details">
                        <p id="headerUserName">Patient Name</p>
                        <span>Patient</span>
                    </div>
                </div>

                <button class="btn-icon" onclick="logout()" title="Logout">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>

            </div>

        </div>
    </header>

    <!-- Main Layout -->
    <div class="main-layout">

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <nav class="sidebar-nav">

                <button class="nav-item active" onclick="switchSection('overview')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Overview</span>
                </button>

                <button class="nav-item" onclick="switchSection('booking')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Book Appointment</span>
                </button>

                <button class="nav-item" onclick="switchSection('profile')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Profile</span>
                </button>

            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <!-- ==========================
                OVERVIEW
            =========================== -->
            <section id="overview" class="content-section active">

                <div class="section-header">
                    <h2>Overview</h2>
                </div>

                <!-- NEW Two-column layout -->
                <div class="appointments-flex">

                    <!-- Pending -->
                    <div class="appointment-card half">
                        <div class="card-header">
                            <h3>Pending Appointments</h3>
                            <span class="badge badge-warning" id="pendingCount">0</span>
                        </div>

                        <div id="pendingList" class="appointment-list"></div>
                    </div>

                    <!-- Cancelled -->
                    <div class="appointment-card half">
                        <div class="card-header">
                            <h3>Cancelled Appointments</h3>
                            <span class="badge badge-danger" id="cancelledCount">0</span>
                        </div>

                        <div id="cancelledList" class="appointment-list"></div>
                    </div>


                </div>
            </section>

            <!-- ==========================
                BOOK APPOINTMENT
            =========================== -->
            <section id="booking" class="content-section">

                <div class="section-header">
                    <h2>Book Appointment</h2>
                </div>

                <div class="booking-container">

                    <div class="booking-form-card">
                        <form id="bookingForm" onsubmit="handleBooking(event)">

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Name *</label>
                                    <input type="text" id="name" class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label>Contact No. *</label>
                                    <input type="tel" id="contactNo" class="form-input" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" id="email" class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label>Age *</label>
                                    <input type="number" id="age" class="form-input" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Gender *</label>
                                    <select id="gender" class="form-select" required>
                                        <option value="">Select gender</option>
                                        <option>Male</option>
                                        <option>Female</option>
                                        <option>Other</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Address *</label>
                                    <input type="text" id="address" class="form-input" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Date of Birth *</label>
                                    <input type="date" id="dateOfBirth" class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label>Appointment Date *</label>
                                    <input type="date" id="appointmentDate" class="form-input" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Session *</label>
                                    <select id="appointmentSession" class="form-select" required>
                                        <option value="">Select session</option>
                                        <option value="am">AM (8:00–11:45)</option>
                                        <option value="pm">PM (1:00–4:45)</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Appointment Time *</label>
                                    <select id="appointmentTime" class="form-select" required>
                                        <option value="">Select time</option>
                                    </select>
                                    <small id="slotInfo"></small>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Reason *</label>
                                <select id="reason" class="form-select" required>
                                    <option value="">Select reason</option>
                                    <option>General Checkup</option>
                                    <option>Follow-up</option>
                                    <option>Medical Certificate</option>
                                    <option>Flu Symptoms</option>
                                    <option>Dental</option>
                                    <option>Injury</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Book Appointment</button>

                        </form>
                    </div>

                </div>
            </section>

            <!-- ==========================
                PROFILE
            =========================== -->
            <section id="profile" class="content-section">

                <div class="section-header">
                    <h2>Profile</h2>
                </div>

                <div class="profile-card">

                    <div class="profile-header">
                        <div class="profile-avatar-section">
                            <div class="profile-avatar">PN</div>
                            <div>
                                <h3 id="profileFullName">Patient Name</h3>
                                <p class="profile-role">Patient</p>
                            </div>
                        </div>
                        <button class="btn btn-primary" onclick="editProfile()">Edit Profile</button>
                    </div>

                    <div class="profile-info-grid">

                        <div class="profile-info-item">
                            <span>Full Name</span>
                            <p id="profileName"></p>
                        </div>

                        <div class="profile-info-item">
                            <span>Email</span>
                            <p id="profileEmail"></p>
                        </div>

                        <div class="profile-info-item">
                            <span>Gender</span>
                            <p id="profileGender"></p>
                        </div>

                        <div class="profile-info-item">
                            <span>Contact No.</span>
                            <p id="profileContact"></p>
                        </div>

                        <div class="profile-info-item">
                            <span>Address</span>
                            <p id="profileAddress"></p>
                        </div>

                        <div class="profile-info-item">
                            <span>Date of Birth</span>
                            <p id="profileDOB"></p>
                        </div>

                    </div>

                </div>
            </section>

        </main>
    </div>

    <!-- ==========================
        RESCHEDULE MODAL
    =========================== -->
    <div id="rescheduleModal" class="modal-overlay">
        <div class="modal-box">

            <h3>Reschedule Appointment</h3>

            <label>New Date</label>
            <input type="date" id="newAppointmentDate" class="modal-input" required>

            <label>New Time</label>
            <select id="newAppointmentTime" class="modal-input" required>
                <option value="">Select time</option>
            </select>

            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeRescheduleModal()">Cancel</button>
                <button class="btn-save" onclick="saveReschedule()">Save</button>
            </div>

        </div>
    </div>

    <script src="../../js/patient_js/patient-script.js"></script>

</body>

</html>