<?php
/**
 * Report Lost Item Page - Converted from user_report.html
 * Requires authentication - redirects to login if not logged in
 */

require_once 'auth_check.php';
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt@BatStateU — Report Lost Item</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="user_home.css">
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
        <a href="user_report.php" class="active">Report An Item</a>
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

      <!-- Profile Avatar - Dynamic with PHP Session -->
      <?php include 'avatar_component.php'; ?>

    </div>

  </div>
</header>

  <main class="report-page" id="reportPage">
    <header class="page-header">
      <h1>Report Found Item</h1>
      <p class="subtitle">Thank you for helping! Fill in the details below to report an item you found on campus.</p>
    </header>

    <form id="foundForm" class="form-card" novalidate data-report-type="found">
      <!-- ITEM INFORMATION -->
      <section class="form-section">
        <h2 class="section-title">Item Information</h2>

        <label class="field">
          <span class="label-text">Item Name <span class="required">*</span></span>
          <input type="text" name="itemName" id="itemName" placeholder="e.g., Student ID Card, iPhone 13, Black Backpack" required>
          <div class="hint">Provide a short name to identify the item.</div>
        </label>

        <label class="field">
          <span class="label-text">Category <span class="required">*</span></span>
          <select name="category" id="category" required>
            <option value="" disabled selected>Select item category</option>
            <option>Electronics</option>
            <option>Identification</option>
            <option>Bag / Wallet</option>
            <option>Accessories</option>
            <option>Others</option>
          </select>
        </label>

        <label class="field">
          <span class="label-text">Description <span class="required">*</span></span>
          <textarea name="description" id="description" rows="5" placeholder="Provide detailed description including color, brand, size, unique features, etc." required></textarea>
          <div class="hint">Detailed description helps the owner identify their item.</div>
        </label>

        <label class="field upload-field" id="uploadField">
          <span class="label-text">Upload Photo (Optional)</span>

          <div class="dropzone" id="dropzone" tabindex="0" role="button" aria-label="Upload photo">
            <svg class="upload-illustration" width="48" height="48" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 3v10" stroke="#9CA3AF" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="#9CA3AF" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M7 10l5-5 5 5" stroke="#9CA3AF" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>

            <div class="drop-text">
              <strong>Click to upload or drag and drop</strong>
              <div class="muted">PNG, JPG up to 10MB</div>
            </div>

            <input type="file" id="fileInput" accept="image/png, image/jpeg" aria-hidden="true">
          </div>

          <div class="preview-row" id="previewRow" aria-live="polite"></div>
        </label>
      </section>

      <!-- WHERE & WHEN -->
      <section class="form-section two-col">
        <div class="col">
          <label class="field">
            <span class="label-text">Location Found <span class="required">*</span></span>
            <select name="locationFound" id="locationFound" required>
              <option value="" disabled selected>Where did you find the item?</option>
              <option>Library</option>
              <option>Cafeteria</option>
              <option>Gym</option>
              <option>Lecture Hall</option>
              <option>Parking Area</option>
              <option>Others</option>
            </select>
          </label>
        </div>

        <div class="col-inline">
          <label class="field">
            <span class="label-text">Date Found <span class="required">*</span></span>
            <input type="date" name="dateFound" id="dateFound" required>
          </label>

          <label class="field">
            <span class="label-text">Time Found (Optional)</span>
            <input type="time" name="timeFound" id="timeFound">
          </label>
        </div>
      </section>

      <!-- PICKUP -->
      <section class="form-section">
        <h3 class="section-subtitle">Pickup Information</h3>

        <label class="field">
          <span class="label-text">Item Location for Pickup <span class="required">*</span></span>
          <select name="pickupLocation" id="pickupLocation" required>
            <option value="" disabled selected>Where can the owner claim this item?</option>
            <option>I have the item with me</option>
            <option>Lost & Found Office</option>
            <option>Security Office</option>
            <option>Bring to Admin</option>
          </select>
        </label>

        <div class="info-box info-warning" role="note">
          <strong>Important:</strong> If you selected “I have the item with me”, please bring it to the Lost & Found Office as soon as possible or coordinate with the owner through the admin team.
        </div>
      </section>

      <!-- CONTACT -->
      <section class="form-section">
        <h3 class="section-subtitle">Your Contact Information</h3>

        <label class="field">
          <span class="label-text">Email Address <span class="required">*</span></span>
          <input type="email" name="email" id="email" placeholder="your.email@batstate-u.edu.ph" required>
        </label>

        <label class="field">
          <span class="label-text">Phone Number <span class="required">*</span></span>
          <input type="tel" name="phone" id="phone" placeholder="+63 912 345 6789" required pattern="^\+?\d[\d\s-]{7,}$">
        </label>

        <div class="info-box info-blue" role="note">
          <strong>Privacy Note:</strong> Your contact information will only be shared with the verified owner of the item or the admin team for coordination purposes.
        </div>
      </section>

      <!-- BUTTONS -->
      <section class="form-actions">
        <button type="button" class="btn btn-ghost" id="btnCancel">Cancel</button>
        <button type="submit" class="submit-btn" id="btnSubmit">
          <svg class="icon-send" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 2L11 13" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M22 2l-7 20 1-7 7-7z" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Submit Report
        </button>
      </section>
    </form>

    <!-- SUCCESS BOX -->
    <aside class="success-box" id="successBox" hidden>
      <div class="success-inner">
        <div class="success-icon">✔︎</div>
        <div class="success-text">
          <h4>Thank you for your kindness!</h4>
          <p>By reporting found items, you're helping your fellow students, faculty, and staff recover their belongings. Your report will be verified and matched with lost item reports. The rightful owner will be notified once verified.</p>
        </div>
      </div>
    </aside>
  </main>

    <!-- Include JavaScript files -->
  <script src="avatar_dropdown.js"></script>
  <script src="user_script.js"></script>
  <script src="report_form.js"></script>

</body>
</html>



