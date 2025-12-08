<?php ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body class="login-body">

  <div class="login-card">
    <h2>Admin Sign In</h2>

    <?php if (!empty($_GET['err'])): ?>
      <div class="error-msg">Invalid username or password</div>
    <?php endif; ?>

    <form method="post" action="src/loginAction.php">
      <label>Username
        <input name="username" type="text" required autocomplete="username">
      </label>

      <label>Password
        <input name="password" type="password" required autocomplete="current-password">
      </label>

      <button type="submit">Login</button>
    </form>

    <div class="back-link-container">
      <a href="index.php" class="back-btn"> Back </a>
    </div>
  </div>

  <script src="js/script.js"></script>
</body>
</html>
