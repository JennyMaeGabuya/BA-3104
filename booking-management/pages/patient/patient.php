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
                <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()" title="Menu">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="logo">BSU</div>
                <div class="logo-text">
                    <h1>BatStateU Clinic</h1>
                    <p>Patient Portal</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn-icon" title="Notifications">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Overview</span>
                </button>
                <button class="nav-item" onclick="switchSection('booking')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Book Appointment</span>
                </button>
                <button class="nav-item" onclick="switchSection('profile')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Profile</span>
                </button>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Overview Section -->
            <section id="overview" class="content-section active">
                <div class="section-header">
                    <h2>Overview</h2>

                </div>

                <div class="appointments-container">
                    <!-- Pending Appointments -->
                    <div class="appointment-card">
                        <div class="card-header">
                            <h3>Pending Appointments</h3>
                            <span class="badge badge-warning" id="pendingCount">0</span>
                        </div>
                        <div class="table-wrapper">
                            <table class="appointment-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reason</th>
                                        <th>First Name</th>
                                        <th>Contact No.</th>
                                        <th>Email</th>
                                        <th>Age</th>
                                        <th>Date of Birth</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="pendingTable">
                                    <tr>
                                        <td colspan="8" class="empty-state">No pending appointments</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Approved Appointments -->
                    <div class="appointment-card">
                        <div class="card-header">
                            <h3>Approved Appointments</h3>
                            <span class="badge badge-success" id="approvedCount">0</span>
                        </div>
                        <div class="table-wrapper">
                            <table class="appointment-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reason</th>
                                        <th>First Name</th>
                                        <th>Contact No.</th>
                                        <th>Email</th>
                                        <th>Age</th>
                                        <th>Date of Birth</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="approvedTable">
                                    <tr>
                                        <td colspan="8" class="empty-state">No approved appointments</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Book Appointment Section -->
            <section id="booking" class="content-section">
                <div class="section-header">
                    <h2>Book Appointment</h2>
                    <p>Schedule a new appointment with the clinic</p>
                </div>

                <div class="booking-container">
                    <div class="booking-form-card">
                        <form id="bookingForm" onsubmit="handleBooking(event)">
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Name *</label>
                                    <input type="text" class="form-input" id="name" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contact No. *</label>
                                    <input type="tel" class="form-input" id="contactNo" placeholder="+63 912 345 6789" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-input" id="email" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Age *</label>
                                    <input type="number" class="form-input" id="age" min="1" max="120" required>
                                </div>
                            </div>

                            <!-- NEW FIELDS -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Gender *</label>
                                    <select class="form-select" id="gender" required>
                                        <option value="">Select gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Address *</label>
                                    <input type="text" class="form-input" id="address" placeholder="Enter your address" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Date of Birth *</label>
                                    <input type="date" class="form-input" id="dateOfBirth" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Appointment Date *</label>
                                    <input type="date" class="form-input" id="appointmentDate" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Reason for Consultation *</label>
                                <select class="form-select" id="reason" required>
                                    <option value="">Select reason</option>
                                    <option value="General Checkup">General Checkup</option>
                                    <option value="Follow-up Consultation">Follow-up Consultation</option>
                                    <option value="Medical Certificate">Medical Certificate</option>
                                    <option value="Flu Symptoms">Flu Symptoms</option>
                                    <option value="Dental Checkup">Dental Checkup</option>
                                    <option value="Injury/Accident">Injury/Accident</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Book Appointment</button>
                        </form>

                    </div>

                    <!-- Booked Appointments Table -->
                    <div class="booked-appointments-card">
                        <div class="card-header">
                            <h3>Your Booked Appointments</h3>
                        </div>
                        <div class="table-wrapper">
                            <table class="appointment-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reason</th>
                                        <th>Name</th>
                                        <th>Contact No.</th>
                                        <th>Email</th>
                                        <th>Gender</th>
                                        <th>Age</th>
                                        <th>Date of Birth</th>
                                        <th>Address</th>
                                    </tr>
                                </thead>

                                <tbody id="bookedTable">
                                    <tr>
                                        <td colspan="7" class="empty-state">No booked appointments yet</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profile Section -->
            <section id="profile" class="content-section">
                <div class="section-header">
                    <h2>Profile</h2>
                    <p>View and manage your personal information</p>
                </div>

                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar-section">
                            <div class="profile-avatar">PN</div>
                            <div class="profile-user-info">
                                <h3 id="profileFullName">Patient Name</h3>
                                <p class="profile-role">Patient</p>
                            </div>
                        </div>
                        <button class="btn btn-primary" onclick="editProfile()">Edit Profile</button>
                    </div>
                    <div class="profile-info-grid">
                        <div class="profile-info-item">
                            <span class="info-title">Full Name</span>
                            <p id="profileName">Patient Name</p>
                        </div>
                        <div class="profile-info-item">
                            <span class="info-title">Email</span>
                            <p id="profileEmail" class="empty-field">Not provided</p>
                        </div>
                        <div class="profile-info-item">
                            <span class="info-title">Gender</span>
                            <p id="profileGender" class="empty-field">Not provided</p>
                        </div>
                        <div class="profile-info-item">
                            <span class="info-title">Contact No.</span>
                            <p id="profileContact" class="empty-field">Not provided</p>
                        </div>
                        <div class="profile-info-item">
                            <span class="info-title">Address</span>
                            <p id="profileAddress" class="empty-field">Not provided</p>
                        </div>
                        <div class="profile-info-item">
                            <span class="info-title">Date of Birth</span>
                            <p id="profileDOB" class="empty-field">Not provided</p>
                        </div>
                    </div>

                </div>
    </div>
    </section>
    </main>
    </div>

    <!-- Reschedule Appointment Modal -->
    <div id="rescheduleModal" class="modal-overlay">
        <div class="modal-box">
            <h3>Reschedule Appointment</h3>

            <input type="date" id="newAppointmentDate" class="modal-input" required>

            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeRescheduleModal()">Cancel</button>
                <button class="btn-save" onclick="saveReschedule()">Save</button>
            </div>
        </div>
    </div>

    <script src="../../js/patient_js/patient-script.js"></script>
</body>

</html>