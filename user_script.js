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

// dataset populated by PHP
const ITEMS = Array.isArray(window.FOUND_ITEMS)
  ? window.FOUND_ITEMS.map((item, idx) => ({
      id: item.report_id || idx,
      title: item.item_name || 'Found Item',
      category: item.category || 'Others',
      location: item.location_found || 'Unknown Location',
      date: item.date_found || '—',
      status: item.status || 'Verified',
      image: item.photo_url || '',
      description: item.description || '',
      time: item.time_found || ''
    }))
  : [];

// DOM refs
const cardsGrid = document.getElementById('cardsGrid');
const categorySelect = document.getElementById('categorySelect');
const locationSelect = document.getElementById('locationSelect');
const clearFiltersBtn = document.getElementById('clearFilters');
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');
const galleryCount = document.getElementById('gallery-count');
const idUploadInput = document.getElementById('claimIdUpload');
const lastSeenInput = document.getElementById('claimLastSeen');
const idUploadFeedback = document.getElementById('idUploadFeedback');
const defaultIdMessage = 'Upload a clear BatStateU ID photo (portrait with the red banner).';
let idUploadIsValid = false;

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
  setupIdUploadValidation();
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
  const total = ITEMS.length;
  if(!dataset.length){
    cardsGrid.innerHTML = '<div class="empty-state">No verified found items yet.</div>';
    galleryCount.textContent = `Showing 0 of ${total} items`;
    return;
  }
  galleryCount.textContent = `Showing ${dataset.length} of ${total} items`;

  const fragment = document.createDocumentFragment();
  for(const item of dataset){
    const statusText = statusLabel(item.status);
    const statusClass = statusClassName(item.status);
    const card = document.createElement('article');
    card.className = 'found-card';
    card.innerHTML = `
      <div class="found-thumb">
        <img src="${item.image}" alt="${escapeHtml(item.title)}">
      </div>
      <div class="found-body">
        <div class="found-title-row">
          <h3 class="found-title">${escapeHtml(item.title)}</h3>
          <span class="status-pill ${statusClass}">${statusText}</span>
        </div>
        <div class="category-chip">${escapeHtml(item.category)}</div>
        <p class="found-desc">Description hidden for safety. Contact admin if this is your item.</p>
        <div class="found-meta">
          <div class="found-meta-item">📍 <span>Exact location withheld</span></div>
          <div class="found-meta-item">📅 <span>${escapeHtml(item.date)}</span></div>
        </div>
        <button class="btn-claim" data-id="${item.id}">Claim This Item</button>
      </div>
    `;
    fragment.appendChild(card);
  }
  cardsGrid.appendChild(fragment);
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

function setupIdUploadValidation(){
  if(!idUploadInput) return;
  setIdUploadFeedback(defaultIdMessage, 'note');
  idUploadInput.addEventListener('change', handleIdUploadChange);
}

async function handleIdUploadChange(ev){
  idUploadIsValid = false;
  const file = ev.target.files && ev.target.files[0];
  if(!file){
    setIdUploadFeedback(defaultIdMessage, 'note');
    return;
  }
  if(!file.type.startsWith('image/')){
    setIdUploadFeedback('Please upload an image file (JPG or PNG).', 'error');
    ev.target.value = '';
    return;
  }
  if(file.size > 10 * 1024 * 1024){
    setIdUploadFeedback('File is too large. Maximum allowed size is 10MB.', 'error');
    ev.target.value = '';
    return;
  }

  setIdUploadFeedback('Validating ID photo…', 'note');
  try {
    const analysis = await analyzeIdImage(file);
    if(!analysis.isPortrait){
      setIdUploadFeedback('Photo must be portrait orientation like the sample BatStateU ID.', 'error');
      return;
    }
    if(!analysis.hasRedHeader){
      setIdUploadFeedback('Top area must include the BatStateU red header. Please retake the photo.', 'error');
      return;
    }
    idUploadIsValid = true;
    setIdUploadFeedback('ID photo looks good!', 'success');
  } catch (err) {
    console.error(err);
    setIdUploadFeedback('Unable to read the image. Please try another photo.', 'error');
  }
}

function openClaimModal(ev) {
  const id = ev.currentTarget.getAttribute("data-id");
  const item = ITEMS.find(x => String(x.id) === String(id));
  if (!item) return;

  const claimBox = document.getElementById("claimItemBox");
  claimModal.dataset.reportId = item.id;

  claimBox.innerHTML = `
    <div class="item-preview">
      <img src="${item.image}" class="preview-img" alt="${escapeHtml(item.title)}">
      <div class="preview-info">
        <h4>${escapeHtml(item.title)}</h4>
        <div class="tag">${escapeHtml(item.category)}</div>
        <div class="meta">📍 Exact location shared after verification</div>
        <div class="meta">📅 Found on ${escapeHtml(item.date)}</div>
      </div>
    </div>
  `;

  claimModal.setAttribute("aria-hidden", "false");
}

// close modal
function closeModal(){
  claimModal.setAttribute('aria-hidden', 'true');
  resetClaimForm();
}

function resetClaimForm(){
  const details = document.getElementById('claimDetails');
  const contact = document.getElementById('claimContact');
  const upload = document.getElementById('claimIdUpload');
  const lastSeen = document.getElementById('claimLastSeen');
  if(details) details.value = '';
  if(contact) contact.value = '';
  if(upload) upload.value = '';
  if(lastSeen) lastSeen.value = '';
  idUploadIsValid = false;
  setIdUploadFeedback(defaultIdMessage, 'note');
  delete claimModal.dataset.reportId;
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

function setIdUploadFeedback(message, state){
  if(!idUploadFeedback) return;
  idUploadFeedback.textContent = message;
  idUploadFeedback.classList.remove('helper-error','helper-success','helper-note');
  switch(state){
    case 'error':
      idUploadFeedback.classList.add('helper-error');
      break;
    case 'success':
      idUploadFeedback.classList.add('helper-success');
      break;
    default:
      idUploadFeedback.classList.add('helper-note');
  }
}

function analyzeIdImage(file){
  return new Promise((resolve, reject) => {
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
      try {
        const isPortrait = img.height >= img.width * 1.2;
        const canvas = document.createElement('canvas');
        const targetWidth = 220;
        const targetHeight = Math.max(220, Math.round(targetWidth * (img.height / img.width)));
        canvas.width = targetWidth;
        canvas.height = targetHeight;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.drawImage(img, 0, 0, targetWidth, targetHeight);
        const sampleHeight = Math.max(12, Math.floor(targetHeight * 0.18));
        const data = ctx.getImageData(0, 0, targetWidth, sampleHeight).data;
        let r = 0, g = 0, b = 0;
        const totalPixels = sampleHeight * targetWidth;
        for(let i = 0; i < data.length; i += 4){
          r += data[i];
          g += data[i + 1];
          b += data[i + 2];
        }
        r /= totalPixels;
        g /= totalPixels;
        b /= totalPixels;
        const hasRedHeader = r > 150 && (r - g) > 25 && (r - b) > 25;
        resolve({ isPortrait, hasRedHeader });
      } catch (err) {
        reject(err);
      } finally {
        URL.revokeObjectURL(url);
      }
    };
    img.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error('Unable to load image'));
    };
    img.src = url;
  });
}

function statusLabel(status){
  switch((status || '').toLowerCase()){
    case 'claimed': return 'Claimed';
    case 'rejected': return 'Rejected';
    case 'pending': return 'Pending Review';
    default: return 'Available';
  }
}

function statusClassName(status){
  switch((status || '').toLowerCase()){
    case 'claimed': return 'status-pill--claimed';
    case 'rejected': return 'status-pill--rejected';
    case 'pending': return 'status-pill--pending';
    default: return 'status-pill--available';
  }
}

// init
init();

document.getElementById("cancelClaim").addEventListener("click", closeModal);

document.getElementById("submitClaim").addEventListener("click", async () => {
  const detailsEl = document.getElementById("claimDetails");
  const contactEl = document.getElementById("claimContact");
  const lastSeenEl = document.getElementById('claimLastSeen');
  const details = detailsEl.value.trim();
  const contact = contactEl.value.trim();
  const lastSeen = lastSeenEl ? lastSeenEl.value.trim() : '';
  const idFile = idUploadInput && idUploadInput.files ? idUploadInput.files[0] : null;

  if (!details || !contact || !lastSeen) {
    alert("Please fill out all required fields before submitting.");
    if(!details) detailsEl.focus();
    else if(!lastSeen) lastSeenEl.focus();
    else contactEl.focus();
    return;
  }

  if (!idFile) {
    setIdUploadFeedback('Student ID upload is required to submit a claim.', 'error');
    if (idUploadInput) idUploadInput.focus();
    return;
  }

  if (!idUploadIsValid) {
    setIdUploadFeedback('Please upload a BatStateU ID that matches the template before submitting.', 'error');
    if (idUploadInput) idUploadInput.focus();
    return;
  }

  const activeBtn = document.getElementById('submitClaim');
  activeBtn.disabled = true;
  activeBtn.textContent = 'Submitting…';

  try {
    const data = new FormData();
    data.append('report_id', claimModal.dataset.reportId || '');
    data.append('details', details);
    data.append('contact', contact);
    data.append('last_seen_location', lastSeen);
    data.append('school_id', idFile);

    const response = await fetch('submit_claim_request.php', {
      method: 'POST',
      body: data
    });
    const payload = await response.json();

    if (!payload.success) {
      throw new Error(payload.error || 'Failed to submit claim request.');
    }

    alert('Your claim request has been submitted! Admin will contact you soon. Reference: ' + (payload.requestCode || '—'));
    closeModal();
  } catch (err) {
    alert(err.message || 'Something went wrong while submitting your claim request.');
  } finally {
    activeBtn.disabled = false;
    activeBtn.textContent = 'Submit Claim Request';
  }
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
