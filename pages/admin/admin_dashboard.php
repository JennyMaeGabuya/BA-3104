<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BatStateU Clinic</title>
    <link rel="stylesheet" href="/booking-management/css/admin_css/admin_style.css">
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
                    <p>Admin Dashboard</p>
                </div>
            </div>
            <div class="header-actions">
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
                <button class="nav-item" onclick="switchSection('notifications')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span>Notifications</span>
                </button>
                <button class="nav-item" onclick="switchSection('medical-records')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Medical Records</span>
                </button>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Overview Section -->
            <section id="overview" class="content-section active">
                <div class="section-header">
                    <h2>Overview</h2>
                    <p>Manage appointments and patient consultations</p>
                </div>

                <div class="appointment-card">
                    <div class="card-header">
                        <h3>Appointment Table</h3>
                        <span class="badge badge-info" id="appointmentCount">0</span>
                    </div>
                    <div class="table-wrapper">
                        <table class="appointment-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Reason</th>
                                    <th>Full Name</th>
                                    <th>Contact No.</th>
                                    <th>Email</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="appointmentTable">
                                <tr>
                                    <td colspan="9" class="empty-state">No appointments found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Notifications Section -->
            <section id="notifications" class="content-section">
                <div class="section-header">
                    <h2>Notifications</h2>
                    <p>View canceled and rescheduled appointments</p>
                </div>

                <div class="notifications-container">
                    <!-- Canceled Appointments -->
                    <div class="appointment-card">
                        <div class="card-header">
                            <h3>Canceled Appointments</h3>
                            <span class="badge badge-danger" id="canceledCount">0</span>
                        </div>
                        <div class="table-wrapper">
                            <table class="appointment-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="canceledTable">
                                    <tr>
                                        <td colspan="5" class="empty-state">No canceled appointments</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Rescheduled Appointments -->
                    <div class="appointment-card">
                        <div class="card-header">
                            <h3>Rescheduled Appointments</h3>
                            <span class="badge badge-warning" id="rescheduledCount">0</span>
                        </div>
                        <div class="table-wrapper">
                            <table class="appointment-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="rescheduledTable">
                                    <tr>
                                        <td colspan="5" class="empty-state">No rescheduled appointments</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Medical Records Section -->
            <section id="medical-records" class="content-section">
                <div class="section-header">
                    <h2>Medical Records</h2>
                    <p>View patient consultation history and doctor's notes</p>
                </div>

                <div class="medical-records-card">
                    <div class="card-header">
                        <h3>Patient Medical Records</h3>
                        <span class="badge badge-success" id="recordsCount">0</span>
                    </div>
                    <div class="table-wrapper">
                        <table class="appointment-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Full Name</th>
                                    <th>Reason</th>
                                    <th>Doctor's Note</th>
                                </tr>
                            </thead>
                            <tbody id="medicalRecordsTable">
                                <tr>
                                    <td colspan="5" class="empty-state">No medical records found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Status Modal -->
    <div class="modal" id="statusModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Appointment Status</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modalPatientName"></p>
                <div class="modal-actions">
                    <button class="btn btn-success" onclick="updateStatus('accepted')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Accept
                    </button>
                    <button class="btn btn-danger" onclick="updateStatus('declined')">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Decline
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Doctor's Note Modal -->
    <div class="modal" id="noteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add Doctor's Note</h3>
                <button class="modal-close" onclick="closeNoteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="notePatientName"></p>
                <div class="form-group">
                    <label class="form-label">Doctor's Note</label>
                    <textarea class="form-textarea" id="doctorNote" rows="5" placeholder="Enter consultation notes, diagnosis, prescriptions, etc."></textarea>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-primary" onclick="saveDoctorNote()">Save Note</button>
                    <button class="btn btn-outline" onclick="closeNoteModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/booking-management/js/admin_js/admin_dashboard.js"></script>
</body>

</html>