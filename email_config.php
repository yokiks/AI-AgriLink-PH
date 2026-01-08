<?php
/**
 * Email Configuration for Password Reset
 * 
 * IMPORTANT: PHP's mail() function requires a mail server to be configured.
 * For development/testing, you have several options:
 */

// =============================================================================
// OPTION 1: Use PHP mail() (Default - requires mail server configuration)
// =============================================================================
define('EMAIL_METHOD', 'php_mail');

// =============================================================================
// OPTION 2: Use SMTP (Recommended for production)
// =============================================================================
// To use SMTP, uncomment below and install PHPMailer:
// composer require phpmailer/phpmailer

/*
define('EMAIL_METHOD', 'smtp');

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');        // For Gmail
define('SMTP_PORT', 587);                     // 587 for TLS, 465 for SSL
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password'); // Use App Password for Gmail
define('SMTP_SECURE', 'tls');                 // 'tls' or 'ssl'
define('SMTP_FROM_EMAIL', 'noreply@agrilink.ph');
define('SMTP_FROM_NAME', 'AI-AgriLink PH');
*/

// =============================================================================
// OPTION 3: Save to file (For development/testing ONLY)
// =============================================================================
// Uncomment this to save emails to files instead of sending them
// define('EMAIL_METHOD', 'file');
// define('EMAIL_SAVE_PATH', __DIR__ . '/../email_logs/');

// =============================================================================
// EMAIL SETTINGS
// =============================================================================
define('EMAIL_FROM', 'noreply@agrilink.ph');
define('EMAIL_FROM_NAME', 'AI-AgriLink PH');

/**
 * Send email function with multiple method support
 */
function send_email($to, $subject, $html_message) {
    $method = defined('EMAIL_METHOD') ? EMAIL_METHOD : 'php_mail';
    
    switch ($method) {
        case 'smtp':
            return send_email_smtp($to, $subject, $html_message);
        
        case 'file':
            return save_email_to_file($to, $subject, $html_message);
        
        case 'php_mail':
        default:
            return send_email_php_mail($to, $subject, $html_message);
    }
}

/**
 * Send email using PHP's mail() function
 */
function send_email_php_mail($to, $subject, $html_message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">" . "\r\n";
    
    return mail($to, $subject, $html_message, $headers);
}

/**
 * Send email using SMTP (requires PHPMailer)
 */
function send_email_smtp($to, $subject, $html_message) {
    // Check if PHPMailer is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer not found. Install it with: composer require phpmailer/phpmailer");
        return false;
    }
    
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email send failed: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Save email to file (for development/testing)
 */
function save_email_to_file($to, $subject, $html_message) {
    $save_path = defined('EMAIL_SAVE_PATH') ? EMAIL_SAVE_PATH : __DIR__ . '/../email_logs/';
    
    // Create directory if it doesn't exist
    if (!file_exists($save_path)) {
        mkdir($save_path, 0777, true);
    }
    
    $filename = $save_path . date('Y-m-d_H-i-s') . '_' . sanitize_filename($to) . '.html';
    
    $content = "To: {$to}\n";
    $content .= "Subject: {$subject}\n";
    $content .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $content .= "---\n\n";
    $content .= $html_message;
    
    $result = file_put_contents($filename, $content);
    
    if ($result !== false) {
        error_log("Email saved to: {$filename}");
        return true;
    }
    
    return false;
}

/**
 * Sanitize filename
 */
function sanitize_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
}