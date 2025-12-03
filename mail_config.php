<?php
/**
 * Production mail configuration for FindIt SMTP notifications.
 *
 * WARNING: This file contains sensitive credentials. Keep it out of source control
 * (e.g., add mail_config.php to .gitignore) and restrict file permissions.
 */

putenv('FINDIT_SMTP_HOST=smtp.gmail.com');
putenv('FINDIT_SMTP_PORT=587');
putenv('FINDIT_SMTP_SECURE=tls');
putenv('FINDIT_SMTP_USER=jerishannemalibiransarmiento@gmail.com');
putenv('FINDIT_SMTP_PASS=mrecawfhvkaopirl');
putenv('FINDIT_MAIL_FROM=FindIt Admin <jerishannemalibiransarmiento@gmail.com>');
putenv('FINDIT_MAIL_REPLY=FindIt Helpdesk <jerishannemalibiransarmiento@gmail.com>');
putenv('FINDIT_ADMIN_EMAILS=jerishannemalibiransarmiento@gmail.com');