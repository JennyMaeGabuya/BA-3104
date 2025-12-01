<?php
/**
 * Dynamic User Profile Avatar Component
 * Extracts user initials from session and displays avatar with dropdown menu
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Extract user initials from full name
 * @param string $fullname Full name (e.g., "Ralph Adrian Dizon")
 * @return string Initials (e.g., "RD")
 */
function getUserInitials($fullname) {
    if (empty($fullname)) {
        return '?';
    }
    
    // Trim and split by spaces
    $parts = array_filter(explode(' ', trim($fullname)));
    
    if (empty($parts)) {
        return '?';
    }
    
    // Get first letter of first name
    $firstInitial = strtoupper(substr($parts[0], 0, 1));
    
    // Get first letter of last name (last element in array)
    $lastInitial = strtoupper(substr(end($parts), 0, 1));
    
    return $firstInitial . $lastInitial;
}

// Get user's full name from session (support both 'fullname' and 'user_name')
$fullname = $_SESSION['fullname'] ?? $_SESSION['user_name'] ?? '';
$initials = getUserInitials($fullname);

// Detect if we're on a Dashboard page
$isDashboardPage = strpos($_SERVER['REQUEST_URI'], '/Dashboard/') !== false;
?>

<!-- Profile Avatar Container -->
<div class="profile-avatar-container">
  <div class="profile-avatar" id="profileAvatar" role="button" aria-label="User profile menu" aria-expanded="false" tabindex="0">
    <?php echo htmlspecialchars($initials); ?>
  </div>
  
  <!-- Dropdown Menu -->
  <div class="profile-dropdown" id="profileDropdown" role="menu" aria-hidden="true">
    <div class="dropdown-header">
      <div class="dropdown-name"><?php echo htmlspecialchars($fullname ?: 'Guest User'); ?></div>
    </div>
    
    <div class="dropdown-divider"></div>
    
    <?php if ($isDashboardPage): ?>
    <button class="dropdown-item" id="themeToggle" role="menuitem">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="theme-icon">
        <circle cx="12" cy="12" r="5"></circle>
        <line x1="12" y1="1" x2="12" y2="3"></line>
        <line x1="12" y1="21" x2="12" y2="23"></line>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
        <line x1="1" y1="12" x2="3" y2="12"></line>
        <line x1="21" y1="12" x2="23" y2="12"></line>
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
      </svg>
      <span id="themeLabel">Dark Mode</span>
    </button>
    <?php endif; ?>
    
    <a href="/SIA.html/Dashboard/settings.php" class="dropdown-item" role="menuitem">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"></circle>
        <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"></path>
      </svg>
      <span>Settings</span>
    </a>
    
    <a href="/SIA.html/logout.php" class="dropdown-item dropdown-item-logout" role="menuitem">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
        <polyline points="16 17 21 12 16 7"></polyline>
        <line x1="21" y1="12" x2="9" y2="12"></line>
      </svg>
      <span>Logout</span>
    </a>
  </div>
</div>

