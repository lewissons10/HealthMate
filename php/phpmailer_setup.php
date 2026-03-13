<?php
// PHPMailer Setup for HealthMate
// This script will download and set up PHPMailer for proper SMTP email

echo "<h2>HealthMate PHPMailer Setup</h2>";

// Check if PHPMailer is already installed
$phpmailer_dir = __DIR__ . '/phpmailer/';
$phpmailer_autoload = $phpmailer_dir . 'src/PHPMailer.php';

if (file_exists($phpmailer_autoload)) {
    echo "<p style='color: green;'>✅ PHPMailer is already installed</p>";
} else {
    echo "<p style='color: orange;'>⚠️ PHPMailer not found. Installing...</p>";
    
    // Create directory
    if (!is_dir($phpmailer_dir)) {
        mkdir($phpmailer_dir, 0755, true);
    }
    
    // Download PHPMailer files
    $files = [
        'src/PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
        'src/SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php',
        'src/Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php'
    ];
    
    foreach ($files as $file => $url) {
        $file_path = $phpmailer_dir . $file;
        $dir = dirname($file_path);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $content = file_get_contents($url);
        if ($content !== false) {
            file_put_contents($file_path, $content);
            echo "<p style='color: green;'>✅ Downloaded: $file</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to download: $file</p>";
        }
    }
}

// Test PHPMailer installation
if (file_exists($phpmailer_autoload)) {
    echo "<h3>Testing PHPMailer Installation</h3>";
    
    try {
        require_once $phpmailer_autoload;
        require_once $phpmailer_dir . 'src/SMTP.php';
        require_once $phpmailer_dir . 'src/Exception.php';
        
        echo "<p style='color: green;'>✅ PHPMailer loaded successfully</p>";
        
        // Test PHPMailer functionality
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        echo "<p style='color: green;'>✅ PHPMailer object created successfully</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ PHPMailer error: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Next Steps</h3>";
echo "<p>1. <a href='test_phpmailer.php'>Test PHPMailer SMTP</a></p>";
echo "<p>2. <a href='test_smtp.php'>Back to SMTP Test</a></p>";
echo "<p>3. <a href='index.html'>Back to HealthMate</a></p>";
?>
