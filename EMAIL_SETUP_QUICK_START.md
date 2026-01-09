# Quick Start: Email Setup for Password Reset

## Current Setup (XAMPP/Development)

**The system is now configured to save emails to files** - perfect for development!

### How It Works Now:

1. When a user requests a password reset, the email is **saved to a file** instead of being sent
2. Files are saved in: `email_logs/` folder (created automatically)
3. Each email is saved as an HTML file with timestamp and recipient email

### To Test Password Reset:

1. Go to `forgot_password.php`
2. Enter a registered email address
3. Click "Send Reset Link"
4. Check the `email_logs/` folder in your project root
5. Open the HTML file - it contains the full email with the reset link
6. Copy the reset link from the email file
7. Paste it in your browser to reset the password

### Example Email File Location:
```
C:\xampp\htdocs\AI_AgriLinkPH\email_logs\
2025-01-08_14-30-45_user@example.com.html
```

## For Production (Sending Real Emails)

### Option 1: Use SMTP (Recommended)

1. Edit `email_config.php`
2. Comment out the file method (lines 38-39)
3. Uncomment and configure SMTP settings (lines 20-31)
4. Install PHPMailer: `composer require phpmailer/phpmailer`

**Gmail Example:**
```php
define('EMAIL_METHOD', 'smtp');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password'); // Get from Google Account
define('SMTP_SECURE', 'tls');
```

### Option 2: Configure PHP mail() (Not Recommended for XAMPP)

Requires configuring a mail server, which is complex on Windows/XAMPP.

## Current Configuration

✅ **EMAIL_METHOD**: `file` (saves to email_logs/ folder)
✅ **No mail server needed**
✅ **Perfect for development/testing**

## Troubleshooting

**Q: Where are my emails?**
A: Check the `email_logs/` folder in your project root directory.

**Q: How do I get the reset link?**
A: Open the HTML file in `email_logs/` folder and copy the link from the email.

**Q: Can I send real emails?**
A: Yes! Configure SMTP in `email_config.php` (see Option 1 above).

**Q: The email_logs folder doesn't exist?**
A: It will be created automatically when the first email is saved.

