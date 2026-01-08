# Password Reset Setup Guide

This guide explains how to set up the password reset functionality via email for AI-AgriLink PH.

## Features

- ✅ Secure token-based password reset
- ✅ Email notifications with reset links
- ✅ Token expiration (1 hour)
- ✅ One-time use tokens
- ✅ Multiple email sending methods supported

## Database Setup

The system automatically creates a `password_reset_tokens` table when you first use the password reset feature. No manual setup required!

## Email Configuration

You have three options for sending emails:

### Option 1: PHP mail() Function (Default - Development)

This uses PHP's built-in `mail()` function. Works if your server has mail configured.

**Configuration:** Already set as default in `email_config.php`

**Pros:**
- No additional setup required
- Works on most servers

**Cons:**
- Emails may go to spam
- Requires server mail configuration
- Less reliable

### Option 2: SMTP (Recommended for Production)

Use SMTP for reliable email delivery. Supports Gmail, Outlook, and other SMTP servers.

**Setup Steps:**

1. Install PHPMailer (if using Composer):
   ```bash
   composer require phpmailer/phpmailer
   ```

2. Edit `email_config.php`:
   - Uncomment the SMTP configuration section (lines 20-31)
   - Update the SMTP settings:
     ```php
     define('EMAIL_METHOD', 'smtp');
     define('SMTP_HOST', 'smtp.gmail.com');        // Your SMTP server
     define('SMTP_PORT', 587);                     // 587 for TLS, 465 for SSL
     define('SMTP_USERNAME', 'your-email@gmail.com');
     define('SMTP_PASSWORD', 'your-app-password'); // Use App Password for Gmail
     define('SMTP_SECURE', 'tls');                 // 'tls' or 'ssl'
     define('SMTP_FROM_EMAIL', 'noreply@agrilink.ph');
     define('SMTP_FROM_NAME', 'AI-AgriLink PH');
     ```

**Gmail Setup:**
1. Enable 2-Step Verification on your Google account
2. Generate an App Password: https://myaccount.google.com/apppasswords
3. Use the App Password (not your regular password) in `SMTP_PASSWORD`

**Outlook/Hotmail Setup:**
- SMTP_HOST: `smtp-mail.outlook.com`
- SMTP_PORT: `587`
- SMTP_SECURE: `tls`
- Use your Outlook email and password

### Option 3: Save to File (Testing/Development)

For testing without sending actual emails, save emails to files.

**Setup:**

1. Edit `email_config.php`:
   ```php
   define('EMAIL_METHOD', 'file');
   define('EMAIL_SAVE_PATH', __DIR__ . '/../email_logs/');
   ```

2. The system will create an `email_logs` folder and save all emails there as HTML files.

**Pros:**
- Perfect for development/testing
- No email server needed
- Can preview emails before sending

**Cons:**
- Not for production use
- Emails are not actually sent

## How It Works

1. **User requests password reset:**
   - User enters email on `forgot_password.php`
   - System generates a secure 64-character token
   - Token is stored in database with 1-hour expiration
   - Email is sent with reset link

2. **User clicks reset link:**
   - Link goes to `reset_password.php?token=XXXXX`
   - System verifies token is valid and not expired
   - User enters new password

3. **Password is reset:**
   - New password is hashed and saved
   - Token is marked as used (cannot be reused)
   - User can now login with new password

## Security Features

- ✅ Tokens expire after 1 hour
- ✅ Tokens can only be used once
- ✅ Secure random token generation (64 characters)
- ✅ Password hashing with bcrypt
- ✅ No email enumeration (same message shown whether email exists or not)

## Testing

1. **Test with file method first:**
   - Set `EMAIL_METHOD` to `'file'`
   - Request password reset
   - Check `email_logs/` folder for the email
   - Verify the reset link works

2. **Test with SMTP:**
   - Configure SMTP settings
   - Request password reset
   - Check your email inbox
   - Click the reset link and test password change

## Troubleshooting

### Emails not sending (PHP mail):
- Check server mail configuration
- Check spam folder
- Consider using SMTP instead

### SMTP connection failed:
- Verify SMTP credentials
- Check firewall settings
- For Gmail: Use App Password, not regular password
- Check if port 587/465 is blocked

### Token not working:
- Check if token expired (1 hour limit)
- Verify token wasn't already used
- Check database connection

### Reset link not working:
- Verify `reset_password.php` exists
- Check URL structure in email
- Ensure HTTPS is configured if using secure links

## Files Modified

- `includes/session.php` - Added password reset functions
- `email_config.php` - Email sending configuration
- `forgot_password.php` - Request reset page
- `reset_password.php` - Reset password page
- Database: `password_reset_tokens` table (auto-created)

## Support

For issues or questions, check:
- Server error logs
- Email logs (if using file method)
- Database for token records
- PHP error logs


