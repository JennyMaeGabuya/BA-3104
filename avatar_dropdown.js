/**
 * Profile Avatar Dropdown Menu Controller
 * Handles opening/closing dropdown and outside click detection
 */

(function() {
  'use strict';

  // Get DOM elements
  const avatar = document.getElementById('profileAvatar');
  const dropdown = document.getElementById('profileDropdown');

  if (!avatar || !dropdown) {
    return; // Component not found, exit gracefully
  }

  /**
   * Toggle dropdown visibility
   */
  function toggleDropdown() {
    const isOpen = dropdown.getAttribute('aria-hidden') === 'false';
    
    if (isOpen) {
      closeDropdown();
    } else {
      openDropdown();
    }
  }

  /**
   * Open dropdown menu
   */
  function openDropdown() {
    dropdown.setAttribute('aria-hidden', 'false');
    avatar.setAttribute('aria-expanded', 'true');
  }

  /**
   * Close dropdown menu
   */
  function closeDropdown() {
    dropdown.setAttribute('aria-hidden', 'true');
    avatar.setAttribute('aria-expanded', 'false');
  }

  /**
   * Check if click is outside the dropdown component
   * @param {Event} event - Click event
   * @returns {boolean} - True if click is outside
   */
  function isClickOutside(event) {
    const container = avatar.closest('.profile-avatar-container');
    if (!container) return true;
    
    return !container.contains(event.target);
  }

  // Event Listeners

  // Click on avatar to toggle dropdown
  avatar.addEventListener('click', function(event) {
    event.stopPropagation();
    toggleDropdown();
  });

  // Keyboard support: Enter and Space keys
  avatar.addEventListener('keydown', function(event) {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      toggleDropdown();
    }
    
    // Escape key to close
    if (event.key === 'Escape') {
      closeDropdown();
    }
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', function(event) {
    if (dropdown.getAttribute('aria-hidden') === 'false' && isClickOutside(event)) {
      closeDropdown();
    }
  });

  // Close dropdown on Escape key
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && dropdown.getAttribute('aria-hidden') === 'false') {
      closeDropdown();
    }
  });

  // Close dropdown when clicking on a dropdown item (optional - allows navigation)
  const dropdownItems = dropdown.querySelectorAll('.dropdown-item');
  dropdownItems.forEach(item => {
    item.addEventListener('click', function(event) {
      // Allow navigation to proceed, but close dropdown after a short delay
      // This ensures the click event completes before closing
      setTimeout(() => {
        closeDropdown();
      }, 100);
    });
  });

})();

