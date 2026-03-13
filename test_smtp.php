<?php
require_once 'php/config.php';
require_once 'php/smtp_mail.php';

// Test SMTP email functionality
echo "<h2>HealthMate SMTP Email Test</h2>";

echo "<h3>Configuration Status</h3>";
echo "<p><strong>Admin Email:</strong> " . ADMIN_EMAIL . "</p>";
echo "<p><strong>SMTP Host:</strong> " . SMTP_HOST . "</p>";
echo "<p><strong>SMTP Port:</strong> " . SMTP_PORT . "</p>";
echo "<p><strong>SMTP Username:</strong> " . SMTP_USERNAME . "</p>";
echo "<p><strong>SMTP Password:</strong> " . (SMTP_PASSWORD ? "✅ Configured" : "❌ Not configured") . "</p>";

// Test 1: Gmail SMTP
echo "<h3>Test 1: Gmail SMTP Email</h3>";
$test_subject = "HealthMate SMTP Test - " . date('Y-m-d H:i:s');
$test_message = "This is a test email from HealthMate using Gmail SMTP.\n\n";
$test_message .= "If you receive this email, the SMTP configuration is working correctly.\n";
$test_message .= "Test sent at: " . date('Y-m-d H:i:s') . "\n\n";
$test_message .= "Best regards,\nHealthMate Team";

$result = sendSMTPEmail(ADMIN_EMAIL, $test_subject, $test_message);

if ($result) {
    echo "<p style='color: green;'>✅ Gmail SMTP email sent successfully to " . ADMIN_EMAIL . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Gmail SMTP failed, trying Mercury Mail Server...</p>";
    
    // Test 2: Mercury Mail Server
    echo "<h3>Test 2: Mercury Mail Server</h3>";
    $result2 = sendEmailViaMercury(ADMIN_EMAIL, $test_subject, $test_message);
    
    if ($result2) {
        echo "<p style='color: green;'>✅ Mercury Mail Server email sent successfully</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Mercury Mail Server failed, using file-based fallback...</p>";
        
        // Test 3: File-based fallback
        echo "<h3>Test 3: File-based Email (Fallback)</h3>";
        $result3 = sendEmailToFile(ADMIN_EMAIL, $test_subject, $test_message);
        
        if ($result3) {
            echo "<p style='color: green;'>✅ Email saved to file system (check /emails/ directory)</p>";
        } else {
            echo "<p style='color: red;'>❌ All email methods failed</p>";
        }
    }
}

// Test 4: Feedback notification
echo "<h3>Test 4: Feedback Notification</h3>";
$feedback_data = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'subject' => 'SMTP Test Feedback',
    'message' => 'This is a test feedback message to verify SMTP notifications are working.'
];

$result4 = sendFeedbackNotification($feedback_data);

if ($result4) {
    echo "<p style='color: green;'>✅ Feedback notification sent successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to send feedback notification</p>";
}

// Test 5: Welcome email
echo "<h3>Test 5: Welcome Email</h3>";
$result5 = sendWelcomeEmail('test@example.com', 'Test User');

if ($result5) {
    echo "<p style='color: green;'>✅ Welcome email sent successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to send welcome email</p>";
}

// Check saved emails
$emails_dir = __DIR__ . '/emails/';
if (is_dir($emails_dir)) {
    $email_files = glob($emails_dir . '*.json');
    if (!empty($email_files)) {
        echo "<h3>Saved Emails (File-based)</h3>";
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
echo "<p><strong>If Gmail SMTP still fails:</strong></p>";
echo "<ul>";
echo "<li>Verify your Gmail App Password is correct (16 characters, no spaces)</li>";
echo "<li>Make sure 2-factor authentication is enabled on your Gmail account</li>";
echo "<li>Check if your hosting provider blocks SMTP connections</li>";
echo "<li>Consider using XAMPP's Mercury Mail Server for local testing</li>";
echo "</ul>";

echo "<p><strong>To enable Mercury Mail Server:</strong></p>";
echo "<ul>";
echo "<li>Open XAMPP Control Panel</li>";
echo "<li>Click 'Start' next to Mercury</li>";
echo "<li>Access Mercury configuration at: http://localhost/mercury/</li>";
echo "<li>Set up local email accounts for testing</li>";
echo "</ul>";

echo "<p><a href='test_email.php'>← Back to Original Email Test</a> | <a href='index.html'>← Back to HealthMate</a></p>";
?>
