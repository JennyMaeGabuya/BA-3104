# FindIt — PHP + MySQL backend (local)

This workspace contains simple PHP pages and a minimal MySQL schema to support registration and login for the FindIt project.

Files added:

- `db_config.php` — PDO configuration file (update DB credentials as needed).
- `register.php` — Registration page and handler (POST) that inserts users into the `users` table.
- `login.php` — Login page and handler (POST) that authenticates a user and starts a session.
- `sql/schema.sql` — SQL script to create the `findit` database and `users` table.

Quick setup (Windows, XAMPP or similar):

1. Install XAMPP or another Apache + PHP + MySQL stack.
2. Start Apache and MySQL services.
3. Import the SQL schema using phpMyAdmin or the mysql CLI:

```powershell
mysql -u root -p < "C:\Users\Ralph Aranda\OneDrive\Desktop\SIA.html\sql\schema.sql"
```

4. Update database credentials in `db_config.php` if different from defaults (`root` / empty password).
5. Place this project folder inside your webserver document root (e.g., `C:\xampp\htdocs\findit`) or configure a VirtualHost.
6. Visit `http://localhost/findit/register.php` and `http://localhost/findit/login.php`.

Security notes:
- This is a minimal example for local development. For production, add CSRF protection, input sanitization, rate-limiting, secure session settings, and use environment variables for secrets.
