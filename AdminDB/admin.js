// admin-dashboard.js
// Small behavior for toggles, nav active class and demo interactions.

function showToast(msg) {
  const toast = document.createElement('div');
  toast.textContent = msg;
  toast.style.position = 'fixed';
  toast.style.right = '18px';
  toast.style.bottom = '18px';
  toast.style.padding = '10px 14px';
  toast.style.background = 'rgba(17,24,39,0.92)';
  toast.style.color = '#fff';
  toast.style.borderRadius = '8px';
  toast.style.boxShadow = '0 8px 20px rgba(0,0,0,0.12)';
  toast.style.zIndex = '4000';
  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; }, 1600);
  setTimeout(() => toast.remove(), 2000);
}

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
  const statChips = Array.from(document.querySelectorAll('.stat-chip[data-filter-target]'));
  const managementPanels = Array.from(document.querySelectorAll('.management-panel[data-panel-type]'));
  const managementGrid = document.querySelector('.management-grid[data-panel-filter]');

  if (statChips.length && managementPanels.length && managementGrid) {
    let activeFilter = '';

    const setChipStates = (target) => {
      statChips.forEach(chip => {
        const isActive = target && chip.dataset.filterTarget === target;
        chip.classList.toggle('is-active', isActive);
        chip.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
    };

    const applyPanelFilter = (target) => {
      const effectiveTarget = target === 'all' ? '' : target;
      managementPanels.forEach(panel => {
        const matches = !effectiveTarget || panel.dataset.panelType === effectiveTarget;
        panel.classList.toggle('is-hidden', !matches);
        panel.setAttribute('aria-hidden', matches ? 'false' : 'true');
      });
      managementGrid.classList.toggle('is-single', Boolean(effectiveTarget));
    };

    const toggleFilter = (target) => {
      const nextFilter = activeFilter === target ? '' : target;
      activeFilter = nextFilter;
      setChipStates(nextFilter);
      applyPanelFilter(nextFilter);
    };

    statChips.forEach(chip => {
      chip.addEventListener('click', () => {
        toggleFilter(chip.dataset.filterTarget || '');
      });
      chip.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          chip.click();
        }
      });
    });

    applyPanelFilter(activeFilter);
  }

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
      const btn = event.target.closest('.claim-pill-btn');
      if (!btn) return;
      const group = btn.closest('.claim-action-group');
      const requestCode = group ? group.getAttribute('data-request') : '';
      if (!requestCode) return;

      if (btn.hasAttribute('data-claim-view')) {
        const json = btn.getAttribute('data-match');
        if (!json) return;
        try {
          const payload = JSON.parse(json);
          openMatchModal(payload);
        } catch (err) {
          alert('Unable to open match details.');
        }
        return;
      }

      const action = btn.getAttribute('data-claim-action');
      if (action) {
        await handleClaimStatus(requestCode, btn, action);
      }
    });
  }

  const workflowLabels = {
    pending: 'Pending review',
    claimable: 'Ready for pickup',
    claimed: 'Claim completed',
    rejected: 'Match dismissed'
  };

  const workflowMessages = {
    pending: 'Workflow reset',
    claimable: 'Marked as claimable',
    claimed: 'Marked as claimed',
    rejected: 'Match dismissed'
  };

  const workflowHints = {
    pending: 'Confirm both reports before choosing an action.',
    claimable: 'Notify the claimant to pick up the item, then mark Claimed after release.',
    claimed: 'This match is complete. No further action is required.',
    rejected: 'Suggestion ignored. A new match will appear if the system finds another candidate.'
  };

  function applyWorkflowState(container, status) {
    const normalized = workflowLabels[status] ? status : 'pending';
    container.dataset.status = normalized;
    const pill = container.querySelector('.workflow-pill');
    if (pill) {
      pill.textContent = workflowLabels[normalized];
      pill.className = `workflow-pill workflow-pill--${normalized}`;
    }
    container.querySelectorAll('[data-workflow-action]').forEach(btn => {
      const action = btn.dataset.workflowAction;
      let shouldDisable = false;
      if (normalized === 'claimed') {
        shouldDisable = true;
      } else if (normalized === 'claimable') {
        shouldDisable = action === 'claimable';
      } else if (normalized === 'rejected') {
        shouldDisable = action === 'reject';
      }
      btn.disabled = shouldDisable;
      btn.classList.toggle('is-disabled', shouldDisable);
    });
    const hintEl = container.querySelector('[data-workflow-hint]');
    if (hintEl) {
      hintEl.textContent = workflowHints[normalized] || workflowHints.pending;
    }
  }

  function setWorkflowBusy(containers, busy) {
    containers.forEach(container => {
      container.classList.toggle('is-processing', busy);
      container.querySelectorAll('[data-workflow-action]').forEach(btn => {
        if (busy) {
          btn.disabled = true;
          btn.classList.add('is-busy');
        } else {
          btn.classList.remove('is-busy');
        }
      });
      if (!busy) {
        applyWorkflowState(container, container.dataset.status || 'pending');
      }
    });
  }

  function syncWorkflowContainers(lostId, foundId, status) {
    const candidates = Array.from(document.querySelectorAll('[data-workflow]'));
    candidates.forEach(container => {
      if (container.dataset.lostId === lostId && container.dataset.foundId === foundId) {
        applyWorkflowState(container, status);
      }
    });
  }

  const workflowBlocks = Array.from(document.querySelectorAll('[data-workflow]'));
  if (workflowBlocks.length) {
    workflowBlocks.forEach(container => applyWorkflowState(container, container.dataset.status || 'pending'));

    document.addEventListener('click', async (event) => {
      const button = event.target.closest('[data-workflow-action]');
      if (!button) return;
      const container = button.closest('[data-workflow]');
      if (!container) return;

      const action = button.dataset.workflowAction;
      if (!action) return;
      if (!window.MATCH_WORKFLOW_ENDPOINT) {
        alert('Match workflow endpoint not configured.');
        return;
      }
      if (action === 'reject') {
        const confirmReject = confirm("Mark this suggestion as not a match?");
        if (!confirmReject) {
          return;
        }
      }
      const lostId = container.dataset.lostId || '';
      const foundId = container.dataset.foundId || '';
      if (!lostId || !foundId) {
        alert('Match identifiers are missing.');
        return;
      }
      const prevStatus = container.dataset.status || 'pending';
      const linked = Array.from(document.querySelectorAll('[data-workflow]')).filter(block => block.dataset.lostId === lostId && block.dataset.foundId === foundId);
      setWorkflowBusy(linked, true);
      try {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('lost_report_id', lostId);
        formData.append('found_report_id', foundId);
        const response = await fetch(window.MATCH_WORKFLOW_ENDPOINT, { method: 'POST', body: formData });
        if (!response.ok) {
          throw new Error('Server error while updating match.');
        }
        const payload = await response.json();
        if (!payload.success) {
          throw new Error(payload.error || 'Unable to update match.');
        }
        const nextStatus = payload.status || prevStatus;
        syncWorkflowContainers(lostId, foundId, nextStatus);
        const toastMsg = workflowMessages[nextStatus] || 'Match updated';
        showToast(toastMsg);
      } catch (error) {
        syncWorkflowContainers(lostId, foundId, prevStatus);
        alert(error.message || 'Unable to update match.');
      } finally {
        setWorkflowBusy(linked, false);
      }
    });
  }

  async function handleClaimStatus(requestCode, button, action) {
    if (!window.CLAIM_UPDATE_ENDPOINT) {
      alert('Claim API not configured.');
      return;
    }
    button.disabled = true;
    const formData = new FormData();
    formData.append('request_code', requestCode);
    formData.append('status', action);
    try {
      const response = await fetch(window.CLAIM_UPDATE_ENDPOINT, { method: 'POST', body: formData });
      const payload = await response.json();
      if (!payload.success) throw new Error(payload.error || 'Update failed');
      const statusLabel = action;
      button.classList.add('is-disabled');
      showToast(`Claim request updated: ${statusLabel}`);
      const statusPill = button.closest('.claim-actions')?.querySelector('[data-claim-status-label]');
      if (statusPill) {
        statusPill.textContent = statusLabel === 'Approved' ? 'Claimable' : (statusLabel === 'Claimed' ? 'Claimed' : (statusLabel === 'Rejected' ? "Doesn't match" : 'Pending review'));
      }
    } catch (error) {
      alert(error.message || 'Unable to update claim request');
    } finally {
      button.disabled = false;
    }
  }

  function openMatchModal(payload){
    const modal = document.getElementById('matchModal');
    if (!modal) return;
    const scoreEl = document.getElementById('matchModalScore');
    const labelEl = document.getElementById('matchModalLabel');
    const lostEl = document.getElementById('matchModalLost');
    const foundEl = document.getElementById('matchModalFound');
    const reasonsEl = document.getElementById('matchModalReasons');
    const proofEl = document.getElementById('matchModalProof');
    const idLinkEl = document.getElementById('matchModalIdLink');
    const idMissingEl = document.getElementById('matchModalIdMissing');
    const idImageEl = document.getElementById('matchModalIdImage');
    const idBlockEl = document.getElementById('matchModalIdBlock');
    const dateFormatter = new Intl.DateTimeFormat(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

    const formatDateForModal = (raw) => {
      if (!raw) return '—';
      const parsed = new Date(raw);
      return Number.isNaN(parsed.getTime()) ? raw : dateFormatter.format(parsed);
    };

    const escapeHtml = (value) => String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');

    const formatItemBlock = (data = {}, type = 'lost') => {
      const locationLabel = type === 'lost' ? 'Last seen' : 'Found at';
      const dateLabel = type === 'lost' ? 'Date lost' : 'Date found';
      const rawDate = data.event_date || '';
      const descSource = (data.description && String(data.description).trim().length)
        ? data.description
        : 'No description provided.';
      const safeDesc = escapeHtml(descSource).replace(/\n/g, '<br>');
      return `
        <h4>${escapeHtml(data.item_name || 'Item')}</h4>
        <div class="match-modal__item-meta">
          <div><span>Report ID</span>${escapeHtml(data.report_id || '—')}</div>
          <div><span>Category</span>${escapeHtml(data.category || '—')}</div>
          <div><span>${locationLabel}</span>${escapeHtml(data.location || '—')}</div>
          <div><span>${dateLabel}</span>${escapeHtml(formatDateForModal(rawDate))}</div>
        </div>
        <p class="match-modal__item-desc">${safeDesc}</p>
      `;
    };

    if (scoreEl) scoreEl.textContent = (payload.score ?? 0) + '%';
    if (labelEl) labelEl.textContent = payload.label || 'Match';
    if (lostEl) lostEl.innerHTML = formatItemBlock(payload.lost || {}, 'lost');
    if (foundEl) foundEl.innerHTML = formatItemBlock(payload.found || {}, 'found');
    if (reasonsEl) {
      reasonsEl.innerHTML = '';
      const reasonList = (payload.reasons && payload.reasons.length)
        ? payload.reasons
        : ['No detailed signals were generated for this pair.'];
      reasonList.forEach(reason => {
        const li = document.createElement('li');
        li.className = 'match-modal__reason';
        li.textContent = reason;
        reasonsEl.appendChild(li);
      });
    }
    const showProof = Boolean(payload.has_proof);
    if (proofEl) {
      proofEl.style.display = showProof ? 'flex' : 'none';
    }
    const hasIdPhoto = showProof && Boolean(payload.id_photo && payload.id_photo.trim().length);
    if (idLinkEl) {
      if (hasIdPhoto) {
        idLinkEl.href = payload.id_photo;
        idLinkEl.style.display = 'inline';
      } else {
        idLinkEl.removeAttribute('href');
        idLinkEl.style.display = 'none';
      }
    }
    if (idMissingEl) {
      idMissingEl.style.display = showProof && !hasIdPhoto ? 'inline' : 'none';
    }
    if (idImageEl) {
      if (hasIdPhoto) {
        idImageEl.src = payload.id_photo;
        idImageEl.style.display = 'block';
      } else {
        idImageEl.src = '';
        idImageEl.style.display = 'none';
      }
    }
    if (idBlockEl) {
      idBlockEl.style.display = hasIdPhoto ? 'block' : 'none';
    }
    modal.setAttribute('aria-hidden','false');
    modal.classList.add('is-visible');
    document.body.classList.add('modal-open');
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
});
