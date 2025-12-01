# 🔴 Complete Guide: Why PHP Sessions Don't Work in .html Files

## 1. Why PHP Session Variables Do NOT Work Inside .html Files

### The Problem Explained Simply:

**Web servers treat files differently based on their extension:**

- **`.html` files** → Server sends them **as-is** (static HTML text)
- **`.php` files** → Server **processes** them through PHP interpreter first

### What Happens When You Access a File:

#### Scenario A: Accessing `user_found.html`
```
Browser Request → Web Server → Reads file → Sends HTML directly → Browser displays
```

**Result:** Any PHP code like `<?php session_start(); ?>` is sent as **plain text** to the browser. The browser sees it as text, not code!

#### Scenario B: Accessing `user_found.php`
```
Browser Request → Web Server → PHP Interpreter processes file → Executes PHP code → Sends HTML → Browser displays
```

**Result:** PHP code is **executed**, sessions work, variables are available!

### Why `$_SESSION` Cannot Be Accessed in .html Files:

1. **No PHP Processing:** The server never runs the PHP interpreter on `.html` files
2. **No Session Start:** `session_start()` never executes, so no session is created
3. **No Variable Access:** `$_SESSION['user_name']` doesn't exist because PHP never ran

### Why Your Avatar Initials Don't Load:

- `avatar_component.php` contains PHP code (`<?php ... ?>`)
- When included in `.html` file, the PHP code is **not executed**
- The server treats it as plain text
- Result: Avatar shows "?" or nothing

---

## 2. How to Properly Convert .html Pages to PHP

### Step-by-Step Conversion Process:

#### Step 1: Rename Files
```
user_found.html  →  user_found.php
user_report.html →  user_report.php
user_about.html  →  user_about.php
```

#### Step 2: Add PHP Session Code at the Top

**BEFORE (.html file):**
```html
<!doctype html>
<html lang="en">
<head>
```

**AFTER (.php file):**
```php
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
```

#### Step 3: Replace Static Avatar with PHP Component

**BEFORE:**
```html
<div class="profile-avatar">JD</div>
```

**AFTER:**
```php
<?php include 'avatar_component.php'; ?>
```

#### Step 4: Update Navigation Links

Change all `.html` references to `.php`:
```php
<a href="user_found.php">Found Items</a>
<a href="user_report.php">Report Lost Item</a>
<a href="user_about.php">About</a>
```

---

## 3. Correct Folder Structure

### Current Structure (After Conversion):
```
SIA.html/
├── login.php
├── logout.php
├── student_auth.php
├── db_config.php
├── avatar_component.php          ← Shared component
├── avatar_dropdown.js            ← Shared JavaScript
├── user_home.php                 ← ✅ Already converted
├── user_found.php                ← ✅ Converted
├── user_report.php               ← ✅ Converted
├── user_about.php                ← ✅ Converted
├── user_home.css                 ← Shared CSS (paths unchanged)
├── user_script.js                ← Shared JavaScript
└── ... (other files)
```

### Why CSS/JS Paths Still Work:

**Relative paths don't change when you rename files!**

- `user_found.html` uses: `<link rel="stylesheet" href="user_home.css">`
- `user_found.php` uses: `<link rel="stylesheet" href="user_home.css">`
- **Same path!** ✅

The browser requests `user_home.css` from the same directory, regardless of whether the requesting file is `.html` or `.php`.

---

## 4. Complete Working Code Examples

### Example: `user_found.php` (Complete Fixed Page)

```php
<?php
session_start();

// Authentication check - redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>FindIt@BatStateU — Found Items</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="user_home.css">
</head>
<body>

  <!-- Header -->
  <header class="site-header">
    <div class="header-inner">
      <!-- Logo section -->
      <div class="brand">
        <!-- ... logo code ... -->
      </div>

      <!-- Navigation + Avatar -->
      <div class="header-right">
        <nav class="nav">
          <a href="user_home.php">Home</a>
          <a href="user_report.php">Report Lost Item</a>
          <a href="user_found.php" class="active">Found Items</a>
          <a href="user_about.php">About</a>
        </nav>

        <!-- Dynamic Avatar Component -->
        <?php include 'avatar_component.php'; ?>
      </div>
    </div>
  </header>

  <!-- Rest of page content -->
  <!-- ... -->

  <!-- Include dropdown JavaScript -->
  <script src="avatar_dropdown.js"></script>
  <script src="user_script.js"></script>
</body>
</html>
```

### Key Components Explained:

#### 1. Session Start
```php
<?php
session_start();
```
- **Must be first line** (before any HTML output)
- Starts/resumes PHP session
- Makes `$_SESSION` available

#### 2. Authentication Check
```php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
```
- Checks if user is logged in
- Redirects to login if not authenticated
- `exit` prevents further code execution

#### 3. Get Session Data
```php
$fullname = $_SESSION['fullname'] ?? $_SESSION['user_name'] ?? '';
$initials = getUserInitials($fullname);
```
- Retrieves user's full name from session
- Supports both `fullname` and `user_name` session keys
- Generates initials automatically

#### 4. Include Avatar Component
```php
<?php include 'avatar_component.php'; ?>
```
- Loads the dynamic avatar with dropdown
- Automatically uses session data
- Shows "?" if no user logged in

---

## 5. Best Practices

### ✅ Using a Shared Header Template

**Create `header.php`:**
```php
<?php
// header.php - Shared header component
if (!isset($_SESSION)) {
    session_start();
}

// Get current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="site-header">
  <div class="header-inner">
    <!-- Logo -->
    <div class="brand">
      <!-- ... -->
    </div>

    <!-- Navigation -->
    <div class="header-right">
      <nav class="nav">
        <a href="user_home.php" <?= $current_page === 'user_home.php' ? 'class="active"' : '' ?>>Home</a>
        <a href="user_report.php" <?= $current_page === 'user_report.php' ? 'class="active"' : '' ?>>Report</a>
        <a href="user_found.php" <?= $current_page === 'user_found.php' ? 'class="active"' : '' ?>>Found Items</a>
        <a href="user_about.php" <?= $current_page === 'user_about.php' ? 'class="active"' : '' ?>>About</a>
      </nav>
      
      <?php include 'avatar_component.php'; ?>
    </div>
  </div>
</header>
```

**Use in pages:**
```php
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include 'header.php';
?>
```

### ✅ Avoid Repeating PHP Code

**Create `auth_check.php`:**
```php
<?php
// auth_check.php - Reusable authentication check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
```

**Use in every page:**
```php
<?php
require_once 'auth_check.php';
?>
<!doctype html>
<!-- rest of page -->
```

### ✅ Keep HTML/CSS Layout Unchanged

- **CSS files stay the same** (no changes needed)
- **JavaScript files stay the same** (no changes needed)
- **Only add PHP at the top** of HTML files
- **Rename `.html` to `.php`**
- **Replace static content with PHP includes**

---

## Summary Checklist

- [x] Rename `.html` files to `.php`
- [x] Add `session_start()` at the top
- [x] Add authentication check
- [x] Replace static avatar with `<?php include 'avatar_component.php'; ?>`
- [x] Update all navigation links to `.php`
- [x] Include `avatar_dropdown.js` script
- [x] Test that CSS/JS still load correctly
- [x] Verify session works on all pages

---

## Quick Reference

| File Type | PHP Execution | Session Support | Avatar Works |
|-----------|--------------|-----------------|--------------|
| `.html`    | ❌ No        | ❌ No           | ❌ No        |
| `.php`     | ✅ Yes       | ✅ Yes          | ✅ Yes       |

**Solution: Convert all `.html` files to `.php`!**

