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

  const inputs = {
    firstName: document.getElementById('firstName'),
    lastName: document.getElementById('lastName'),
    email: document.getElementById('email'),
    phone: document.getElementById('phone'),
  };

  const prefs = {
    emailFound: document.getElementById('prefEmailFound'),
    smsUpdates: document.getElementById('prefSmsUpdates'),
    weekly: document.getElementById('prefWeeklySummary'),
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

    const data = {
      profile,
      prefs: {
        emailFound: prefs.emailFound.checked,
        smsUpdates: prefs.smsUpdates.checked,
        weekly: prefs.weekly.checked,
      }
    };

    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
  }

  function showToast(msg = 'Saved', ms = 2200) {
    toastMessage.textContent = msg;
    toast.hidden = false;
    setTimeout(() => { toast.hidden = true; }, ms);
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

  // auto-save prefs when toggled
  Object.values(prefs).forEach(el => {
    el.addEventListener('change', () => {
      save();
      showToast('Preferences updated');
    });
  });

  // initial load
  load();
});

// tabs + filters behavior: safe init after DOM ready
document.addEventListener('DOMContentLoaded', () => {
  // tabs switching (if any tabs exist)
  document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      // for demo: keep empty state always; in real app you'd load approved/rejected lists
      showEmptyState();
    });
  });

  // Apply button demo (filters not wired to real data)
  const applyBtn = document.getElementById('applyBtn');
  if (applyBtn) {
    applyBtn.addEventListener('click', () => {
      // small animation to show button press
      applyBtn.classList.add('pressed');
      setTimeout(() => applyBtn.classList.remove('pressed'), 200);
      // demo: toggle empty vs list (keeps empty per screenshot)
      showEmptyState();
    });
  }
});

function showEmptyState() {
  const empty = document.getElementById('emptyState');
  const list = document.getElementById('listArea');
  if (empty) empty.classList.remove('hidden');
  if (list) list.classList.add('hidden');
}

// Approve/Reject behaviour (generic, works with any `.item-card` markup)
document.addEventListener('DOMContentLoaded', () => {
  const listRoot = document.getElementById('listArea') || document.getElementById('resultsInner');
  if (!listRoot) return;

  // ensure sub-containers for approved/rejected (non-destructive)
  let approvedContainer = listRoot.querySelector('.approved-list');
  let rejectedContainer = listRoot.querySelector('.rejected-list');
  if (!approvedContainer) {
    approvedContainer = document.createElement('div');
    approvedContainer.className = 'approved-list';
    listRoot.appendChild(approvedContainer);
  }
  if (!rejectedContainer) {
    rejectedContainer = document.createElement('div');
    rejectedContainer.className = 'rejected-list hidden';
    listRoot.appendChild(rejectedContainer);
  }

  // Event delegation for approve/reject buttons
  listRoot.addEventListener('click', (ev) => {
    const btn = ev.target.closest('[data-action="approve"], [data-action="reject"]');
    if (!btn) return;
    const card = btn.closest('.item-card');
    if (!card) return;

    const action = btn.dataset.action;
    if (action === 'approve') {
      card.dataset.state = 'approved';
      card.classList.remove('rejected');
      card.classList.add('approved');
      approvedContainer.appendChild(card);
    } else if (action === 'reject') {
      card.dataset.state = 'rejected';
      card.classList.remove('approved');
      card.classList.add('rejected');
      rejectedContainer.appendChild(card);
    }

    // show list area and hide empty state when there is at least one card
    const empty = document.getElementById('emptyState');
    if (empty) empty.classList.add('hidden');
    const listArea = document.getElementById('listArea');
    if (listArea) listArea.classList.remove('hidden');
  });
});