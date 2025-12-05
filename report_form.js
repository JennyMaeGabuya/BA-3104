/**
 * Report Form Handler
 * Handles file upload, preview, form validation, and submission
 * for both report_found.php and user_report.php
 */

document.addEventListener('DOMContentLoaded', () => {
  const dropzone = document.getElementById('dropzone');
  const fileInput = document.getElementById('fileInput');
  const previewRow = document.getElementById('previewRow');
  const form = document.getElementById('foundForm');
  const successBox = document.getElementById('successBox');
  const btnCancel = document.getElementById('btnCancel');
  const dateInput = document.getElementById('dateFound');
  const timeInput = document.getElementById('timeFound');

  // Check if elements exist (for pages that don't have the form)
  if (!dropzone || !fileInput || !previewRow || !form) {
    return;
  }

  function getPhilippinesNow() {
    return new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
  }

  function pad(value) {
    return String(value).padStart(2, '0');
  }

  function formatDate(value) {
    return `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}`;
  }

  function formatTime(value) {
    return `${pad(value.getHours())}:${pad(value.getMinutes())}`;
  }

  function updateDateConstraints() {
    if (!dateInput) {
      return;
    }

    const today = formatDate(getPhilippinesNow());
    dateInput.max = today;

    if (dateInput.value && dateInput.value > today) {
      dateInput.value = today;
    }
  }

  function getSelectedDateValue() {
    return dateInput ? dateInput.value : '';
  }

  function getSelectedTimeValue() {
    return timeInput ? timeInput.value : '';
  }

  function parseSelectedDateTime() {
    const dateValue = getSelectedDateValue();
    const timeValue = getSelectedTimeValue() || '00:00';
    if (!dateValue) {
      return null;
    }
    const dateTimeString = `${dateValue}T${timeValue}:00`;
    const parsed = new Date(dateTimeString);
    return Number.isFinite(parsed.getTime()) ? parsed : null;
  }

  function isFutureDateTime() {
    const selected = parseSelectedDateTime();
    if (!selected) {
      return false;
    }
    return selected.getTime() > getPhilippinesNow().getTime();
  }

  function clampTimeToNow() {
    if (!timeInput || !dateInput) {
      return;
    }
    const now = getPhilippinesNow();
    const today = formatDate(now);
    const currentTime = formatTime(now);
    if (dateInput.value === today && timeInput.value && timeInput.value > currentTime) {
      timeInput.value = currentTime;
    }
  }

  function updateTimeConstraints() {
    if (!timeInput) {
      return;
    }

    const now = getPhilippinesNow();
    const today = formatDate(now);
    const currentTime = formatTime(now);

    if (dateInput && dateInput.value === today) {
      timeInput.max = currentTime;
      clampTimeToNow();
    } else {
      timeInput.removeAttribute('max');
    }
  }

  function refreshDateTimeConstraints() {
    updateDateConstraints();
    updateTimeConstraints();
  }

  if (dateInput) {
    dateInput.addEventListener('input', () => {
      if (dateInput.value && dateInput.max && dateInput.value > dateInput.max) {
        dateInput.value = dateInput.max;
      }
      updateTimeConstraints();
    });
  }

  if (timeInput && dateInput) {
    timeInput.addEventListener('input', clampTimeToNow);
    timeInput.addEventListener('change', clampTimeToNow);
  }

  if (dateInput || timeInput) {
    refreshDateTimeConstraints();
    setInterval(refreshDateTimeConstraints, 60000);
  }

  let currentFile = null;
  let originalDropzoneHTML = dropzone.innerHTML;

  // Helper: Show preview inside dropzone
  function showPreviewInDropzone(file) {
    const url = URL.createObjectURL(file);
    
    // Clear dropzone and show preview
    dropzone.innerHTML = '';
    dropzone.style.padding = '0';
    dropzone.style.border = 'none';
    dropzone.style.background = 'transparent';
    
    const previewContainer = document.createElement('div');
    previewContainer.className = 'preview-container';
    previewContainer.style.position = 'relative';
    previewContainer.style.width = '100%';
    previewContainer.style.borderRadius = '12px';
    previewContainer.style.overflow = 'hidden';
    previewContainer.style.cursor = 'pointer';
    
    const img = document.createElement('img');
    img.src = url;
    img.alt = file.name;
    img.style.width = '100%';
    img.style.height = 'auto';
    img.style.display = 'block';
    img.style.maxHeight = '400px';
    img.style.objectFit = 'contain';
    img.style.backgroundColor = '#f9fafb';

    const changeButton = document.createElement('button');
    changeButton.type = 'button';
    changeButton.className = 'change-photo-btn';
    changeButton.innerHTML = 'Click to change photo';
    changeButton.style.position = 'absolute';
    changeButton.style.bottom = '20px';
    changeButton.style.left = '50%';
    changeButton.style.transform = 'translateX(-50%)';
    changeButton.style.background = 'rgba(255, 255, 255, 0.95)';
    changeButton.style.color = '#c8102e';
    changeButton.style.border = 'none';
    changeButton.style.padding = '10px 24px';
    changeButton.style.borderRadius = '8px';
    changeButton.style.cursor = 'pointer';
    changeButton.style.fontWeight = '600';
    changeButton.style.fontSize = '14px';
    changeButton.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
    changeButton.style.transition = 'all 0.2s ease';

    changeButton.addEventListener('mouseenter', () => {
      changeButton.style.background = '#c8102e';
      changeButton.style.color = 'white';
      changeButton.style.transform = 'translateX(-50%) scale(1.05)';
    });

    changeButton.addEventListener('mouseleave', () => {
      changeButton.style.background = 'rgba(255, 255, 255, 0.95)';
      changeButton.style.color = '#c8102e';
      changeButton.style.transform = 'translateX(-50%) scale(1)';
    });

    // Make entire container clickable
    previewContainer.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      fileInput.click();
    });

    previewContainer.appendChild(img);
    previewContainer.appendChild(changeButton);
    dropzone.appendChild(previewContainer);
  }

  // Helper: Reset dropzone to original state
  function resetDropzone() {
    if (currentFile) {
      URL.revokeObjectURL(URL.createObjectURL(currentFile));
    }
    currentFile = null;
    dropzone.innerHTML = originalDropzoneHTML;
    dropzone.style.padding = '';
    dropzone.style.border = '';
    dropzone.style.background = '';
    
    // Re-get the file input after resetting HTML
    const newFileInput = document.getElementById('fileInput');
    if (newFileInput) {
      newFileInput.value = '';
      newFileInput.addEventListener('change', handleFileInputChange);
    }
  }

  // File input change handler
  function handleFileInputChange(ev) {
    if (ev.target.files && ev.target.files.length > 0) {
      handleFiles(ev.target.files);
    }
  }

  fileInput.addEventListener('change', handleFileInputChange);

  // Open file dialog when clicking dropzone
  dropzone.addEventListener('click', (e) => {
    // Only trigger if no file is uploaded yet and not clicking on file input directly
    if (!currentFile && e.target.id !== 'fileInput') {
      e.preventDefault();
      e.stopPropagation();
      const input = document.getElementById('fileInput');
      if (input) {
        input.click();
      }
    }
  });

  // Keyboard accessibility
  dropzone.addEventListener('keydown', (e) => { 
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      const input = document.getElementById('fileInput');
      if (input) {
        input.click();
      }
    }
  });

  // File input change handler
  function handleFileInputChange(ev) {
    if (ev.target.files && ev.target.files.length > 0) {
      handleFiles(ev.target.files);
    }
  }

  fileInput.addEventListener('change', handleFileInputChange);

  // Drag and drop visual feedback
  ['dragenter', 'dragover'].forEach(evt => {
    dropzone.addEventListener(evt, (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (!currentFile) {
        dropzone.style.borderColor = '#3b82f6';
        dropzone.style.backgroundColor = '#eff6ff';
      }
    });
  });

  ['dragleave', 'drop'].forEach(evt => {
    dropzone.addEventListener(evt, (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (!currentFile) {
        dropzone.style.borderColor = '';
        dropzone.style.backgroundColor = '';
      }
    });
  });

  // Handle dropped files
  dropzone.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    if (dt && dt.files && dt.files.length) {
      handleFiles(dt.files);
    }
  });

  // Process and validate files
  function handleFiles(fileList) {
    const file = fileList[0]; // Only take the first file
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
      alert(`"${file.name}" is not an image file. Please upload PNG or JPG files only.`);
      return;
    }
    
    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
      alert(`"${file.name}" is too large. Maximum file size is 10MB.`);
      return;
    }
    
    // Store current file and show preview
    currentFile = file;
    showPreviewInDropzone(file);
  }

  // Cancel button: reset form
  if (btnCancel) {
    btnCancel.addEventListener('click', (e) => {
      e.preventDefault();
      if (!confirm('Are you sure you want to clear the form?')) return;
      
      form.reset();
      resetDropzone();
    });
  }

  // Form validation and submission
  form.addEventListener('submit', (e) => {
    e.preventDefault();

    // Define required fields
    const requiredFields = [
      'itemName',
      'category',
      'description',
      'locationFound',
      'dateFound',
      'email',
      'phone'
    ];

    // Add pickupLocation for report_found.php (check if it exists)
    const pickupLocation = document.getElementById('pickupLocation');
    if (pickupLocation) {
      requiredFields.push('pickupLocation');
    }

    // Check for missing required fields
    const missing = requiredFields.filter(id => {
      const el = document.getElementById(id);
      return !el || (el.value || '').trim() === '';
    });

    if (missing.length) {
      const first = document.getElementById(missing[0]);
      if (first) {
        first.focus();
        first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
      alert('Please fill in all required fields before submitting.');
      return;
    }

    // Validate email format
    const email = document.getElementById('email').value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      document.getElementById('email').focus();
      alert('Please enter a valid email address.');
      return;
    }

    // Validate phone format
    const phone = document.getElementById('phone').value.trim();
    const phoneRegex = /^\+?\d[\d\s-]{7,}$/;
    if (!phoneRegex.test(phone)) {
      document.getElementById('phone').focus();
      alert('Please enter a valid phone number.');
      return;
    }

    if (isFutureDateTime()) {
      if (timeInput && dateInput) {
        alert('Please select a time and date that are not in the future.');
        if (timeInput.value && dateInput.value) {
          timeInput.focus();
        } else {
          dateInput.focus();
        }
        return;
      }
    }

    // All validation passed - prepare form data
    const formData = new FormData(form);
    
    // Add uploaded file to form data
    if (currentFile) {
      formData.append('photo', currentFile);
    }

    // Submit to server via AJAX
    submitForm(formData);
  });

  // Submit form to backend
  function submitForm(formData) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalHTML = submitBtn.innerHTML;
    
    // Disable button and show loading state
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    // Choose endpoint based on explicit data attribute to avoid heuristics
    const reportType = (form.getAttribute('data-report-type') || '').toLowerCase();
    const endpoint = reportType === 'found' ? '/BA-3104/submit_found_report.php' : '/BA-3104/submit_lost_report.php';

    // Send to backend via fetch API
    fetch(endpoint, {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Hide form and show success message
        form.style.display = 'none';
        
        if (successBox) {
          successBox.hidden = false;
          successBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Clean up and reset form
        resetDropzone();
        form.reset();

        // Redirect to My Reports after 2 seconds
        setTimeout(() => {
          window.location.href = 'Dashboard/my_report.php';
        }, 2000);
      } else {
        // Show error message (support both 'error' and 'message' keys)
        alert('Error: ' + (data.error || data.message || 'Failed to submit report'));
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHTML;
      }
    })
    .catch(error => {
      console.error('Submission error:', error);
      alert('An error occurred while submitting your report. Please try again.');
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalHTML;
    });
  }
});
