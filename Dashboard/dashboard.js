// dashboard.js
// Minimal interactivity: notification toggle, mobile menu toggle, sidebar active item handling, theme toggle.

document.addEventListener('DOMContentLoaded', function () {
  const sidebar = document.getElementById('sidebar');
  const btnMenu = document.getElementById('btnMenu');
  const notifBtn = document.getElementById('notifBtn');
  const topbarBadge = document.getElementById('topbarBadge');
  const sidebarBadge = document.getElementById('sidebarBadge');

  // Theme toggle functionality
  const themeToggle = document.getElementById('themeToggle');
  const themeLabel = document.getElementById('themeLabel');
  const htmlElement = document.documentElement;
  
  // Load saved theme preference
  const savedTheme = localStorage.getItem('theme') || 'light';
  htmlElement.setAttribute('data-theme', savedTheme);
  updateThemeLabel(savedTheme);
  
  // Theme toggle click handler
  if (themeToggle) {
    themeToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const currentTheme = htmlElement.getAttribute('data-theme') || 'light';
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      
      htmlElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('theme', newTheme);
      updateThemeLabel(newTheme);
    });
  }
  
  // Update theme label and icon
  function updateThemeLabel(theme) {
    if (themeLabel) {
      themeLabel.textContent = theme === 'light' ? 'Dark Mode' : 'Light Mode';
    }
    // Update icon
    const themeIcon = themeToggle?.querySelector('.theme-icon');
    if (themeIcon) {
      if (theme === 'light') {
        // Sun icon for light mode (shows "switch to dark")
        themeIcon.innerHTML = `<circle cx="12" cy="12" r="5"></circle>
        <line x1="12" y1="1" x2="12" y2="3"></line>
        <line x1="12" y1="21" x2="12" y2="23"></line>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
        <line x1="1" y1="12" x2="3" y2="12"></line>
        <line x1="21" y1="12" x2="23" y2="12"></line>
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>`;
      } else {
        // Moon icon for dark mode (shows "switch to light")
        themeIcon.innerHTML = `<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>`;
      }
    }
  }

  // Mobile menu toggle: show/hide sidebar
  btnMenu && btnMenu.addEventListener('click', () => {
    if (sidebar.style.display === 'block') {
      sidebar.style.display = 'none';
    } else {
      sidebar.style.display = 'block';
      sidebar.style.position = 'fixed';
      sidebar.style.zIndex = 50;
      sidebar.style.background = '#fff';
    }
  });

  // Notification button: toggle dropdown
  const notificationDropdown = document.getElementById('notificationDropdown');
  notifBtn && notifBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (notificationDropdown) {
      const isVisible = notificationDropdown.style.display === 'block';
      notificationDropdown.style.display = isVisible ? 'none' : 'block';
      
      // Clear badge when opening notifications
      if (!isVisible) {
        if (topbarBadge) topbarBadge.style.display = 'none';
        if (sidebarBadge) sidebarBadge.style.display = 'none';
      }
    }
  });

  // Close notification dropdown when clicking outside
  document.addEventListener('click', (e) => {
    if (notificationDropdown && !e.target.closest('.notification-container')) {
      notificationDropdown.style.display = 'none';
    }
  });

  // nav item active handling
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
      document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('nav-item--active'));
      item.classList.add('nav-item--active');

      // optionally update content area - demo
      const section = item.dataset.section || '';
      if (section && section !== 'dashboard') {
        // For demo, show a short message in console
        console.log('Switch to:', section);
      }
    });
  });

  // simple accessibility: close sidebar when clicking outside on mobile
  document.addEventListener('click', (ev) => {
    if (window.innerWidth <= 800 && sidebar && btnMenu) {
      if (!sidebar.contains(ev.target) && !btnMenu.contains(ev.target)) {
        sidebar.style.display = 'none';
      }
    }
  });
});

// my-reports.js
document.addEventListener('DOMContentLoaded', function () {
  // Auto-highlight active nav based on current filename
  const path = window.location.pathname.split('/').pop();
  const navLinks = document.querySelectorAll('.nav a, .nav .nav-item');
  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (!href) return;
    if (href === path || (href === 'dashboard.html' && (path === '' || path === 'index.html'))) {
      navLinks.forEach(l => l.classList && l.classList.remove('nav-item--active'));
      link.classList && link.classList.add('nav-item--active');
    }
  });

  // Mobile menu toggling could be added here (if a menu-button is implemented)
});

// settings.js
// Simple settings behavior: load/save to localStorage, validation, toast

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('profileForm');
  const btnSave = document.getElementById('btnSave');

  if (!form || !btnSave) {
    return; // Only run settings logic on the settings page
  }

  const inputs = {
    firstName: document.getElementById('firstName'),
    lastName: document.getElementById('lastName'),
    email: document.getElementById('email'),
    phone: document.getElementById('phone'),
  };

  const toast = document.getElementById('toast');
  const toastMessage = document.getElementById('toastMessage');

  // Keys
  const STORAGE_KEY = 'findit:settings';

  // Load saved data
  function load() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return;
      const data = JSON.parse(raw);
      if (data.profile) {
        Object.keys(inputs).forEach(k => { if (data.profile[k] !== undefined) inputs[k].value = data.profile[k]; });
      }
      if (data.prefs) {
        prefs.emailFound.checked = !!data.prefs.emailFound;
        prefs.smsUpdates.checked = !!data.prefs.smsUpdates;
        prefs.weekly.checked = !!data.prefs.weekly;
      }
    } catch (err) {
      console.warn('Could not parse settings:', err);
    }
  }

  // Save data
  function save() {
    const profile = {
      firstName: inputs.firstName.value.trim(),
      lastName: inputs.lastName.value.trim(),
      email: inputs.email.value.trim(),
      phone: inputs.phone.value.trim(),
    };

    const data = { profile };

    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
  }

  function showToast(msg = 'Saved', ms = 2200) {
    toastMessage.textContent = msg;
    toast.hidden = false;
    setTimeout(() => { toast.hidden = true; }, ms);
  }

  // Change password handling
  const securityForm = document.getElementById('securityForm');
  const changeBtn = document.getElementById('changePasswordBtn');
  if (securityForm && changeBtn) {
    securityForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const current = document.getElementById('currentPassword')?.value || '';
      const nw = document.getElementById('newPassword')?.value || '';
      const confirm = document.getElementById('confirmPassword')?.value || '';
      if (!current || !nw || !confirm) {
        showToast('Please fill all password fields');
        return;
      }
      if (nw.length < 8) {
        showToast('New password must be at least 8 characters');
        return;
      }
      if (nw !== confirm) {
        showToast('Passwords do not match');
        return;
      }
      changeBtn.disabled = true;
      try {
        const res = await fetch('change_password.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ current_password: current, new_password: nw })
        });
        const data = await res.json();
        if (data && data.success) {
          showToast(data.message || 'Password changed');
          securityForm.reset();
        } else {
          alert(data && data.error ? data.error : 'Unable to change password');
        }
      } catch (err) {
        alert('An error occurred while changing password.');
      }
      changeBtn.disabled = false;
    });
  }

  // Basic validation
  function validate() {
    // email format (simple)
    const email = inputs.email.value.trim();
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showToast('Please enter a valid email address');
      inputs.email.focus();
      return false;
    }
    // phone basic check (optional)
    const phone = inputs.phone.value.trim();
    if (phone && !/^\+?\d[\d\s-]{7,}$/.test(phone)) {
      showToast('Please enter a valid phone number');
      inputs.phone.focus();
      return false;
    }
    return true;
  }

  // On save
  btnSave.addEventListener('click', () => {
    if (!validate()) return;
    save();
    showToast('Settings saved');
  });

  // initial load
  load();
});

// tabs + filters behavior: safe init after DOM ready
document.addEventListener('DOMContentLoaded', () => {
  const resultsInner = document.getElementById('resultsInner');
  if (!resultsInner) {
    return; // Nothing to filter on pages without the status list
  }

  const tabs = document.querySelectorAll('.tabs .tab[data-tab]');
  const emptyState = document.getElementById('emptyState');
  const statusList = document.getElementById('statusList');
  const cards = Array.from(document.querySelectorAll('[data-status-card]'));
  const searchInput = document.getElementById('searchInput');
  const filterLocation = document.getElementById('filterLocation');
  const filterType = document.getElementById('filterType');
  const applyBtn = document.getElementById('applyBtn');

  const EMPTY_MESSAGES = {
    verified: 'No verified reports yet.',
    rejected: 'No rejected reports yet.'
  };

  const hasCardsFor = (status) => cards.some(card => card.dataset.status === status);

  let activeTab = resultsInner.dataset.defaultTab || 'verified';

  function getSearchTerm() {
    const raw = searchInput?.value || '';
    return raw.trim().toLowerCase();
  }

  function refreshList() {
    const searchTerm = getSearchTerm();
    const locationValue = filterLocation?.value || 'all';
    const typeValue = filterType?.value || 'all';
    const filtersApplied = searchTerm !== '' || locationValue !== 'all' || typeValue !== 'all';

    let visibleCount = 0;
    cards.forEach(card => {
      const matchesStatus = card.dataset.status === activeTab;
      const matchesSearch = !searchTerm || (card.dataset.search || '').includes(searchTerm);
      const matchesLocation = locationValue === 'all' || (card.dataset.location || '') === locationValue;
      const matchesType = typeValue === 'all' || (card.dataset.type || '') === typeValue;

      const isVisible = matchesStatus && matchesSearch && matchesLocation && matchesType;
      card.classList.toggle('hidden', !isVisible);
      if (isVisible) visibleCount += 1;
    });

    if (statusList) {
      statusList.classList.toggle('hidden', visibleCount === 0);
    }

    if (emptyState) {
      emptyState.classList.toggle('hidden', visibleCount > 0);
      if (visibleCount === 0) {
        const message = filtersApplied
          ? `No ${activeTab} reports match your filters yet.`
          : EMPTY_MESSAGES[activeTab] || 'No reports to show yet.';
        emptyState.textContent = message;
      }
    }
  }

  function setActiveTab(name) {
    activeTab = name;
    tabs.forEach(tab => {
      const isActive = tab.dataset.tab === name;
      tab.classList.toggle('active', isActive);
      tab.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
    refreshList();
  }

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.tab || 'verified';
      setActiveTab(target);
    });
  });

  let initialTab = activeTab;
  if (!hasCardsFor(initialTab)) {
    if (initialTab === 'verified' && hasCardsFor('rejected')) {
      initialTab = 'rejected';
    } else if (initialTab === 'rejected' && hasCardsFor('verified')) {
      initialTab = 'verified';
    }
  }

  setActiveTab(initialTab);

  if (applyBtn) {
    applyBtn.addEventListener('click', () => {
      applyBtn.classList.add('pressed');
      setTimeout(() => applyBtn.classList.remove('pressed'), 200);
      refreshList();
    });
  }

  if (searchInput) {
    searchInput.addEventListener('keydown', (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        refreshList();
      }
    });
  }
});