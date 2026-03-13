<?php
require_once 'php/config.php';
require_once 'php/phpmailer_mail.php';

// Test PHPMailer email functionality
echo "<h2>HealthMate PHPMailer SMTP Test</h2>";

echo "<h3>Configuration Status</h3>";
echo "<p><strong>Admin Email:</strong> " . ADMIN_EMAIL . "</p>";
echo "<p><strong>SMTP Host:</strong> " . SMTP_HOST . "</p>";
echo "<p><strong>SMTP Port:</strong> " . SMTP_PORT . "</p>";
echo "<p><strong>SMTP Username:</strong> " . SMTP_USERNAME . "</p>";
echo "<p><strong>SMTP Password:</strong> " . (SMTP_PASSWORD ? "✅ Configured" : "❌ Not configured") . "</p>";

// Check PHPMailer installation
$phpmailer_dir = __DIR__ . '/php/phpmailer/';
$phpmailer_autoload = $phpmailer_dir . 'src/PHPMailer.php';

if (file_exists($phpmailer_autoload)) {
    echo "<p style='color: green;'>✅ PHPMailer is installed</p>";
} else {
    echo "<p style='color: red;'>❌ PHPMailer not found</p>";
    echo "<p><a href='php/phpmailer_setup.php'>Install PHPMailer</a></p>";
    exit();
}

// Test 1: PHPMailer SMTP
echo "<h3>Test 1: PHPMailer Gmail SMTP</h3>";
$test_subject = "HealthMate PHPMailer Test - " . date('Y-m-d H:i:s');
$test_message = "This is a test email from HealthMate using PHPMailer with Gmail SMTP.\n\n";
$test_message .= "If you receive this email, the PHPMailer SMTP configuration is working correctly.\n";
$test_message .= "Test sent at: " . date('Y-m-d H:i:s') . "\n\n";
$test_message .= "Best regards,\nHealthMate Team";

$result = sendEmailWithPHPMailer(ADMIN_EMAIL, $test_subject, $test_message);

if ($result) {
    echo "<p style='color: green;'>✅ PHPMailer SMTP email sent successfully to " . ADMIN_EMAIL . "</p>";
    echo "<p>Check your Gmail inbox for the test email!</p>";
} else {
    echo "<p style='color: orange;'>⚠️ PHPMailer SMTP failed, using file-based fallback...</p>";
    
    // Fallback to file-based
    $result = sendEmailToFile(ADMIN_EMAIL, $test_subject, $test_message);
    if ($result) {
        echo "<p style='color: green;'>✅ Email saved to file system (check /emails/ directory)</p>";
    } else {
        echo "<p style='color: red;'>❌ All email methods failed</p>";
    }
}

// Test 2: Feedback notification
echo "<h3>Test 2: Feedback Notification (PHPMailer)</h3>";
$feedback_data = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'subject' => 'PHPMailer Test Feedback',
    'message' => 'This is a test feedback message to verify PHPMailer notifications are working.'
];

$result2 = sendFeedbackNotification($feedback_data);

if ($result2) {
    echo "<p style='color: green;'>✅ Feedback notification sent successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to send feedback notification</p>";
}

// Test 3: Welcome email
echo "<h3>Test 3: Welcome Email (PHPMailer)</h3>";
$result3 = sendWelcomeEmail('test@example.com', 'Test User');

if ($result3) {
    echo "<p style='color: green;'>✅ Welcome email sent successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to send welcome email</p>";
}

// Check saved emails
$emails_dir = __DIR__ . '/emails/';
if (is_dir($emails_dir)) {
    $email_files = glob($emails_dir . '*.json');
    if (!empty($email_files)) {
        echo "<h3>Saved Emails (File-based Fallback)</h3>";
        echo "<p>Found " . count($email_files) . " saved email(s):</p>";
        echo "<ul>";
        foreach (array_slice($email_files, -3) as $file) { // Show last 3 emails
            $email_data = json_decode(file_get_contents($file), true);
            echo "<li><strong>" . basename($file) . "</strong> - To: " . $email_data['to'] . " - Subject: " . $email_data['subject'] . "</li>";
        }
        echo "</ul>";
    }
}

echo "<h3>Next Steps</h3>";
echo "<p>1. Check your email inbox at " . ADMIN_EMAIL . " for test emails</p>";
echo "<p>2. If emails are not received, check your spam folder</p>";
echo "<p>3. Check the <code>/emails/</code> directory for file-based emails</p>";
echo "<p>4. <a href='view_emails.php'>View all saved emails</a></p>";

echo "<h3>Troubleshooting</h3>";
echo "<p><strong>If PHPMailer SMTP fails:</strong></p>";
echo "<ul>";
echo "<li>Verify your Gmail App Password is correct (16 characters, no spaces)</li>";
echo "<li>Make sure 2-factor authentication is enabled on your Gmail account</li>";
echo "<li>Check if your hosting provider blocks SMTP connections</li>";
echo "<li>Check error logs for detailed error messages</li>";
echo "</ul>";

echo "<p><strong>Gmail Security Settings:</strong></p>";
echo "<ul>";
echo "<li>Enable 'Less secure app access' (if available)</li>";
echo "<li>Use App Passwords instead of regular passwords</li>";
echo "<li>Check Gmail's security settings for any blocks</li>";
echo "</ul>";

echo "<p><a href='test_smtp.php'>← Back to SMTP Test</a> | <a href='index.html'>← Back to HealthMate</a></p>";
?>
