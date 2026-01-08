# AI-AgriLinkPH Installation Guide

## Features Added

1. **Full Responsiveness** - Mobile, tablet, and desktop optimized
2. **Excel Import Support** - CSV and Excel (.xlsx, .xls) file import with validation
3. **PDF Export** - Export dashboard reports as PDF
4. **Session Management** - Login, logout, session timeout, and protected pages

## Installation Steps

### 1. Basic Setup (No Additional Libraries Required)

The system works out of the box with basic CSV support. For Excel and advanced PDF features:

### 2. Optional: Install PhpSpreadsheet for Excel Support

```bash
composer require phpoffice/phpspreadsheet
```

Or download manually from: https://github.com/PHPOffice/PhpSpreadsheet

### 3. Optional: Install TCPDF for Advanced PDF Generation

```bash
composer require tecnickcom/tcpdf
```

Or download manually from: https://github.com/tecnickcom/TCPDF

**Note:** The system will work without these libraries, but with limited functionality:

- Without PhpSpreadsheet: Only CSV files supported
- Without TCPDF: PDF export will generate HTML that can be printed as PDF

## Default Login Credentials

- **Username:** `admin` | **Password:** `admin123`
- **Username:** `user` | **Password:** `user123`

## File Structure

```
AI_AgriLinkPH/
├── includes/
│   ├── session.php          # Session management
│   ├── functions.php        # Core functions (CSV/Excel parsing, PDF generation)
│   ├── header.php           # Header template
│   └── footer.php           # Footer template
├── assets/
│   ├── css/
│   │   └── style.css        # Responsive styles
│   └── js/
│       └── chart.min.js      # Chart.js library
├── uploads/                 # Uploaded files directory
├── index.php               # Main upload page
├── login.php               # Login page
├── logout.php              # Logout handler
├── dashboard.php           # Dashboard (protected)
└── export_pdf.php          # PDF export handler (protected)
```

## Session Configuration

Session timeout is set to **1 hour (3600 seconds)**. You can modify this in `includes/session.php`:

```php
define('SESSION_TIMEOUT', 3600); // Change this value
```

## Usage

1. **Access the system:** Navigate to `index.php`
2. **Login:** Use default credentials to access dashboard
3. **Upload file:** Upload CSV or Excel file
4. **View dashboard:** Explore insights and visualizations
5. **Export PDF:** Click "Export PDF" button to download report
6. **Logout:** Click logout button in header

## Security Features

- ✅ Session-based authentication
- ✅ Protected dashboard pages (cannot access directly)
- ✅ Session timeout (auto-logout after inactivity)
- ✅ File upload validation
- ✅ Secure file handling

## Browser Compatibility

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Excel files not working?

- Install PhpSpreadsheet library via Composer
- Ensure PHP has necessary extensions (zip, xml)

### PDF export not working?

- Install TCPDF library for advanced PDF generation
- System will fallback to HTML print version

### Session issues?

- Check PHP session configuration in `php.ini`
- Ensure `uploads/` directory has write permissions

## Support

For issues or questions, refer to the code comments in each file.
