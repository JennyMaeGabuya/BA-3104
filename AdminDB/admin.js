// admin-dashboard.js
// Small behavior for toggles, nav active class and demo interactions.

document.addEventListener('DOMContentLoaded', () => {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  const navItems = document.querySelectorAll('.nav-item');
  const notifBtn = document.getElementById('notifBtn');
  const topbarBadge = document.getElementById('topbarBadge');
  const pendingBadge = document.getElementById('pendingBadge');

  // Avatar dropdown behavior
  const adminAvatar = document.getElementById('adminAvatar');
  const avatarDropdown = document.getElementById('avatarDropdown');
  const themeToggle = document.getElementById('themeToggle');

  if (adminAvatar && avatarDropdown) {
    adminAvatar.addEventListener('click', (e) => {
      const open = avatarDropdown.getAttribute('aria-hidden') === 'false';
      avatarDropdown.setAttribute('aria-hidden', open ? 'true' : 'false');
      avatarDropdown.style.display = open ? 'none' : 'block';
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
      if (!adminAvatar.contains(e.target) && !avatarDropdown.contains(e.target)) {
        avatarDropdown.setAttribute('aria-hidden', 'true');
        avatarDropdown.style.display = 'none';
      }
    });
  }

  // Theme toggle (stores preference in localStorage)
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const dark = document.documentElement.classList.toggle('dark-mode');
      localStorage.setItem('findit_theme_dark', dark ? '1' : '0');
      themeToggle.textContent = dark ? 'Light mode' : 'Dark mode';
    });

    // initialize from storage
    const saved = localStorage.getItem('findit_theme_dark');
    if (saved === '1') {
      document.documentElement.classList.add('dark-mode');
      themeToggle.textContent = 'Light mode';
    } else {
      themeToggle.textContent = 'Dark mode';
    }
  }

  // Mobile menu toggle: show/hide sidebar
  menuToggle && menuToggle.addEventListener('click', () => {
    if (sidebar.style.display === 'block') {
      sidebar.style.display = '';
    } else {
      sidebar.style.display = 'block';
      sidebar.style.position = 'fixed';
      sidebar.style.background = '#fff';
      sidebar.style.zIndex = 60;
    }
  });

  // nav active behavior (for demo pages)
  navItems.forEach(item => {
    item.addEventListener('click', (e) => {
      navItems.forEach(i => i.classList.remove('nav-item--active'));
      item.classList.add('nav-item--active');
    });
  });

  // notifications demo: clear badges and show message
  notifBtn && notifBtn.addEventListener('click', () => {
    if (topbarBadge) topbarBadge.style.display = 'none';
    if (pendingBadge) pendingBadge.style.display = 'none';
    alert('Notifications cleared (demo).');
  });

  // Pending-page status filtering
  const statusList = document.getElementById('adminStatusList');
  const emptyState = document.getElementById('adminEmptyState');
  const tabButtons = document.querySelectorAll('.tabs .tab[data-tab]');
  const statusCards = Array.from(document.querySelectorAll('[data-status-card]'));
  const claimTable = document.getElementById('reportsTbody');

  if (statusList && tabButtons.length) {
    const messages = {
      pending: 'No pending reports to review.',
      verified: 'No verified reports found.',
      rejected: 'No rejected reports found.'
    };

    const hasCardsFor = (key) => statusCards.some(card => card.dataset.status === key);

    function setActiveTab(name) {
      tabButtons.forEach(btn => {
        const isActive = btn.dataset.tab === name;
        btn.classList.toggle('tab--active', isActive);
        btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
    }

    function showStatus(name) {
      let visible = 0;
      statusCards.forEach(card => {
        const match = card.dataset.status === name;
        card.classList.toggle('hidden', !match);
        if (match) visible += 1;
      });

      const hasVisible = visible > 0;
      statusList.classList.toggle('hidden', !hasVisible);
      if (emptyState) {
        emptyState.classList.toggle('hidden', hasVisible);
        if (!hasVisible) {
          emptyState.textContent = messages[name] || 'No reports to display.';
        }
      }
    }

    let initial = statusList.dataset.defaultTab || 'pending';
    if (!hasCardsFor(initial)) {
      const fallbacks = ['pending', 'verified', 'rejected'];
      const replacement = fallbacks.find(key => hasCardsFor(key));
      if (replacement) initial = replacement;
    }

    setActiveTab(initial);
    showStatus(initial);

    tabButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.tab || 'pending';
        setActiveTab(target);
        showStatus(target);
      });
    });
  }

  if (claimTable) {
    claimTable.addEventListener('click', async (event) => {
      const target = event.target;
      if (!target.closest('.action-btn')) return;
      const rowActions = target.closest('.actions-col');
      const requestCode = rowActions ? rowActions.getAttribute('data-request') : '';
      if (!requestCode) return;

      if (target.classList.contains('view')) {
        const claimData = target.getAttribute('data-claim');
        if (claimData) {
          try {
            const parsed = JSON.parse(claimData);
            showClaimModal(parsed);
          } catch (error) {
            alert('Unable to open claim details.');
          }
        }
      }

      if (target.classList.contains('claim')) {
        await handleClaimStatus(requestCode, target);
      }
    });
  }

  async function handleClaimStatus(requestCode, button) {
    if (!window.CLAIM_UPDATE_ENDPOINT) {
      alert('Claim API not configured.');
      return;
    }
    button.disabled = true;
    const formData = new FormData();
    formData.append('request_code', requestCode);
    formData.append('status', 'Claimed');
    try {
      const response = await fetch(window.CLAIM_UPDATE_ENDPOINT, { method: 'POST', body: formData });
      const payload = await response.json();
      if (!payload.success) throw new Error(payload.error || 'Update failed');
      button.textContent = 'Claimed';
      button.classList.add('is-disabled');
      showToast('Claim request marked as resolved');
    } catch (error) {
      alert(error.message || 'Unable to update claim request');
    } finally {
      button.disabled = false;
    }
  }

  function showClaimModal(data) {
    const modal = document.createElement('dialog');
    modal.className = 'claim-dialog';
    const photo = data.found_photo ? `/BA-3104/${data.found_photo}` : 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="240" height="180"><rect width="100%" height="100%" fill="%23f8fafc"/></svg>';
    modal.innerHTML = `
      <form method="dialog" class="claim-dialog__panel">
        <h3>Claim Request ${data.request_code || ''}</h3>
        <p><strong>Reporter:</strong> ${data.requester_name || 'Unknown'}</p>
        <p><strong>Item:</strong> ${data.found_item || 'Item'} (${data.item_type || 'Found'})</p>
        <p><strong>Location:</strong> ${data.found_location || '—'}</p>
        <p><strong>Contact:</strong> ${data.contact_info || '—'}</p>
        <p><strong>Details:</strong></p>
        <div class="claim-dialog__details">${(data.details || '').replace(/\n/g, '<br>')}</div>
        <div class="claim-dialog__photo">
          <img src="${photo}" alt="Item photo">
        </div>
        <div class="claim-dialog__foot">
          <button type="submit" class="btn">Close</button>
        </div>
      </form>`;
    document.body.appendChild(modal);
    modal.showModal();
    modal.addEventListener('close', () => modal.remove());
  }
});

// script.js - Settings form behaviour (demo only)
// Uses the HTML structure in index.html

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('settingsForm');
  const notifBtn = document.getElementById('notifBtn');

  if (!form) {
    return;
  }

  // Keep nav active highlight (visual only)
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
      document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('nav-item--active'));
      item.classList.add('nav-item--active');
    });
  });

  // Save settings (demo)
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const data = {
      autoVerify: form.autoVerify.value,
      claimDeadline: form.claimDeadline.value,
      emailReports: form.emailReports.checked,
      dailySummary: form.dailySummary.checked
    };

    // simple feedback, replace with API call to save server-side
    showToast('Settings saved');
    console.log('Settings payload', data);
  });

  // notifications button demo
  notifBtn && notifBtn.addEventListener('click', () => {
    const badge = notifBtn.querySelector('.top-badge');
    if (badge) badge.style.display = 'none';
    showToast('No new notifications (demo)');
  });

  // small toast
  function showToast(msg) {
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.position = 'fixed';
    t.style.right = '18px';
    t.style.bottom = '18px';
    t.style.padding = '10px 14px';
    t.style.background = 'rgba(17,24,39,0.92)';
    t.style.color = '#fff';
    t.style.borderRadius = '8px';
    t.style.boxShadow = '0 8px 20px rgba(0,0,0,0.12)';
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; }, 1600);
    setTimeout(() => t.remove(), 2000);
  }
});
