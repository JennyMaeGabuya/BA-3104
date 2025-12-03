<?php
/**
 * Example mail configuration for FindIt SMTP notifications.
 *
 * 1. Copy this file to mail_config.php (do NOT commit secrets).
 * 2. Replace the placeholder values with your SMTP credentials (e.g., Gmail app password).
 * 3. Include mail_config.php before mail_notifications.php or set these env vars globally.
 */

putenv('FINDIT_SMTP_HOST=smtp.gmail.com');
putenv('FINDIT_SMTP_PORT=587');
putenv('FINDIT_SMTP_SECURE=tls');
putenv('FINDIT_SMTP_USER=lhitasarmiento153@gmail.com');
putenv('FINDIT_SMTP_PASS=mrecawfhvkaopirl');
putenv('FINDIT_MAIL_FROM=FindIt Admin <lhitasarmiento153@gmail.com>');
putenv('FINDIT_MAIL_REPLY=FindIt Helpdesk <lhitasarmiento153@gmail.com>');

// Optional: notify admins via this list (comma separated) when crafting custom email flows.
putenv('FINDIT_ADMIN_EMAILS=admin1@batstateu.edu,admin2@batstateu.edu');
