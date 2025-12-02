<?php
/**
 * Home Page - Requires authentication
 */

require_once 'auth_check.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt@BatStateU — Home</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
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
        <a href="user_home.php" class="active">Home</a>
        <a href="user_report.php">Report An Item</a>
        <a href="user_found.php">Found Items</a>
        <a href="user_about.php">About</a>
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

      <!-- Profile Avatar -->
      <?php include 'avatar_component.php'; ?>

    </div>

  </div>
</header>


  <!-- Hero -->
  <section class="hero" aria-label="Hero">
    <div class="hero-inner">
      <div class="hero-text">
        <h2>FindIt@BatStateU</h2>
        <p class="lead">Helping you find what's lost, faster.</p>
        <p class="desc">A centralized platform for Batangas State University - Malvar Campus students, faculty, and staff to report, track, and recover lost or found items efficiently.</p>

        <div class="controls">
          <a class="btn submit-btn" href="user_report.php">Report An Item</a>
          <a class="btn btn-ghost" href="user_found.php">Search Found Items</a>
        </div>
      </div>

      <div class="features-grid" aria-hidden="false">
        <div class="feature">
          <div class="icon" aria-hidden="true">
            <!-- box -->
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
          </div>
          <div>
            <h4>Lost Items</h4>
            <p>Report missing belongings quickly and easily.</p>
          </div>
        </div>

        <div class="feature">
          <div class="icon" aria-hidden="true">
            <!-- magnifier -->
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
          </div>
          <div>
            <h4>Find Items</h4>
            <p>Search through found items database.</p>
          </div>
        </div>

        <div class="feature">
          <div class="icon" aria-hidden="true">
            <!-- bell -->
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 1 0-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
          </div>
          <div>
            <h4>Get Notified</h4>
            <p>Receive alerts when items are found.</p>
          </div>
        </div>

        <div class="feature">
          <div class="icon" aria-hidden="true">
            <!-- shield -->
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l7 3v5c0 5-3.9 9.8-7 11-3.1-1.2-7-6-7-11V5l7-3z"></path></svg>
          </div>
          <div>
            <h4>Secure</h4>
            <p>Verified reports and safe recovery.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- How it works + stats -->
  <section class="section" aria-label="How it works">
    <div class="container center">
      <h3 class="section-title">How It Works</h3>
      <p class="muted sub">Simple steps to report and recover your items</p>

      <div class="how-cards" role="list">
        <div class="how-card" role="listitem">
          <div class="how-num">1</div>
          <h3>Report</h3>
          <p>Submit a lost or found item report with details and photos</p>
        </div>

        <div class="how-card" role="listitem">
          <div class="how-num">2</div>
          <h3>Track</h3>
          <p>Monitor the status of your report in real-time</p>
        </div>

        <div class="how-card" role="listitem">
          <div class="how-num">3</div>
          <h3>Recover</h3>
          <p>Get notified and claim your item at the designated office</p>
        </div>
      </div>

      <div class="stats" aria-hidden="false">
        <div>
          <div class="stat-num">53</div>
          <div class="stat-label">Items Recovered</div>
        </div>
        <div>
          <div class="stat-num">79%</div>
          <div class="stat-label">Success Rate</div>
        </div>
        <div>
          <div class="stat-num">6</div>
          <div class="stat-label">Active Users</div>
        </div>
        <div>
          <div class="stat-num">24/7</div>
          <div class="stat-label">Online Support</div>
        </div>
      </div>
    </div>
  </section>

  <!-- About -->
  <section class="section" aria-label="About">
    <div class="container">
      <div class="about">
        <div>
          <h3 class="section-title left">Serving the BatStateU Community</h3>
          <p class="muted" style="margin-top:14px; max-width:60ch;">Our Lost and Found Management System is specifically designed for the Batangas State University - Malvar Campus community. We understand the importance of your belongings and strive to make the recovery process as smooth as possible.</p>

          <div class="features-list">
            <div class="bullet">
              <div class="ico" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2l7 3v5c0 5-3.9 9.8-7 11-3.1-1.2-7-6-7-11V5l7-3z"></path></svg>
              </div>
              <div>
                <strong>Multiple Locations</strong>
                <div class="muted small">Track items across different campus buildings</div>
              </div>
            </div>

            <div class="bullet">
              <div class="ico" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8"></path></svg>
              </div>
              <div>
                <strong>Verified Reports</strong>
                <div class="muted small">Admin verification ensures authenticity</div>
              </div>
            </div>

            <div class="bullet">
              <div class="ico" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 1 0-12 0v3.159"></path></svg>
              </div>
              <div>
                <strong>Instant Notifications</strong>
                <div class="muted small">Get alerted when matching items are found</div>
              </div>
            </div>
          </div>
        </div>

        <div class="photo" aria-hidden="true">
          <!-- replace 'campus.jpg' with your campus image -->
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3873.730839332063!2d121.09429617592337!3d13.995243191682063!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33bd7b76e2f8184f%3A0x4adac1c9bbfaeb2e!2sBatangas%20State%20University%20-%20Malvar%20Campus!5e0!3m2!1sen!2sph!4v1709197000000!5m2!1sen!2sph"
            width="100%"
            height="350"
            style="border:0; border-radius: 10px;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
            </iframe>
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
          <a href="user_home.html">Home</a>
          <a href="user_report.html">Report An Item</a>
          <a href="user_found.html">Found Items</a>
          <a href="user_about.html">About Us</a>
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

  <script>
    // simple image fallback
    document.querySelectorAll('img').forEach(img=>{
      img.addEventListener('error', ()=> {
        img.style.background = '#ddd';
        img.style.minHeight = '200px';
      });
    });
  </script>

  <!-- Profile Avatar Dropdown Script -->
  <script src="avatar_dropdown.js"></script>
  <script src="user_script.js"></script>

</body>
</html>
