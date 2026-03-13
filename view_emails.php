<?php
// Email Viewer for HealthMate
$emails_dir = __DIR__ . '/emails/';

echo "<h2>HealthMate Email Viewer</h2>";

if (!is_dir($emails_dir)) {
    echo "<p style='color: orange;'>No emails directory found. Run the email test first.</p>";
    echo "<p><a href='test_email.php'>Run Email Test</a></p>";
    exit();
}

$email_files = glob($emails_dir . '*.json');

if (empty($email_files)) {
    echo "<p style='color: orange;'>No saved emails found.</p>";
    echo "<p><a href='test_email.php'>Run Email Test</a></p>";
    exit();
}

// Sort files by modification time (newest first)
usort($email_files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

echo "<h3>Saved Emails (" . count($email_files) . " total)</h3>";

foreach ($email_files as $file) {
    $email_data = json_decode(file_get_contents($file), true);
    $file_time = date('Y-m-d H:i:s', filemtime($file));
    
    echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px;'>";
    echo "<h4>📧 " . htmlspecialchars($email_data['subject']) . "</h4>";
    echo "<p><strong>To:</strong> " . htmlspecialchars($email_data['to']) . "</p>";
    echo "<p><strong>From:</strong> " . htmlspecialchars($email_data['from_name']) . " &lt;" . htmlspecialchars($email_data['from']) . "&gt;</p>";
    echo "<p><strong>Time:</strong> " . $file_time . "</p>";
    echo "<p><strong>File:</strong> " . basename($file) . "</p>";
    echo "<div style='background: #f9f9f9; padding: 10px; margin: 10px 0; border-radius: 3px;'>";
    echo "<strong>Message:</strong><br>";
    echo "<pre style='white-space: pre-wrap;'>" . htmlspecialchars($email_data['message']) . "</pre>";
    echo "</div>";
    echo "</div>";
}

echo "<p><a href='test_email.php'>← Back to Email Test</a> | <a href='index.html'>← Back to HealthMate</a></p>";
?>
