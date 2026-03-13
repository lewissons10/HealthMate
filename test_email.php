<?php
require_once 'php/config.php';
require_once 'php/phpmailer_config.php';

// Test email functionality
echo "<h2>HealthMate Email Test</h2>";

// Test 1: Basic email function with SMTP
echo "<h3>Test 1: Basic Email Function (SMTP)</h3>";
$test_subject = "HealthMate Email Test - " . date('Y-m-d H:i:s');
$test_message = "This is a test email from HealthMate Fitness App.\n\n";
$test_message .= "If you receive this email, the email configuration is working correctly.\n";
$test_message .= "Test sent at: " . date('Y-m-d H:i:s') . "\n\n";
$test_message .= "Best regards,\nHealthMate Team";

$result = sendEmailWithPHPMailer(ADMIN_EMAIL, $test_subject, $test_message);

if ($result) {
    echo "<p style='color: green;'>✅ Test email sent successfully to " . ADMIN_EMAIL . "</p>";
} else {
    echo "<p style='color: orange;'>⚠️ SMTP email failed, trying file-based approach...</p>";
    
    // Fallback to file-based email
    $result = sendEmailToFile(ADMIN_EMAIL, $test_subject, $test_message);
    if ($result) {
        echo "<p style='color: green;'>✅ Email saved to file system (check /emails/ directory)</p>";
    } else {
        echo "<p style='color: red;'>❌ Both SMTP and file-based email failed</p>";
    }
}

// Test 2: Feedback notification
echo "<h3>Test 2: Feedback Notification</h3>";
$feedback_data = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'subject' => 'Test Feedback',
    'message' => 'This is a test feedback message to verify email notifications are working.'
];

$result2 = sendFeedbackNotification($feedback_data);

if ($result2) {
    echo "<p style='color: green;'>✅ Feedback notification sent successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to send feedback notification</p>";
}

// Test 3: Welcome email
echo "<h3>Test 3: Welcome Email</h3>";
$result3 = sendWelcomeEmail('test@example.com', 'Test User');

if ($result3) {
    echo "<p style='color: green;'>✅ Welcome email sent successfully</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to send welcome email</p>";
}

// Test 4: File-based email (for testing)
echo "<h3>Test 4: File-based Email (Testing)</h3>";
$result4 = sendEmailToFile('test@example.com', 'Test File Email', 'This is a test email saved to file system.');

if ($result4) {
    echo "<p style='color: green;'>✅ File-based email saved successfully</p>";
    echo "<p>Check the <code>/emails/</code> directory for saved emails</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to save file-based email</p>";
}

// Display configuration
echo "<h3>Email Configuration</h3>";
echo "<p><strong>Admin Email:</strong> " . ADMIN_EMAIL . "</p>";
echo "<p><strong>SMTP Host:</strong> " . SMTP_HOST . "</p>";
echo "<p><strong>SMTP Port:</strong> " . SMTP_PORT . "</p>";
echo "<p><strong>SMTP Username:</strong> " . SMTP_USERNAME . "</p>";

// Check if emails directory exists and show saved emails
$emails_dir = __DIR__ . '/emails/';
if (is_dir($emails_dir)) {
    $email_files = glob($emails_dir . '*.json');
    if (!empty($email_files)) {
        echo "<h3>Saved Emails (File-based)</h3>";
        echo "<p>Found " . count($email_files) . " saved email(s):</p>";
        echo "<ul>";
        foreach (array_slice($email_files, -5) as $file) { // Show last 5 emails
            $email_data = json_decode(file_get_contents($file), true);
            echo "<li><strong>" . basename($file) . "</strong> - To: " . $email_data['to'] . " - Subject: " . $email_data['subject'] . "</li>";
        }
        echo "</ul>";
    }
}

echo "<h3>Next Steps</h3>";
echo "<p>1. Check your email inbox at " . ADMIN_EMAIL . " for test emails</p>";
echo "<p>2. If emails are not received, check your spam folder</p>";
echo "<p>3. For Gmail, you may need to set up an App Password</p>";
echo "<p>4. Check the <code>/emails/</code> directory for file-based emails</p>";
echo "<p>5. Configure XAMPP's Mercury Mail Server for local email testing</p>";

echo "<h3>Troubleshooting</h3>";
echo "<p><strong>If SMTP fails:</strong></p>";
echo "<ul>";
echo "<li>Enable 2-factor authentication on your Gmail account</li>";
echo "<li>Generate an App Password and add it to config.php</li>";
echo "<li>Check if your hosting provider allows SMTP connections</li>";
echo "<li>Use the file-based email system for testing</li>";
echo "</ul>";

echo "<p><a href='index.html'>← Back to HealthMate</a></p>";
?>
