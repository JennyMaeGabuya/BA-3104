<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BatStateU Clinic Scheduling System</title>

    <style>
        :root {
            --red: #C8102E;
            --blue: #2A8BC4;
            --white: #FFFFFF;
            --gray: #F7F7F7;
            --dark: #1A1A1A;
            --text-light: #6D6D6D;
            --font-base: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            --gradient: linear-gradient(135deg, #C8102E 0%, #2A8BC4 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-base);
            background: var(--gray);
            color: var(--dark);
            line-height: 1.6;
        }

        /* ================= HERO SECTION ================= */
        header {
            background: var(--gradient);
            color: var(--white);
            padding: 80px 20px;
            text-align: center;


        }

        .logo {
            font-size: 60px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        header h1 {
            font-size: 34px;
            margin-bottom: 10px;
        }

        header p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 30px;
        }

        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .btn {
            padding: 12px 20px;
            font-size: 16px;
            text-decoration: none;
            border-radius: 6px;
            transition: 0.3s ease;
            font-weight: bold;
        }

        .btn-primary {
            background: var(--white);
            color: var(--red);
        }

        .btn-primary:hover {
            background: #f1f1f1;
        }

        .btn-outline {
            border: 2px solid var(--white);
            color: var(--white);
        }

        .btn-outline:hover {
            background: var(--white);
            color: var(--red);
        }

        /* ================= ABOUT SECTION ================= */
        .about-section {
            padding: 60px 20px;
            text-align: center;
            background: var(--white);

        }

        .about-section h2 {
            font-size: 28px;
            margin-bottom: 15px;
            color: var(--red);
        }

        .about-section p {
            max-width: 800px;
            margin: 0 auto;
            color: var(--text-light);
            font-size: 17px;
        }

        /* ================= FEATURES SECTION ================= */
        .features {
            background: var(--gray);
            padding: 60px 20px;
            text-align: center;
        }

        .features h2 {
            font-size: 28px;
            margin-bottom: 30px;
            color: var(--blue);
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1100px;
            margin: 0 auto;
        }

        .feature-box {
            background: var(--white);
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.07);
            transition: transform 0.3s ease;
        }

        .feature-box:hover {
            transform: translateY(-5px);
        }

        .feature-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .feature-desc {
            color: var(--text-light);
            font-size: 15px;
        }

        /* ================= FOOTER ================= */
        footer {
            text-align: center;
            padding: 20px;
            background: #222;
            color: var(--white);
            margin-top: 40px;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <!-- ================= HERO SECTION ================= -->
    <header>
        <div class="logo">BSU</div>

        <h1>BatStateU Clinic Scheduling System</h1>
        <p>JPLPC - Malvar Campus</p>

        <div class="btn-group">
            <a href="/booking-management/pages/auth/login.php" class="btn btn-primary">Login</a>
            <a href="/booking-management/pages/auth/signup.php" class="btn btn-outline">Sign Up</a>
        </div>
    </header>

    <!-- ================= ABOUT ================= -->
    <section class="about-section">
        <h2>About the System</h2>
        <p>
            The BatStateU Clinic Scheduling System is designed to simplify and digitize
            appointment management within the campus clinic. Students and staff can book
            consultations, check clinic availability, and receive updates — all in one platform.
        </p>
    </section>

    <!-- ================= FEATURES ================= -->
    <section class="features">
        <h2>What You Can Do</h2>

        <div class="feature-grid">
            <div class="feature-box">
                <div class="feature-title">Book Appointments</div>
                <div class="feature-desc">Schedule clinic visits based on available dates and times.</div>
            </div>

            <div class="feature-box">
                <div class="feature-title">View Doctor Availability</div>
                <div class="feature-desc">Check clinic staff schedules to plan your visit effectively.</div>
            </div>

            <div class="feature-box">
                <div class="feature-title">Manage Your Records</div>
                <div class="feature-desc">Access your clinic appointment history in the system.</div>
            </div>

            <div class="feature-box">
                <div class="feature-title">Receive Notifications</div>
                <div class="feature-desc">Stay updated on appointment confirmations and reminders.</div>
            </div>
        </div>
    </section>

    <!-- ================= FOOTER ================= -->
    <footer>
        © 2025 Batangas State University - Clinic Management System
    </footer>

</body>

</html>