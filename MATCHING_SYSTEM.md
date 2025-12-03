# BatStateU Lost & Found Matching System

## Overview
- Restricted to authenticated Batangas State University users (students, faculty, staff).
- Supports Lost/Found submissions, claim requests, and admin verification.
- Automatic matcher compares verified Lost reports with verified Found reports using weighted factors and exposes results on the admin "Manage Items" page.
- Public Found listing now shows limited, non-identifying data to protect item owners.

## Eligibility & Security
1. **Authentication**
   - `auth_check.php` and `student_auth.php` keep public routes locked behind university accounts.
   - Admin routes (`AdminDB/*`) use `ADMINSESSID` plus the `adminsessions` table for session validation.
2. **Report visibility**
   - Lost/Found submissions capture full description, location, serial/unique identifiers, optional image.
   - Public listing (`user_found.php`) now hides description and exact location; only category, status, and image remain visible.
3. **Admin scope**
   - Admin Manage Items page shows the complete record, automatic match badges, and claim requests.

## Matching Algorithm
Algorithm lives in [`matching_engine.php`](matching_engine.php) and exposes helpers for admin/automation.

| Factor | Weight | Notes |
|--------|--------|-------|
| Category | 30 pts | Exact category match required for scoring. |
| Location similarity | 25 pts | Exact, contains, or token-overlap scoring. |
| Date proximity | 20 pts | Same day → 20, ≤3 days → 16, ≤7 → 12, ≤14 → 8, ≤30 → 4. |
| Item name similarity | 15 pts | Exact, contains, or keyword overlap (max 60%). |
| Description keywords | 10 pts | Shared keywords after stop-word removal (2 pts per unique word, capped at 10). |
| Photo bonus | 5 pts | Awarded when both reports include images. |

- Scores ≥ 40% surface as matches with quality labels (Excellent/Good/Possible/Weak).
- `compute_verified_matches($pdo)` fetches verified Lost & Found items, evaluates each pair, and returns sorted matches plus per-report lookup tables.

## Manage Items Experience (`AdminDB/verified.php`)
- Displays **Verified Lost** and **Verified Found** sections with cards, thumbnails, metadata, and real-time Match Found badges.
- Clicking **View Match** opens a modal containing:
  - Score & quality label.
  - Lost vs Found details (IDs, names, locations).
  - Full reason stack explaining each factor.
- Claim requests table remains available for human review and action (`Mark as Claim`).

### Styling & Scripts
- New components styled in `AdminDB/admin.css` (`.match-section`, `.match-card`, `.match-modal`, etc.).
- Lightweight modal controller added inline at the bottom of `verified.php`.

## Claim Requests & Status
1. Students browse the restricted Found listing, pick an item, and submit a claim request with identifying details + school ID photo.
2. Admins review claim info alongside match suggestions.
3. Approving a claim via `update_claim_status.php` updates claim status and allows manual changes to report statuses (e.g., set Found report to `Claimed`).

## Testing & Verification
1. **Lint checks** (already run):
   - `php -l matching_engine.php`
   - `php -l AdminDB/verified.php`
   - `php -l user_found.php`
2. **Manual validation**
   - Seed Lost/Found reports with status `Verified` (and distinct categories/locations/dates) via phpMyAdmin or forms.
   - Visit `AdminDB/verified.php` (as admin) to see Match Found badges; open modal for reasons.
   - Confirm public `user_found.php` hides description/location.
   - Submit claim requests and ensure they appear in the Claim Requests table.
3. **Performance**
   - Optional indexes (recommended):
     ```sql
     ALTER TABLE found_reports
       ADD INDEX idx_category_found (category),
       ADD INDEX idx_date_found (date_found);

     ALTER TABLE lost_reports
       ADD INDEX idx_category (category),
       ADD INDEX idx_date_lost (date_lost);
     ```

## Next Steps
- Persist confirmed matches in a dedicated `report_matches` table for auditing and to exclude resolved pairs.
- Send notifications (email/SMS) when a high-confidence match appears or a claim changes status.
- Integrate background cron/queue job to refresh match suggestions periodically if the database grows large.
