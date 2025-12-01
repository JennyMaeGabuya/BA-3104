// Notification Dropdown Handler
document.addEventListener('DOMContentLoaded', () => {
  const notifBtn = document.getElementById('notifBtn');
  const notificationDropdown = document.getElementById('notificationDropdown');
  const notifDot = document.getElementById('notifDot');

  // Toggle notification dropdown
  if (notifBtn && notificationDropdown) {
    notifBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isVisible = notificationDropdown.style.display === 'block';
      notificationDropdown.style.display = isVisible ? 'none' : 'block';
      
      // Hide notification dot when opening notifications
      if (!isVisible && notifDot) {
        notifDot.style.display = 'none';
      }
    });

    // Close notification dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.notification-container')) {
        notificationDropdown.style.display = 'none';
      }
    });
  }
});

// sample dataset (replace with real data / API)
const ITEMS = [
  {
    id: 1,
    title: "Black Backpack",
    category: "Bag",
    location: "Cafeteria",
    date: "2025-11-11",
    status: "Available",
    image: "https://images.unsplash.com/photo-1520975913949-2f6d60c3f0d6?q=80&w=1400&auto=format&fit=crop",
    description: "Black Nike backpack with laptop compartment. Contains some textbooks."
  },
  {
    id: 2,
    title: "iPhone 13 Pro",
    category: "Electronics",
    location: "Library - 2nd Floor",
    date: "2025-11-10",
    status: "Available",
    image: "https://images.unsplash.com/photo-1603791440384-56cd371ee9a7?q=80&w=1400&auto=format&fit=crop",
    description: "Blue iPhone 13 Pro with cracked screen protector. Found near the benches."
  },
  {
    id: 3,
    title: "Brown Leather Wallet",
    category: "Wallet",
    location: "Gymnasium",
    date: "2025-11-09",
    status: "Available",
    image: "https://images.unsplash.com/photo-1585238341976-1f8a1f3c6c3b?q=80&w=1400&auto=format&fit=crop",
    description: "Brown leather wallet with student ID inside. Owner can identify by providing ID number."
  },
  {
    id: 4,
    title: "Student ID Card",
    category: "ID",
    location: "Computer Laboratory",
    date: "2025-11-08",
    status: "Available",
    image: "https://images.unsplash.com/photo-1532012197267-da84d127e765?q=80&w=1400&auto=format&fit=crop",
    description: "Student ID card from CICS department. Name withheld for security."
  },
  {
    id: 5,
    title: "Red Umbrella",
    category: "Others",
    location: "Administration Building",
    date: "2025-11-08",
    status: "Available",
    image: "https://images.unsplash.com/photo-1522770179533-24471fcdba45?q=80&w=1400&auto=format&fit=crop",
    description: "Red folding umbrella found near the entrance."
  },
  {
    id: 6,
    title: "Wireless Earbuds",
    category: "Electronics",
    location: "Cafeteria",
    date: "2025-11-07",
    status: "Available",
    image: "https://images.unsplash.com/photo-1580894908360-8b62e4d7b8b6?q=80&w=1400&auto=format&fit=crop",
    description: "White wireless earbuds with charging case. Brand: Generic."
  }
];

// DOM refs
const cardsGrid = document.getElementById('cardsGrid');
const categorySelect = document.getElementById('categorySelect');
const locationSelect = document.getElementById('locationSelect');
const clearFiltersBtn = document.getElementById('clearFilters');
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');
const galleryCount = document.getElementById('gallery-count');

const claimModal = document.getElementById('claimModal');
const modalClose = document.getElementById('modalClose');

// current filters
let filters = {
  q: '',
  category: 'all',
  location: 'all'
};

// initialize app
function init(){
  populateFilterOptions();
  renderCards(ITEMS);
  attachEvents();
}

function populateFilterOptions(){
  const cats = Array.from(new Set(ITEMS.map(i => i.category))).sort();
  const locs = Array.from(new Set(ITEMS.map(i => i.location))).sort();

  for(const c of cats){
    const opt = document.createElement('option');
    opt.value = c;
    opt.textContent = c;
    categorySelect.appendChild(opt);
  }
  for(const l of locs){
    const opt = document.createElement('option');
    opt.value = l;
    opt.textContent = l;
    locationSelect.appendChild(opt);
  }
}

function renderCards(dataset){
  cardsGrid.innerHTML = '';
  if(!dataset.length){
    cardsGrid.innerHTML = '<p style="grid-column:1/-1;color:var(--muted)">No items found.</p>';
    galleryCount.textContent = `Showing 0 of ${ITEMS.length} items`;
    return;
  }
  galleryCount.textContent = `Showing ${dataset.length} of ${ITEMS.length} items`;

  for(const item of dataset){
    const card = document.createElement('article');
    card.className = 'card';
    card.innerHTML = `
      <img class="card-media" src="${item.image}" alt="${escapeHtml(item.title)}">
      <div class="card-body">
        <div class="card-title">
          <div>${escapeHtml(item.title)}</div>
          <div class="status-badge">${escapeHtml(item.status)}</div>
        </div>

        <div>
          <div class="tag">${escapeHtml(item.category)}</div>
        </div>

        <div class="card-desc">${escapeHtml(truncate(item.description, 160))}</div>

        <div class="meta-row">
          <div class="meta">📍 <span>${escapeHtml(item.location)}</span></div>
          <div class="meta">📅 <span>${escapeHtml(item.date)}</span></div>
        </div>

        <div class="card-cta">
          <button class="btn-claim" data-id="${item.id}">Claim This Item</button>
        </div>
      </div>
    `;
    cardsGrid.appendChild(card);
  }
  // attach claim click listeners
  document.querySelectorAll('.btn-claim').forEach(btn => btn.addEventListener('click', openClaimModal));
}

function applyFilters(){
  const q = filters.q.trim().toLowerCase();
  const category = filters.category;
  const location = filters.location;

  let out = ITEMS.filter(i => {
    // search name or description
    if(q){
      const hay = (i.title + ' ' + i.description + ' ' + i.category + ' ' + i.location).toLowerCase();
      if(!hay.includes(q)) return false;
    }
    if(category !== 'all' && i.category !== category) return false;
    if(location !== 'all' && i.location !== location) return false;
    return true;
  });

  renderCards(out);
}

// Event bindings
function attachEvents(){
  categorySelect.addEventListener('change', (e) => {
    filters.category = e.target.value;
    applyFilters();
  });

  locationSelect.addEventListener('change', (e) => {
    filters.location = e.target.value;
    applyFilters();
  });

  clearFiltersBtn.addEventListener('click', () => {
    filters = { q: '', category: 'all', location: 'all' };
    searchInput.value = '';
    categorySelect.value = 'all';
    locationSelect.value = 'all';
    applyFilters();
  });

  searchBtn.addEventListener('click', () => {
    filters.q = searchInput.value;
    applyFilters();
  });

  // allow Enter key in search input
  searchInput.addEventListener('keydown', (ev) => {
    if(ev.key === 'Enter'){
      ev.preventDefault();
      filters.q = searchInput.value;
      applyFilters();
    }
  });

  // modal close
  modalClose.addEventListener('click', closeModal);
  claimModal.addEventListener('click', (ev) => {
    if(ev.target === claimModal) closeModal();
  });
}

function openClaimModal(ev) {
  const id = ev.currentTarget.getAttribute("data-id");
  const item = ITEMS.find(x => String(x.id) === String(id));
  if (!item) return;

  const claimBox = document.getElementById("claimItemBox");

  // Fill item preview
  claimBox.innerHTML = `
    <div class="item-preview">
      <img src="${item.image}" class="preview-img">
      <div class="preview-info">
        <h4>${item.title}</h4>
        <div class="tag">${item.category}</div>
        <div class="meta">📍 ${item.location}</div>
        <div class="meta">📅 Found on ${item.date}</div>
      </div>
    </div>
  `;

  claimModal.setAttribute("aria-hidden", "false");
}

// close modal
function closeModal(){
  claimModal.setAttribute('aria-hidden', 'true');
}

// small utilities
function escapeHtml(s){
  if(!s) return '';
  return s.replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;');
}
function truncate(s, n){
  if(!s) return '';
  return s.length > n ? s.slice(0,n-1) + '…' : s;
}

// init
init();

document.getElementById("cancelClaim").addEventListener("click", closeModal);

document.getElementById("submitClaim").addEventListener("click", () => {
  const details = document.getElementById("claimDetails").value.trim();
  const contact = document.getElementById("claimContact").value.trim();

  if (!details || !contact) {
    alert("Please fill out all required fields before submitting.");
    return;
  }

  alert("Your claim request has been submitted! Admin will contact you soon.");
  closeModal();
});

// report-found.js
// Handles dropzone, preview, simple validation, and demo submit behavior.

document.addEventListener('DOMContentLoaded', () => {
  const dropzone = document.getElementById('dropzone');
  const fileInput = document.getElementById('fileInput');
  const preview = document.getElementById('preview');
  const form = document.getElementById('foundForm');
  const successPanel = document.getElementById('successPanel');
  const btnCancel = document.getElementById('btnCancel');

  // Prevent default drag behaviors
  ['dragenter','dragover','dragleave','drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, preventDefaults, false);
    document.body.addEventListener(eventName, preventDefaults, false);
  });

  function preventDefaults(e){ e.preventDefault(); e.stopPropagation(); }

  // Highlight
  ['dragenter','dragover'].forEach(ev => dropzone.addEventListener(ev, () => dropzone.classList.add('is-dragover')));
  ['dragleave','drop'].forEach(ev => dropzone.addEventListener(ev, () => dropzone.classList.remove('is-dragover')));

  // Handle dropped files
  dropzone.addEventListener('drop', handleDrop);
  fileInput.addEventListener('change', handleFiles);

  function handleDrop(e){
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles({ target: { files } });
  }

  function handleFiles(e){
    const files = e.target.files;
    if (!files || files.length === 0) return;
    const file = files[0];
    if (!file.type.startsWith('image/')) { alert('Please upload an image (PNG or JPG).'); return; }
    if (file.size > 10 * 1024 * 1024) { alert('File too large. Maximum 10MB.'); return; }
    previewFile(file);
  }

  function previewFile(file){
    preview.style.display = 'flex';
    preview.innerHTML = '';
    const img = document.createElement('img');
    img.alt = file.name;
    const reader = new FileReader();
    reader.onload = e => { img.src = e.target.result; };
    reader.readAsDataURL(file);
    preview.appendChild(img);

    // show remove button
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.textContent = 'Remove';
    removeBtn.style.marginLeft = '8px';
    removeBtn.className = 'btn btn-ghost';
    removeBtn.addEventListener('click', () => {
      preview.style.display = 'none';
      preview.innerHTML = '';
      fileInput.value = '';
    });
    preview.appendChild(removeBtn);
  }

  // Cancel button behavior: reset form
  btnCancel.addEventListener('click', (ev) => {
    ev.preventDefault();
    if (confirm('Clear the form?')) form.reset();
    preview.style.display = 'none';
    preview.innerHTML = '';
  });

  // Basic validation & submit
  // DISABLED: This was a demo handler that conflicts with report_form.js real submission
  // The actual form submission is now handled by report_form.js which sends data to submit_lost_report.php
  /*
  form.addEventListener('submit', (ev) => {
    ev.preventDefault();

    // Basic client-side required checks
    const requiredFields = [
      'itemName','category','description','locationFound','dateFound','pickupLocation','email','phone'
    ];
    const missing = requiredFields.filter(id => {
      const el = document.getElementById(id);
      return !el || (el.value || '').trim() === '';
    });

    if (missing.length) {
      const first = document.getElementById(missing[0]);
      first.focus();
      alert('Please fill all required fields.');
      return;
    }

    // Demo submit: show success panel, clear form
    successPanel.hidden = false;
    successPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Optionally animate or do server post here
    // simulate clearing for demo
    form.reset();
    preview.style.display = 'none';
    preview.innerHTML = '';

    // disable submit button briefly
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitted';
    setTimeout(() => {
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="margin-right:8px"><path d="M22 2L11 13"></path><path d="M22 2l-7 20 1-7 7-7-7-6z"/></svg> Submit Report';
    }, 1800);

  });
  */
});

// report-found.js
// Handles drag/drop upload, preview, simple validation, and simulated submit.

document.addEventListener('DOMContentLoaded', () => {
  const dropzone = document.getElementById('dropzone');
  const fileInput = document.getElementById('fileInput');
  const previewRow = document.getElementById('previewRow');
  const form = document.getElementById('foundForm');
  const successBox = document.getElementById('successBox');
  const btnCancel = document.getElementById('btnCancel');

  let currentFiles = [];

  // helper: create preview element
  function createPreview(file) {
    const url = URL.createObjectURL(file);
    const wrap = document.createElement('div');
    wrap.className = 'preview-thumb';

    const img = document.createElement('img');
    img.src = url;
    img.alt = file.name;

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'preview-remove';
    remove.title = 'Remove photo';
    remove.textContent = '✕';

    remove.addEventListener('click', () => {
      URL.revokeObjectURL(url);
      previewRow.removeChild(wrap);
      currentFiles = currentFiles.filter(f => f !== file);
    });

    wrap.appendChild(img);
    wrap.appendChild(remove);
    return wrap;
  }

  // open file dialog
  dropzone.addEventListener('click', () => fileInput.click());
  dropzone.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') fileInput.click(); });

  // file input change
  fileInput.addEventListener('change', (ev) => {
    handleFiles(ev.target.files);
    ev.target.value = '';
  });

  // drag/drop UX
  ['dragenter','dragover'].forEach(evt => {
    dropzone.addEventListener(evt, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropzone.style.borderColor = '#cfe4ff';
      dropzone.style.boxShadow = '0 8px 20px rgba(59,130,246,0.06)';
    });
  });

  ['dragleave','drop'].forEach(evt => {
    dropzone.addEventListener(evt, (e) => {
      e.preventDefault();
      e.stopPropagation();
      dropzone.style.borderColor = '';
      dropzone.style.boxShadow = '';
    });
  });

  dropzone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    if (dt && dt.files && dt.files.length) {
      handleFiles(dt.files);
    }
  });

  // handle multiple files (limit and size)
  function handleFiles(fileList) {
    const files = Array.from(fileList);
    files.forEach(file => {
      if (!file.type.startsWith('image/')) return;
      if (file.size > 10 * 1024 * 1024) { // 10MB
        alert(`"${file.name}" is too large. Max 10MB.`);
        return;
      }
      currentFiles.push(file);
      const preview = createPreview(file);
      previewRow.appendChild(preview);
    });
  }

  // Cancel functionality: reset form
  btnCancel.addEventListener('click', () => {
    if (!confirm('Clear form?')) return;
    form.reset();
    currentFiles = [];
    previewRow.innerHTML = '';
  });

  // Form submit: basic validation and simulated submit
  // DISABLED: Duplicate handler that conflicts with report_form.js
  // Real submission is handled by report_form.js
  /*
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    // basic required validation
    const requiredFields = [
      'itemName','category','description','locationFound','dateFound','pickupLocation','email','phone'
    ];
    const missing = requiredFields.filter(id => {
      const el = document.getElementById(id);
      return !el || !el.value;
    });

    if (missing.length) {
      const first = document.getElementById(missing[0]);
      first.focus();
      alert('Please complete all required fields before submitting.');
      return;
    }

    // rudimentary email + phone format check
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      alert('Please enter a valid email address.');
      return;
    }
    if (!/^\+?\d[\d\s-]{7,}$/.test(phone)) {
      alert('Please enter a valid phone number.');
      return;
    }

    // Normally here you would build FormData and send to backend.
    // We'll simulate success.
    form.style.display = 'none';
    successBox.hidden = false;
    successBox.scrollIntoView({behavior:'smooth'});
  });
  */

});
