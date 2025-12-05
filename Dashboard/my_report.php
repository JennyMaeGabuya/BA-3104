<?php
/**
 * My Reports Page
 * Requires authentication - redirects to login if not logged in
 */

require_once '../auth_check.php';
require_once '../db_config.php';
require_once __DIR__ . '/notification_context.php';

function resolveReportPhoto(?string $path): string {
  $placeholder = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='80' height='80'><rect width='100%' height='100%' fill='%23FFF9F2'/><circle cx='40' cy='40' r='28' fill='%23FFDCE0'/></svg>";
  if (!$path) {
    return $placeholder;
  }
  $trim = trim($path);
  if ($trim === '') {
    return $placeholder;
  }
  if (str_starts_with($trim, 'Image/')) {
    $trim = 'Dashboard/' . $trim;
  }
  if (preg_match('/^https?:\/\//', $trim) || str_starts_with($trim, '/')) {
    return $trim;
  }
  return '/BA-3104/' . ltrim($trim, '/');
}

$reports = [];
$notificationContext = build_notification_context($pdo, $_SESSION['user_id'] ?? null, 10);
$notificationDropdown = $notificationContext['dropdownNotifications'];
$notificationTotal = $notificationContext['totalCount'];
$notificationUnread = $notificationContext['unreadCount'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt@BatStateU — My Reports</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/BA-3104/Dashboard/dashboard.css?v=1">
</head>
<body>
  <div class="app">
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-top">
       <a href="../user_home.php" class="brand">
        <div class="logo" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
            <path d="M12 3v18"></path>
          </svg>
        </div>
          <div class="brand-text">
            <div class="brand-title">FindIt</div>
            <div class="brand-sub">@BatStateU</div>
          </div>
        </a>

       <nav class="nav" aria-label="Primary">
    
    <a href="dashboard.php" class="nav-item">
        <span class="nav-icon" aria-hidden="true">🏠</span>
        <span class="nav-label">Dashboard</span>
    </a>

    <a href="my_report.php" class="nav-item nav-item--active">
        <span class="nav-icon" aria-hidden="true">📄</span>
        <span class="nav-label">My Reports</span>
    </a>

    <a href="../user_found.php" class="nav-item">
        <span class="nav-icon" aria-hidden="true">🔍</span>
        <span class="nav-label">Found Items</span>
    </a>

    <a href="notification.php" class="nav-item">
      <span class="nav-icon" aria-hidden="true">🔔</span>
      <span class="nav-label">Notifications</span>
      <span class="nav-badge" id="sidebarBadge" <?= $notificationTotal === 0 ? 'style="display:none;"' : '' ?>><?= $notificationTotal ?></span>
    </a>

    <a href="settings.php" class="nav-item">
        <span class="nav-icon" aria-hidden="true">⚙️</span>
        <span class="nav-label">Settings</span>
    </a>

</nav>

      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main">
      <!-- HEADER -->
      <header class="topbar">
        <div class="topbar-left">
          <button id="btnMenu" class="menu-btn" aria-label="Toggle menu">☰</button>
          <div class="topbar-title">Lost and Found Management System</div>
        </div>

        <div class="topbar-right">
          <div class="notification-container">
            <button class="icon-btn" id="notifBtn" aria-label="Notifications">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#374151" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 1 0-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h11z"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              <span class="topbar-badge" id="topbarBadge" <?= $notificationUnread === 0 ? 'style="display:none;"' : '' ?>><?= $notificationUnread > 0 ? $notificationUnread : '' ?></span>
            </button>

            <!-- Notification Dropdown -->
            <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
              <div class="notification-dropdown-header">
                <h3>Notifications</h3>
              </div>
              <div class="notification-dropdown-list">
                <?php if (empty($notificationDropdown)): ?>
                  <div class="notification-dropdown-item">
                    <div class="notification-dropdown-content">
                      <div class="notification-dropdown-title">No recent notifications.</div>
                    </div>
                  </div>
                <?php else: ?>
                  <?php foreach ($notificationDropdown as $notification): ?>
                    <div class="notification-dropdown-item">
                      <div class="notification-dropdown-content">
                        <div class="notification-dropdown-title"><?= htmlspecialchars($notification['message']) ?></div>
                        <div class="notification-dropdown-time"><?= htmlspecialchars(format_relative_time($notification['created_at'] ?? null)) ?></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <div class="notification-dropdown-footer">
                <a href="notification.php" class="view-all-link">View all notifications</a>
              </div>
            </div>
          </div>

      <!-- Profile Avatar -->
      <?php include '../avatar_component.php'; ?>

        </div>
      </header>

      <!-- PAGE BODY -->
      <div class="page-body">

        <!-- Quick Actions -->
        <section class="card quick-actions">
          <div class="quick-actions-inner">
            <h3>Quick Actions</h3>
            <div class="cta-row">
              <a href="../user_report.php" class="btn btn-cta">＋ Report Lost Item</a>
              <a href="../report_found.php" class="btn btn-outline">＋ Report Found Item</a>
            </div>
          </div>
        </section>

        <!-- My Reports Table Card -->
        <section class="card reports-card">
          <h3 class="card-title">My Reports</h3>

          <div class="table-scroll">
            <div class="table-wrap">
            <table class="reports-table" aria-label="My reports">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Report ID</th>
                  <th>Type</th>
                  <th>Item</th>
                  <th>Category</th>
                  <th>Location</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>

              <tbody>
                <?php
                // Fetch user's lost and found item reports from database
                try {
                      $stmt = $pdo->prepare("
                     SELECT 'Lost' AS type, report_id, item_name, category, description, location AS location, date_lost AS date_event,
                       time_lost AS time_event, status, photo_path, contact_email, contact_phone, NULL AS pickup_location, created_at
                     FROM lost_reports 
                     WHERE user_id = :user_id
                     UNION ALL
                     SELECT 'Found' AS type, report_id, item_name, category, description, location_found AS location, date_found AS date_event,
                       time_found AS time_event, status, photo_path, contact_email, contact_phone, pickup_location, created_at
                     FROM found_reports
                     WHERE user_id = :user_id
                     ORDER BY created_at DESC
                      ");
                  $stmt->execute([':user_id' => $_SESSION['user_id']]);
                  $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
                  foreach ($reports as &$rep) {
                    $rep['photo_url'] = resolveReportPhoto($rep['photo_path'] ?? '');
                  }
                  unset($rep);
                  if (count($reports) > 0):
                    foreach ($reports as $report):
                      $img_src = htmlspecialchars($report['photo_url'] ?? '');

                      // Determine status class
                      $status_class = 'status-pending';
                      $status_text = htmlspecialchars($report['status']);
                      if (strtolower($report['status']) === 'verified') {
                        $status_class = 'status-verified';
                      } elseif (strtolower($report['status']) === 'claimed') {
                        $status_class = 'status-claimed';
                      } elseif (strtolower($report['status']) === 'rejected') {
                        $status_class = 'status-rejected';
                      }
                ?>
                <tr>
                  <td class="td-thumb"><img src="<?= $img_src ?>" alt="<?= htmlspecialchars($report['item_name']) ?>"></td>
                  <td class="td-id"><a class="id-link" href="#"><?= htmlspecialchars($report['report_id']) ?></a></td>
                  <td>
                    <?php if ($report['type'] === 'Found'): ?>
                      <span class="pill pill-found">Found</span>
                    <?php else: ?>
                      <span class="pill pill-lost">Lost</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($report['item_name']) ?></td>
                  <td><?= htmlspecialchars($report['category']) ?></td>
                  <td><?= htmlspecialchars($report['location']) ?></td>
                  <td><?= htmlspecialchars($report['date_event']) ?></td>
                  <td><span class="status <?= $status_class ?>"><?= $status_text ?></span></td>
                  <td>
                    <div class="actions-col">
                      <?php if (strtolower($report['status']) === 'pending'): ?>
                        <button type="button" class="action-btn edit" data-report-id="<?= htmlspecialchars($report['report_id']) ?>">Edit</button>
                      <?php else: ?>
                        <button type="button" class="action-btn view" data-view-report="<?= htmlspecialchars($report['report_id']) ?>">View</button>
                      <?php endif; ?>
                      <a class="action-btn delete" href="delete_report.php?id=<?= urlencode($report['report_id']) ?>" onclick="return confirm('Are you sure you want to delete this report?')">Delete</a>
                    </div>
                  </td>
                </tr>
                <?php 
                    endforeach;
                  else:
                ?>
                <tr>
                  <td colspan="9" style="text-align: center; padding: 40px; color: #9ca3af;">
                    No reports found. <a href="../user_report.php" style="color: #c8102e; font-weight: 600;">Report a lost item</a>
                  </td>
                </tr>
                <?php 
                  endif;
                } catch (Exception $e) {
                  echo '<tr><td colspan="9" style="text-align: center; padding: 40px; color: #ef4444;">Error loading reports: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                }
                ?>
              </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- Edit Modal -->
        <div id="editModal" class="edit-modal" aria-hidden="true">
          <div class="edit-modal__panel" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
            <button type="button" class="edit-modal__close" data-close-modal>&times;</button>
            <form id="editReportForm" class="edit-modal__form">
              <input type="hidden" name="report_id" id="editReportIdField">
              <input type="hidden" name="report_type" id="editReportTypeField">

              <header class="edit-modal__header">
                <div class="edit-modal__header-text">
                  <p class="modal-label">Edit Report</p>
                  <h2 id="editModalTitle">Report</h2>
                  <div class="modal-subtext-row">
                    <p class="modal-subtext">Update the details of your report. Changes will be reviewed by an administrator.</p>
                    <div class="modal-tags">
                      <span class="modal-chip" id="editModalReportType">Lost</span>
                      <span class="modal-chip modal-chip--status" id="editModalStatus">Pending</span>
                    </div>
                  </div>
                </div>
              </header>

              <div class="edit-modal__scroll">
                <div class="modal-grid">
                  <div class="modal-field modal-field--info">
                    <span class="field-label">Report ID</span>
                    <span class="field-static" id="editReportIdDisplay">—</span>
                  </div>
                  <label class="modal-field">
                    <span class="field-label">Item Name *</span>
                    <input type="text" name="item_name" id="editItemName" required>
                  </label>
                  <label class="modal-field">
                    <span class="field-label">Category *</span>
                    <input type="text" name="category" id="editCategory" required>
                  </label>
                  <label class="modal-field" id="locationLabel">
                    <span class="field-label" id="locationLabelText">Location *</span>
                    <input type="text" name="location" id="editLocation" required>
                  </label>
                  <label class="modal-field">
                    <span class="field-label">Date *</span>
                    <input type="date" name="date_event" id="editDate" required>
                  </label>
                  <label class="modal-field">
                    <span class="field-label">Time</span>
                    <input type="time" name="time_event" id="editTime">
                  </label>
                  <label class="modal-field" data-found-only>
                    <span class="field-label">Pickup Location</span>
                    <input type="text" name="pickup_location" id="editPickup">
                  </label>
                  <label class="modal-field modal-field--full">
                    <span class="field-label">Description *</span>
                    <textarea name="description" id="editDescription" rows="4" required></textarea>
                  </label>
                  <label class="modal-field">
                    <span class="field-label">Contact Email *</span>
                    <input type="email" name="contact_email" id="editEmail" required>
                  </label>
                  <label class="modal-field">
                    <span class="field-label">Contact Phone *</span>
                    <input type="tel" name="contact_phone" id="editPhone" required>
                  </label>
                </div>

                <div class="modal-image">
                  <span class="field-label">Current Image</span>
                  <div class="modal-image__preview">
                    <img id="editModalImage" alt="Current item image" src="" loading="lazy">
                  </div>
                  <p class="modal-image__hint">To replace the image, please contact support or submit a new report.</p>
                </div>
              </div>

              <div class="modal-alert" id="editModalAlert" hidden></div>

              <div class="edit-modal__actions">
                <button type="button" class="btn btn-outline" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-cta" data-save-btn>Save Changes</button>
              </div>
            </form>
          </div>
        </div>

        <!-- View Modal -->
        <div id="viewModal" class="view-modal" aria-hidden="true">
          <div class="view-modal__panel" role="dialog" aria-modal="true" aria-labelledby="viewModalTitle">
            <button type="button" class="view-modal__close" data-view-close>&times;</button>
            <header class="view-modal__header">
              <div class="view-modal__text">
                <p class="view-modal__eyebrow">Report Details</p>
                <h2 id="viewModalTitle" class="view-modal__title">Item Report</h2>
                <div class="view-modal__subtitle-row">
                  <p class="view-modal__subtitle">Complete information about this report. Scroll to view all details.</p>
                  <div class="view-modal__tags">
                    <span class="view-chip" id="viewModalType">Lost</span>
                    <span class="view-chip view-chip--status" id="viewModalStatus">Pending Approval</span>
                  </div>
                </div>
              </div>
            </header>

            <div class="view-modal__body">
              <figure class="view-modal__image">
                <img id="viewModalImage" src="" alt="Report item image" loading="lazy">
              </figure>

              <section class="view-modal__section">
                <header class="view-section__header">
                  <span class="view-section__label">Report ID</span>
                  <span class="view-section__value" id="viewModalReportId">—</span>
                </header>

                <div class="view-grid">
                  <div class="view-field">
                    <span class="view-field__label">Item Name</span>
                    <span class="view-field__value" id="viewModalItem">—</span>
                  </div>
                  <div class="view-field">
                    <span class="view-field__label">Category</span>
                    <span class="view-field__value" id="viewModalCategory">—</span>
                  </div>
                  <div class="view-field">
                    <span class="view-field__label" id="viewModalLocationLabel">Location</span>
                    <span class="view-field__value" id="viewModalLocation">—</span>
                  </div>
                  <div class="view-field">
                    <span class="view-field__label">Date</span>
                    <span class="view-field__value" id="viewModalDate">—</span>
                  </div>
                  <div class="view-field" id="viewModalTimeRow">
                    <span class="view-field__label">Time</span>
                    <span class="view-field__value" id="viewModalTime">—</span>
                  </div>
                  <div class="view-field" id="viewModalPickupRow">
                    <span class="view-field__label">Pickup Location</span>
                    <span class="view-field__value" id="viewModalPickup">—</span>
                  </div>
                </div>

                <article class="view-description">
                  <h3>Description</h3>
                  <div class="view-description__content" id="viewModalDescription">—</div>
                </article>
              </section>

              <section class="view-modal__section">
                <h3 class="view-section__heading">Contact Information</h3>
                <div class="view-contact">
                  <div class="view-contact__item">
                    <span class="view-field__label">Email</span>
                    <span class="view-field__value" id="viewModalEmail">—</span>
                  </div>
                  <div class="view-contact__item">
                    <span class="view-field__label">Phone</span>
                    <span class="view-field__value" id="viewModalPhone">—</span>
                  </div>
                </div>
              </section>

              <div class="view-modal__footer">
                <button type="button" class="btn btn-cta" data-view-close>Close</button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script>
    (function(){
      const reportsData = <?php echo json_encode($reports ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
      const reportMap = {};
      reportsData.forEach(rep => {
        if (rep && rep.report_id) {
          reportMap[rep.report_id] = rep;
        }
      });

      const modal = document.getElementById('editModal');
      const form = document.getElementById('editReportForm');
      const viewModal = document.getElementById('viewModal');
      if (!modal || !form) {
        return;
      }

      const PLACEHOLDER_IMAGE = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='800' height='400'><rect width='100%' height='100%' fill='%23F1F5F9'/><text x='50%' y='52%' dominant-baseline='middle' text-anchor='middle' fill='%2394A3B8' font-family='Inter, Arial, sans-serif' font-size='32'>No item photo</text></svg>";

      const fields = {
        id: document.getElementById('editReportIdField'),
        idDisplay: document.getElementById('editReportIdDisplay'),
        type: document.getElementById('editReportTypeField'),
        item: document.getElementById('editItemName'),
        category: document.getElementById('editCategory'),
        location: document.getElementById('editLocation'),
        date: document.getElementById('editDate'),
        time: document.getElementById('editTime'),
        description: document.getElementById('editDescription'),
        email: document.getElementById('editEmail'),
        phone: document.getElementById('editPhone'),
        pickup: document.getElementById('editPickup')
      };

      const headerEls = {
        title: document.getElementById('editModalTitle'),
        typeChip: document.getElementById('editModalReportType'),
        statusChip: document.getElementById('editModalStatus'),
        image: document.getElementById('editModalImage'),
        locationLabel: document.getElementById('locationLabelText')
      };

      const pickupField = form.querySelector('[data-found-only]');
      const alertBox = document.getElementById('editModalAlert');
      const saveBtn = form.querySelector('[data-save-btn]');

      function setAlert(message) {
        if (!alertBox) return;
        if (!message) {
          alertBox.hidden = true;
          alertBox.textContent = '';
        } else {
          alertBox.hidden = false;
          alertBox.textContent = message;
        }
      }

      function openModal(report) {
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        modal.classList.add('is-visible');
        form.scrollTop = 0;
      }

      function closeModal() {
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('is-visible');
        if (!viewModal || viewModal.getAttribute('aria-hidden') === 'true') {
          document.body.classList.remove('modal-open');
        }
        setAlert('');
        form.reset();
      }

      function populateForm(report) {
        const type = report.type || 'Lost';
        fields.id.value = report.report_id || '';
        if (fields.idDisplay) {
          fields.idDisplay.textContent = report.report_id || '—';
        }
        fields.type.value = type;
        fields.item.value = report.item_name || '';
        fields.category.value = report.category || '';
        fields.location.value = report.location || '';
        fields.date.value = report.date_event || '';
        fields.time.value = report.time_event || '';
        fields.description.value = report.description || '';
        fields.email.value = report.contact_email || '';
        fields.phone.value = report.contact_phone || '';
        if (fields.pickup) {
          fields.pickup.value = report.pickup_location || '';
        }

        headerEls.title.textContent = report.item_name || 'Edit Report';
        headerEls.typeChip.textContent = type;
        headerEls.typeChip.dataset.type = type.toLowerCase();
        headerEls.statusChip.textContent = report.status || 'Pending';
        headerEls.statusChip.dataset.status = (report.status || 'Pending').toLowerCase();
        headerEls.locationLabel.textContent = type === 'Found' ? 'Location Found *' : 'Location Lost *';

        const showPickup = type === 'Found';
        if (pickupField) {
          pickupField.hidden = !showPickup;
          pickupField.style.display = showPickup ? '' : 'none';
          if (fields.pickup) {
            fields.pickup.disabled = !showPickup;
            if (!showPickup) {
              fields.pickup.value = '';
            }
          }
        }

        const imgSrc = report.photo_url || '';
        if (headerEls.image) {
          headerEls.image.src = imgSrc;
          headerEls.image.alt = 'Photo for ' + (report.item_name || 'item');
        }
      }

      document.querySelectorAll('.action-btn.edit').forEach(btn => {
        btn.addEventListener('click', event => {
          event.preventDefault();
          const reportId = btn.getAttribute('data-report-id');
          const data = reportMap[reportId];
          if (!data) {
            setAlert('Unable to load report details.');
            return;
          }
          populateForm(data);
          openModal(data);
        });
      });

      modal.addEventListener('click', event => {
        if (event.target === modal) {
          closeModal();
        }
      });

      modal.querySelectorAll('[data-close-modal]').forEach(el => {
        el.addEventListener('click', closeModal);
      });
      const VIEW_STATUS_LABELS = {
        pending: 'Pending Approval',
        verified: 'Verified',
        rejected: 'Rejected',
        claimed: 'Claimed'
      };

      function sanitizeText(value, fallback = '—') {
        if (value === null || value === undefined) {
          return fallback;
        }
        const trimmed = String(value).trim();
        return trimmed === '' ? fallback : trimmed;
      }

      if (viewModal) {
        const viewRefs = {
          image: document.getElementById('viewModalImage'),
          typeChip: document.getElementById('viewModalType'),
          statusChip: document.getElementById('viewModalStatus'),
          reportId: document.getElementById('viewModalReportId'),
          item: document.getElementById('viewModalItem'),
          category: document.getElementById('viewModalCategory'),
          locationLabel: document.getElementById('viewModalLocationLabel'),
          location: document.getElementById('viewModalLocation'),
          date: document.getElementById('viewModalDate'),
          time: document.getElementById('viewModalTime'),
          timeRow: document.getElementById('viewModalTimeRow'),
          pickupRow: document.getElementById('viewModalPickupRow'),
          pickup: document.getElementById('viewModalPickup'),
          description: document.getElementById('viewModalDescription'),
          email: document.getElementById('viewModalEmail'),
          phone: document.getElementById('viewModalPhone')
        };

        function populateViewModal(report) {
          const type = sanitizeText(report.type || 'Lost', 'Lost');
          const statusRaw = sanitizeText(report.status || 'Pending', 'Pending');
          const statusKey = statusRaw.toLowerCase();
          const statusLabel = VIEW_STATUS_LABELS[statusKey] || statusRaw;

          if (viewRefs.typeChip) {
            viewRefs.typeChip.textContent = type;
            viewRefs.typeChip.dataset.type = type.toLowerCase();
          }
          if (viewRefs.statusChip) {
            viewRefs.statusChip.textContent = statusLabel;
            viewRefs.statusChip.dataset.status = statusKey;
          }

          if (viewRefs.image) {
            const rawPhoto = typeof report.photo_url === 'string' ? report.photo_url.trim() : '';
            const hasPhoto = rawPhoto !== '';
            viewRefs.image.src = hasPhoto ? rawPhoto : PLACEHOLDER_IMAGE;
            if (hasPhoto) {
              viewRefs.image.removeAttribute('data-placeholder');
            } else {
              viewRefs.image.setAttribute('data-placeholder', 'true');
            }
            viewRefs.image.alt = `Photo for ${sanitizeText(report.item_name, 'item')}`;
          }

          if (viewRefs.reportId) viewRefs.reportId.textContent = sanitizeText(report.report_id);
          if (viewRefs.item) viewRefs.item.textContent = sanitizeText(report.item_name);
          if (viewRefs.category) viewRefs.category.textContent = sanitizeText(report.category);

          const locationLabel = type.toLowerCase() === 'found' ? 'Location Found' : 'Location Lost';
          if (viewRefs.locationLabel) viewRefs.locationLabel.textContent = locationLabel;
          if (viewRefs.location) viewRefs.location.textContent = sanitizeText(report.location);

          if (viewRefs.date) viewRefs.date.textContent = sanitizeText(report.date_event);

          const timeValue = sanitizeText(report.time_event, '');
          if (viewRefs.timeRow) {
            const hasTime = timeValue !== '';
            viewRefs.timeRow.style.display = hasTime ? '' : 'none';
            if (hasTime && viewRefs.time) {
              viewRefs.time.textContent = timeValue;
            }
          }

          if (viewRefs.pickupRow) {
            const isFound = type.toLowerCase() === 'found';
            const pickupValue = sanitizeText(report.pickup_location, '');
            const showPickup = isFound;
            viewRefs.pickupRow.style.display = showPickup ? '' : 'none';
            if (showPickup && viewRefs.pickup) {
              viewRefs.pickup.textContent = pickupValue || '—';
            }
          }

          if (viewRefs.description) {
            viewRefs.description.textContent = sanitizeText(report.description);
          }

          if (viewRefs.email) viewRefs.email.textContent = sanitizeText(report.contact_email);
          if (viewRefs.phone) viewRefs.phone.textContent = sanitizeText(report.contact_phone);
        }

        function openViewModal(report) {
          populateViewModal(report);
          viewModal.setAttribute('aria-hidden', 'false');
          viewModal.classList.add('is-visible');
          document.body.classList.add('modal-open');
        }

        function closeViewModal() {
          viewModal.setAttribute('aria-hidden', 'true');
          viewModal.classList.remove('is-visible');
          if (modal.getAttribute('aria-hidden') === 'true') {
            document.body.classList.remove('modal-open');
          }
        }

        document.querySelectorAll('[data-view-report]').forEach(btn => {
          btn.addEventListener('click', event => {
            event.preventDefault();
            const reportId = btn.getAttribute('data-view-report');
            const data = reportMap[reportId];
            if (!data) {
              return;
            }
            openViewModal(data);
          });
        });

        viewModal.addEventListener('click', event => {
          if (event.target === viewModal) {
            closeViewModal();
          }
        });

        viewModal.querySelectorAll('[data-view-close]').forEach(el => {
          el.addEventListener('click', closeViewModal);
        });

        document.addEventListener('keydown', event => {
          if (event.key === 'Escape' && viewModal.getAttribute('aria-hidden') === 'false') {
            closeViewModal();
          }
        });
      }

      form.addEventListener('submit', async event => {
        event.preventDefault();
        const formData = new FormData(form);
        setAlert('');
        if (saveBtn) {
          saveBtn.disabled = true;
          saveBtn.textContent = 'Saving...';
        }
        try {
          const response = await fetch('update_report.php', {
            method: 'POST',
            body: formData
          });
          const result = await response.json().catch(() => ({ success: false, error: 'Unexpected response from server.' }));
          if (!response.ok || !result.success) {
            throw new Error(result.error || 'Failed to update report.');
          }
          closeModal();
          window.location.reload();
        } catch (err) {
          setAlert(err.message || 'Unable to save changes.');
        } finally {
          if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Changes';
          }
        }
      });

      document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
          closeModal();
        }
      });
    })();
  </script>
  <script src="../avatar_dropdown.js"></script>
  <script src="dashboard.js"></script>
</body>
</html>

