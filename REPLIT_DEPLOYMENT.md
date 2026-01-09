# Replit Deployment Guide for AI-AgriLinkPH

## Overview
This guide helps you deploy your PHP application to Replit and fix common deployment issues.

## Common Issues & Solutions

### 1. System Not Opening in Browser

**Problem:** The application doesn't load when you click "Run" in Replit.

**Solution:**
- The `.replit` file is configured to run `php -S 0.0.0.0:8000 -t .`
- Make sure you're using the webview panel in Replit (not just the console)
- The entry point is set to `index.php`, but users should access `login.php` first
- **Fix:** Update your `.replit` file (already done) and ensure the webview shows the correct URL

### 2. File Upload Not Working

**Problem:** CSV/XLSX files cannot be uploaded or processed.

**Root Causes:**
1. **PHP Upload Limits:** Replit may have different `upload_max_filesize` and `post_max_size` settings
2. **Directory Permissions:** The `uploads/` directory may not be writable
3. **File Path Issues:** Absolute paths may not work correctly

**Solutions Applied:**
- ✅ Fixed `dashboard.php` to properly create and check `uploads/` directory
- ✅ Added error handling for file upload failures
- ✅ Improved directory permission checks

**Additional Steps:**
1. Check PHP upload limits in Replit:
   ```php
   <?php
   echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
   echo "post_max_size: " . ini_get('post_max_size') . "\n";
   ?>
   ```

2. If limits are too low, create a `.user.ini` file in the root:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 12M
   ```

### 3. Registration Not Working

**Problem:** Account creation fails silently or shows errors.

**Root Causes:**
1. **Database Connection:** Replit uses different database credentials
2. **Table Creation:** Database tables may not be created automatically
3. **Error Handling:** Errors may be hidden

**Solutions Applied:**
- ✅ Updated `includes/db_config.php` to support environment variables
- ✅ Improved error handling in database connection
- ✅ Better error messages for debugging

**Setup Steps:**
1. **Add Database in Replit:**
   - Go to Replit → Tools → Database
   - Create a new MySQL/MariaDB database
   - Note the connection details

2. **Set Environment Variables (Replit Secrets):**
   - Go to Replit → Secrets (lock icon)
   - Add these secrets:
     ```
     DB_HOST = your-db-host
     DB_USER = your-db-user
     DB_PASS = your-db-password
     DB_NAME = your-db-name
     ```

3. **Test Database Connection:**
   - The system will automatically create tables on first run
   - Check `includes/session.php` - it calls `init_users_table()` and `init_password_reset_tokens_table()`

### 4. Layout/CSS Breaking

**Problem:** Pages look different or broken after deployment.

**Root Causes:**
1. **Asset Paths:** Relative paths may break depending on URL structure
2. **Tailwind CDN:** Should work, but may have loading issues
3. **Background Images:** Image paths may be incorrect

**Solutions Applied:**
- ✅ Fixed asset paths in `includes/header.php` to use dynamic paths
- ✅ Fixed background image paths in `login.php`, `index.php`, `forgot_password.php`
- ✅ All paths now use PHP to determine correct relative path

**How It Works:**
```php
// Instead of: 'assets/css/style.css'
// Now uses: 
<?php echo (strpos($_SERVER['PHP_SELF'], '/') === 0 ? '' : './') . 'assets/css/style.css'; ?>
```

This ensures paths work whether accessed from root (`/`) or subdirectory.

## Step-by-Step Deployment

### Step 1: Upload Files to Replit
1. Import your project into Replit (GitHub import or file upload)
2. Ensure all files are in the root directory

### Step 2: Configure Database
1. Create a database in Replit (Tools → Database)
2. Set environment variables in Replit Secrets:
   - `DB_HOST`
   - `DB_USER`
   - `DB_PASS`
   - `DB_NAME`

### Step 3: Set PHP Configuration
Create `.user.ini` in the root directory:
```ini
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 300
memory_limit = 128M
```

### Step 4: Create Required Directories
The system will create these automatically, but you can create them manually:
- `uploads/` - For uploaded CSV/Excel files
- `sessions/` - For PHP sessions (if needed)
- `email_logs/` - For email logs (if using file method)

### Step 5: Test the Application
1. Click "Run" in Replit
2. Access `login.php` in the webview
3. Try creating an account
4. Try uploading a CSV file
5. Check for any errors in the console

## Troubleshooting

### Database Connection Errors
**Error:** "Connection failed: ..."

**Fix:**
1. Verify environment variables are set correctly in Replit Secrets
2. Check database is running in Replit
3. Verify database credentials are correct
4. Check `includes/db_config.php` is using environment variables

### File Upload Errors
**Error:** "We could not save the uploaded file"

**Fix:**
1. Check `uploads/` directory exists and is writable
2. Verify PHP upload limits (see above)
3. Check file size is under 10MB
4. Look at error logs in Replit console

### Session Errors
**Error:** Sessions not persisting

**Fix:**
1. Check `sessions/` directory is writable
2. Verify session configuration in `includes/session.php`
3. Check Replit allows session storage

### CSS/Assets Not Loading
**Error:** Styles not applying, images not showing

**Fix:**
1. Check browser console for 404 errors
2. Verify asset paths are correct (check `includes/header.php`)
3. Ensure Tailwind CDN is accessible
4. Check network tab in browser dev tools

## Files Modified for Replit Compatibility

1. **`.replit`** - Replit configuration file
2. **`replit.nix`** - Nix package configuration
3. **`includes/db_config.php`** - Environment variable support
4. **`includes/session.php`** - Session path configuration
5. **`includes/header.php`** - Dynamic asset paths
6. **`login.php`** - Dynamic image paths
7. **`index.php`** - Dynamic image paths
8. **`forgot_password.php`** - Dynamic image paths
9. **`dashboard.php`** - Improved upload handling

## Testing Checklist

- [ ] Application loads in Replit webview
- [ ] Login page displays correctly
- [ ] Registration form works
- [ ] User can create account
- [ ] User can log in
- [ ] File upload form displays
- [ ] CSV file can be uploaded
- [ ] Excel file can be uploaded
- [ ] Dashboard displays after upload
- [ ] Charts render correctly
- [ ] Logout works
- [ ] Password reset works (if configured)

## Additional Notes

- **Email Configuration:** For password reset, the system uses file-based email saving by default (see `email_config.php`)
- **Database:** Tables are created automatically on first run
- **Sessions:** Sessions are stored in `sessions/` directory if writable
- **Error Logging:** Check Replit console for PHP errors

## Support

If you encounter issues not covered here:
1. Check Replit console for PHP errors
2. Enable error display in PHP (add to top of file):
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
3. Check browser console for JavaScript errors
4. Verify all environment variables are set correctly

