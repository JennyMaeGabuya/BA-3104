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

// Modal open
function openClaimModal(ev){
  const id = ev.currentTarget.getAttribute('data-id');
  const item = ITEMS.find(x => String(x.id) === String(id));
  if(!item) return;
  document.getElementById('modalTitle').textContent = `Claim: ${item.title}`;
  document.getElementById('modalBody').textContent = `To claim "${item.title}", please login or contact the Lost & Found Office. Location: ${item.location}.`;
  claimModal.setAttribute('aria-hidden', 'false');
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
