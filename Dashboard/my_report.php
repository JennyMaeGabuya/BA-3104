<?php
/**
 * My Reports Page
 * Requires authentication - redirects to login if not logged in
 */

require_once '../auth_check.php';
require_once '../db_config.php';

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
        <span class="nav-badge" id="sidebarBadge">4</span>
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
              <span class="topbar-badge" id="topbarBadge">4</span>
            </button>

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
                        <button type="button" class="action-btn view" disabled>View</button>
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
                <p class="modal-label">Edit Report</p>
                <h2 id="editModalTitle">Report</h2>
                <div class="modal-tags">
                  <span class="modal-chip" id="editModalReportType">Lost</span>
                  <span class="modal-chip modal-chip--status" id="editModalStatus">Pending</span>
                </div>
                <div class="modal-id-line">
                  <span class="modal-id-label">Report ID:</span>
                  <span class="modal-id-value" id="editModalReportId">—</span>
                </div>
                <p class="modal-subtext">Update the details of your report. Changes will be reviewed by an administrator.</p>
              </header>

              <div class="edit-modal__scroll">
                <div class="modal-grid">
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
                  <img id="editModalImage" alt="Current item image" src="" loading="lazy">
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
      if (!modal || !form) {
        return;
      }

      const fields = {
        id: document.getElementById('editReportIdField'),
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
        idValue: document.getElementById('editModalReportId'),
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
        document.body.classList.remove('modal-open');
        setAlert('');
        form.reset();
      }

      function populateForm(report) {
        const type = report.type || 'Lost';
        fields.id.value = report.report_id || '';
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
        headerEls.idValue.textContent = report.report_id || '—';
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

      document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
          closeModal();
        }
      });

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
    })();
  </script>
  <script src="../avatar_dropdown.js"></script>
  <script src="dashboard.js"></script>
</body>
</html>

