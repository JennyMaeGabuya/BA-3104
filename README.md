# FindIt @ BatStateU — Lost & Found Management System

This is the lost and found portal for BatStateU. Students and staff can report lost or found items, track progress, and get notified. Admins review reports, verify matches, and handle claims.

## What it does
- **Users**: file lost/found reports with photos, see statuses, get notifications, claim matched items.
- **Admins**: approve/reject/verify reports, manage matches/claims, update statuses.
- **Matching**: `match_results` stores claim↔found matches with confidence and classification.
- **Notifications**: same feed on dashboard and home; relative times use Asia/Manila.
- **Theming**: light/dark; status chips keep colors (Pending=orange, Verified=green, Rejected=red, Claimed=violet, Found=blue).

## Stack
- PHP (XAMPP/Apache), MySQL (InnoDB, utf8mb4)
- HTML/CSS/JS (custom), sessions for auth

## Setup (macOS/XAMPP)
1. Start MySQL in XAMPP.
2. Create DB and tables:
   ```sh
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS findit;"
   mysql -u root -p findit < sql/schema.sql
   ```
3. Set DB creds in `db_config.php`.
4. Start Apache & MySQL, open `http://localhost/BA-3104/`.

_No seed data included—add via the app._

## Key files
- `sql/schema.sql` — schema (includes `match_results` and FKs)
- `db_config.php` — DB config
- `Dashboard/` — user pages (`user_home.php`, notifications dropdown)
- `AdminDB/` — admin pages and styles
- `matching_service.php` — claim-to-found matching
- `notification_helpers.php` — fetch + relative time formatting

## Typical flows
- User: log in → submit lost/found → track → get notifications → claim match.
- Admin: review pending → verify/approve/reject → manage matches/claims → update status.

## Troubleshooting
- Empty data: import `sql/schema.sql`, then add via the app.
- CSS not updating: hard refresh (Shift+Reload).
- Uploads failing: ensure the uploads folder (e.g., `Dashboard/Image`) is writable.
