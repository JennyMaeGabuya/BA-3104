<?php
/**
 * About Page - Converted from user_about.html
 * Requires authentication - redirects to login if not logged in
 */

require_once 'auth_check.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt@BatStateU — About</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/BA-3104/user_home.css">
</head>
<body>

  <!-- Header -->
<header class="site-header">
  <div class="header-inner">

    <!-- LEFT: Logo + Text -->
    <div class="brand">
      <div class="logo" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
          <path d="M12 3v18"></path>
        </svg>
      </div>

      <div class="brand-text">
        <div class="site-title">FindIt@BatStateU</div>
        <div class="site-sub">Malvar Campus</div>
      </div>
    </div>

    <!-- RIGHT: NAVIGATION + BELL + PROFILE -->
    <div class="header-right">

      <nav class="nav" role="navigation" aria-label="Main">
        <a href="user_home.php">Home</a>
        <a href="user_report.php">Report An Item</a>
        <a href="user_found.php">Found Items</a>
        <a href="user_about.php" class="active">About</a>
        <a class="btn-dashboard" href="Dashboard/dashboard.php">Dashboard</a>
      </nav>

      <!-- Notification Icon with Dropdown -->
      <div class="notification-container">
        <div class="notif" id="notifBtn">
          <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" fill="none"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22a2 2 0 0 0 2-2H10a2 2 0 0 0 2 2z"></path>
            <path d="M18 16v-5a6 6 0 1 0-12 0v5l-2 2v1h16v-1z"></path>
          </svg>
          <span class="notif-dot" id="notifDot"></span>
        </div>

        <!-- Notification Dropdown -->
        <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
          <div class="notification-dropdown-header">
            <h3>Notifications</h3>
          </div>
          <div class="notification-dropdown-list">
            <div class="notification-dropdown-item">
              <div class="notification-dropdown-content">
                <div class="notification-dropdown-title">Your report #LR-003 is pending admin approval (includes photo)</div>
                <div class="notification-dropdown-time">30 minutes ago</div>
              </div>
            </div>
            
            <div class="notification-dropdown-item">
              <div class="notification-dropdown-content">
                <div class="notification-dropdown-title">Your lost item report #LR-001 has been verified</div>
                <div class="notification-dropdown-time">2 hours ago</div>
              </div>
            </div>
            
            <div class="notification-dropdown-item">
              <div class="notification-dropdown-content">
                <div class="notification-dropdown-title">A matching item was found for your report #LR-001</div>
                <div class="notification-dropdown-time">5 hours ago</div>
              </div>
            </div>
            
            <div class="notification-dropdown-item">
              <div class="notification-dropdown-content">
                <div class="notification-dropdown-title">Please claim your item within 7 days</div>
                <div class="notification-dropdown-time">1 day ago</div>
              </div>
            </div>
          </div>
          <div class="notification-dropdown-footer">
            <a href="Dashboard/notification.php" class="view-all-link">View all notifications</a>
          </div>
        </div>
      </div>

      <!-- Profile Avatar - Dynamic with PHP Session -->
      <?php include 'avatar_component.php'; ?>

    </div>

  </div>
</header>


  <section class="hero hero-about">
  <div class="hero-footer">
    <h1>About FindIt@BatStateU</h1>
    <p class="hero-sub">
      A comprehensive lost and found management system dedicated to serving
      the Batangas State University - Malvar Campus community.
    </p>
  </div>
</section>


  <!-- Mission & Vision -->
  <section class="section about-mission">
    <div class="container mission-grid">
      <div class="mission-card">
        <div class="icon">🎯</div>
        <h3>Our Mission</h3>
        <p>To provide a centralized, efficient, and user-friendly platform that helps students, faculty, and staff of BatStateU - Malvar Campus quickly report, track, and recover lost items, fostering a supportive and trustworthy community environment.</p>
      </div>

      <div class="mission-card">
        <div class="icon">❤️</div>
        <h3>Our Vision</h3>
        <p>To be the most trusted and reliable lost and found system in the university, where every item reported has the highest chance of being reunited with its rightful owner, strengthening the bonds within our campus community.</p>
      </div>
    </div>
  </section>

  <!-- What we offer -->
  <section class="section offers">
    <div class="container">
      <h2 class="section-title center">What We Offer</h2>
      <p class="section-sub center">Comprehensive features designed to help you recover your lost items</p>

      <div class="cards-row">
        <article class="offer-card">
          <div class="ico">📦</div>
          <h4>Easy Reporting</h4>
          <p>Submit lost or found item reports with photos and detailed descriptions in just a few clicks.</p>
        </article>

        <article class="offer-card">
          <div class="ico">🔍</div>
          <h4>Smart Search</h4>
          <p>Browse and filter through our database of found items to quickly locate your belongings.</p>
        </article>

        <article class="offer-card">
          <div class="ico">🔔</div>
          <h4>Real-Time Tracking</h4>
          <p>Monitor the status of your reports and receive notifications when there are updates.</p>
        </article>

        <article class="offer-card">
          <div class="ico">🛡️</div>
          <h4>Admin Verification</h4>
          <p>All photo submissions are reviewed by administrators to ensure quality and authenticity.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- How it works -->
  <section class="section how-it-works">
    <div class="container center">
      <h3 class="section-title">How It Works</h3>
      <p class="section-sub">A simple three-step process to help you find what's lost</p>

      <div class="steps-row">
        <div class="step">
          <div class="how-num">1</div>
          <h4>Report Your Item</h4>
          <p>Create a detailed report about your lost item or submit a found item you discovered on campus.</p>
        </div>

        <div class="step">
          <div class="how-num">2</div>
          <h4>We Verify & Match</h4>
          <p>Our admin team reviews submissions and attempts to match lost and found items based on description.</p>
        </div>

        <div class="step">
          <div class="how-num">3</div>
          <h4>Recover Your Item</h4>
          <p>Once a match is found, you will be notified and can claim your item at the designated office.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- About / Campus -->
  <section id="about" class="section about-split">
    <div class="container about-grid">
      <div class="photo">
        <!-- placeholder image - replace with campus.jpg -->
        <img src="https://images.unsplash.com/photo-1508873699372-7ae0a4d7b3b9?q=80&w=1400&auto=format&fit=crop" alt="Campus building">
      </div>

      <div class="about-text">
        <h3 class="section-title left">Serving the BatStateU Community in 2025</h3>
        <p class="muted">FindIt@BatStateU was created specifically to address the needs of our campus community. We understand how stressful it can be to lose important belongings, especially during busy academic periods.</p>

        <p class="muted">Our platform brings together students, faculty, and staff in a collaborative effort to help each other. Every item reported, every search conducted, and every successful recovery contributes to a stronger, more connected campus community.</p>

        <div class="info-box">
          <strong>Did you know?</strong> Our system has helped recover over <strong>53</strong> lost items with an <strong>89%</strong> success rate.
        </div>

        <a class="btn btn-primary" href="user_report.php">Get Started Today</a>
      </div>
    </div>
  </section>

  <!-- Contact cards -->
  <section class="section contact-cards">
    <div class="container">
      <h3 class="section-title center">Get In Touch</h3>
      <p class="section-sub center">Have questions? We're here to help!</p>

      <div class="cards-row contact-row">
        <div class="contact-card">
          <div class="circle-ico">📍</div>
          <h4>Location</h4>
          <p class="muted">BatStateU Malvar Campus, Batangas, Philippines</p>
        </div>

        <div class="contact-card">
          <div class="circle-ico">📞</div>
          <h4>Phone</h4>
          <p class="muted">(043) 778-2170 <br>Mon-Fri, 8AM-5PM</p>
        </div>

        <div class="contact-card">
          <div class="circle-ico">✉️</div>
          <h4>Email</h4>
          <p class="muted">lostandfound@batstate-u.edu.ph</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="site-footer" role="contentinfo">
    <div class="footer-inner">
      <div>
        <div class="footer-logo">
          <div class="logo" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
              <path d="M12 3v18"></path>
            </svg>
          </div>
          <div>
            <div class="footer-title">FindIt@BatStateU</div>
            <div class="footer-sub">Malvar Campus</div>
          </div>
        </div>
        <p class="footer-desc">Helping the BatStateU community recover lost items since 2024.</p>
      </div>

        <div class="footer-col">
          <h4>Quick Links</h4>
          <a href="user_home.php">Home</a>
          <a href="user_report.php">Report An Item</a>
          <a href="user_found.php">Found Items</a>
          <a href="user_about.php">About Us</a>
        </div>

        <div class="footer-col">
          <h4>Contact Us</h4>
          <p class="contact-line">📍 BatStateU Malvar Campus, Batangas</p>
          <p class="contact-line">📞 (043) 778-2170</p>
          <p class="contact-line">✉️ lostandfound@batstate-u.edu.ph</p>
        </div>
      </div>

      <div class="footer-bottom">
        <hr>
        <p>© 2025 Batangas State University - Malvar Campus. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- Include JavaScript files -->
  <script src="avatar_dropdown.js"></script>
  <script src="user_script.js"></script>
</body>
</html>

