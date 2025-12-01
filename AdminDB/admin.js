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

  // Example: dynamically update metrics from a fake API (simulate)
  setTimeout(() => {
    // these would normally come from API
    document.getElementById('activeReports').textContent = 6;
    document.getElementById('verifiedItems').textContent = 6;
    document.getElementById('resolvedCases').textContent = 0;
    document.getElementById('pendingVerification').textContent = 4;
  }, 200);
});

function changeTab(tabName) {
    document.querySelectorAll(".tab-btn").forEach(btn =>
        btn.classList.remove("active")
    );

    document.querySelector(`[data-tab="${tabName}"]`).classList.add("active");

    document.querySelectorAll(".tab-content").forEach(tab =>
        tab.classList.remove("active")
    );

    document.getElementById(tabName).classList.add("active");
}

document.querySelectorAll(".tab-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        changeTab(btn.dataset.tab);
    });
});

// Handle Approve / Reject
document.addEventListener("click", (e) => {

    if (e.target.classList.contains("approve-btn")) {
        let card = e.target.closest(".item-card");
        moveItem(card, "approved");
    }

    if (e.target.classList.contains("reject-btn")) {
        let card = e.target.closest(".item-card");
        moveItem(card, "rejected");
    }
});

function moveItem(card, target) {
    let targetSection = document.getElementById(target);

    let newCard = card.cloneNode(true);

    // Remove action buttons after moving
    let actions = newCard.querySelector(".action-buttons");
    if (actions) actions.remove();

    // Add status label
    let label = document.createElement("p");
    label.style.fontWeight = "bold";
    label.style.color = target === "approved" ? "#27ae60" : "#c0392b";
    label.textContent = target === "approved" ? "Approved" : "Rejected";
    newCard.querySelector(".info").appendChild(label);

    targetSection.appendChild(newCard);

    // Remove original from Pending
    card.remove();
}

// script.js - Settings form behaviour (demo only)
// Uses the HTML structure in index.html

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('settingsForm');
  const notifBtn = document.getElementById('notifBtn');

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
