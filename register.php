<?php
session_start();
require_once __DIR__ . '/db_config.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $user_type = $_POST['user_type'] ?? 'Student';
    $student_id = trim($_POST['student_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$first_name) $errors[] = 'First name is required.';
    if (!$last_name) $errors[] = 'Last name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Valid email is required.';
    } else {
      // Allow only official BatStateU student email addresses
      if (!preg_match('/@g\.batstate-u\.edu\.ph$/i', $email)) {
        $errors[] = 'Please register with your BatStateU student email ending with @g.batstate-u.edu.ph.';
      }
    }
    // Validate phone: exactly 11 digits, numbers only
    if (!preg_match('/^\d{11}$/', $phone)) {
      $errors[] = 'Phone number must be exactly 11 digits (numbers only), e.g. 09551234567.';
    }

    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
      // Check duplicate email
      $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
        $errors[] = 'An account with that email already exists.';
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
          $insert = $pdo->prepare('INSERT INTO users (first_name, last_name, user_type, student_id, department, email, phone, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
          $insert->execute([$first_name, $last_name, $user_type, $student_id, $department, $email, $phone, $hash]);
          $success = 'Account created successfully. You may now sign in.';
        } catch (PDOException $e) {
          // Show detailed DB error for local debugging. Remove or log in production.
          $errors[] = 'Database error: ' . $e->getMessage();
          // Optionally log to file
          @file_put_contents(__DIR__ . '/logs/db_errors.log', date('[Y-m-d H:i:s] ') . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
      }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Account | FindIt@BatStateU</title>
  <link rel="stylesheet" href="/BA-3104/register.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

  <section class="left">
  <div class="logo">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
      <path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
      <path d="M12 3v18"></path>
    </svg>
  </div>

  <h1>Join FindIt@BatStateU</h1>
  <h2>Create your account today</h2>
  <p>
    Register now to report lost items, browse found items, and get notified when your belongings are recovered.
  </p>
</section>

  <!-- RIGHT SIDE -->
  <section class="right">
    <div class="register-card">
      <h2>Create Account</h2>
      <p>Fill in your details to register</p>

      <?php if ($errors): ?>
        <div class="errors">
          <ul>
            <?php foreach ($errors as $err): ?>
              <li><?=htmlspecialchars($err)?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="success"><?=htmlspecialchars($success)?></div>
      <?php endif; ?>

      <form method="post" action="register.php">
        <div class="input-row">
          <input name="first_name" type="text" placeholder="First Name" required value="<?=htmlspecialchars($_POST['first_name'] ?? '')?>">
          <input name="last_name" type="text" placeholder="Last Name" required value="<?=htmlspecialchars($_POST['last_name'] ?? '')?>">
        </div>

        <select name="user_type" required>
          <option value="" disabled <?= empty($_POST['user_type']) ? 'selected' : '' ?>>Select user type</option>
          <option value="Student" <?= (($_POST['user_type'] ?? '') === 'Student') ? 'selected' : '' ?>>Student</option>
          <option value="Faculty" <?= (($_POST['user_type'] ?? '') === 'Faculty') ? 'selected' : '' ?>>Faculty</option>
          <option value="Staff" <?= (($_POST['user_type'] ?? '') === 'Staff') ? 'selected' : '' ?>>Staff</option>
          <option value="Admin" <?= (($_POST['user_type'] ?? '') === 'Admin') ? 'selected' : '' ?>>Admin</option>
        </select>

        <input name="student_id" type="text" placeholder="Admin/Employee/Student ID" required value="<?=htmlspecialchars($_POST['student_id'] ?? '')?>">
        <input name="department" type="text" placeholder="Department / Office" required value="<?=htmlspecialchars($_POST['department'] ?? '')?>">
        <input name="email" type="email" placeholder="Your.email@g.batstate-u.edu.ph" required pattern="^[A-Za-z0-9._%+-]+@g\.batstate-u\.edu\.ph$" title="Use your BatStateU email (example: your.name@g.batstate-u.edu.ph)" value="<?=htmlspecialchars($_POST['email'] ?? '')?>">
        <input name="phone" type="tel" placeholder="09551234567" required pattern="^[0-9]{11}$" title="Enter 11 digits, numbers only (example: 09551234567)" inputmode="numeric" maxlength="11" value="<?=htmlspecialchars($_POST['phone'] ?? '')?>">
        <input name="password" type="password" placeholder="Password" required>
        <input name="confirm_password" type="password" placeholder="Confirm Password" required>

        <label class="checkbox-label">
          <input type="checkbox" required name="agree">
          I agree to the Terms of Service and Privacy Policy.
        </label>

        <button type="submit" class="btn-primary">Create Account</button>

        <div class="links">
          Already have an account? <a href="login.php">Sign in here</a>
        </div>
        <div class="back-link">
          ← <a href="sia.html">Back to Home</a>
        </div>
      </form>
    </div>
  </section>

</body>
</html>
