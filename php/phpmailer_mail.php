<?php
// PHPMailer Email Configuration for HealthMate
require_once 'config.php';

// Load PHPMailer
$phpmailer_dir = __DIR__ . '/phpmailer/';
$phpmailer_available = false;

if (file_exists($phpmailer_dir . 'src/PHPMailer.php')) {
    require_once $phpmailer_dir . 'src/PHPMailer.php';
    require_once $phpmailer_dir . 'src/SMTP.php';
    require_once $phpmailer_dir . 'src/Exception.php';
    $phpmailer_available = true;
}

/**
 * Send email using PHPMailer with Gmail SMTP
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param string $from_email Sender email
 * @param string $from_name Sender name
 * @return bool Success status
 */
function sendEmailWithPHPMailer($to, $subject, $message, $from_email = 'noreply@healthmate.com', $from_name = 'HealthMate') {
    global $phpmailer_available;
    
    if (!$phpmailer_available) {
        return false; // PHPMailer not available
    }
    
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        // Always send from the authenticated SMTP account for deliverability
        $mail->setFrom(SMTP_USERNAME, 'HealthMate');
        $mail->addAddress($to);
        // Use the provided from_email/from_name as Reply-To so recipients can reply to the actual sender
        if (!empty($from_email)) {
            $mail->addReplyTo($from_email, $from_name);
        }
        
        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        return true;
        
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send feedback notification email
 * @param array $feedback_data Feedback data array
 * @return bool Success status
 */
function sendFeedbackNotification($feedback_data) {
    $admin_subject = "New Feedback from HealthMate: " . $feedback_data['subject'];
    
    $admin_message = "You have received new feedback from HealthMate:\n\n";
    $admin_message .= "Name: " . $feedback_data['name'] . "\n";
    $admin_message .= "Email: " . $feedback_data['email'] . "\n";
    $admin_message .= "Subject: " . $feedback_data['subject'] . "\n\n";
    $admin_message .= "Message:\n" . $feedback_data['message'] . "\n\n";
    $admin_message .= "Submitted on: " . date('Y-m-d H:i:s') . "\n";
    $admin_message .= "---\nThis is an automated notification from HealthMate Fitness App.\n";
    $admin_message .= "To reply, use the email address provided above.";
    
    // Try PHPMailer first, fallback to file-based
    // Set Reply-To as the user's email so admin can respond directly
    $result = sendEmailWithPHPMailer(ADMIN_EMAIL, $admin_subject, $admin_message, $feedback_data['email'], $feedback_data['name']);
    if (!$result) {
        $result = sendEmailToFile(ADMIN_EMAIL, $admin_subject, $admin_message);
    }
    
    return $result;
}

/**
 * Send welcome email to new users
 * @param string $user_email User's email address
 * @param string $user_name User's first name
 * @return bool Success status
 */
function sendWelcomeEmail($user_email, $user_name) {
    $subject = "Welcome to HealthMate - Your Fitness Journey Starts Here!";
    
    $message = "Hi $user_name,\n\n";
    $message .= "Welcome to HealthMate! We're excited to help you achieve your fitness goals.\n\n";
    $message .= "Here's what you can do with HealthMate:\n";
    $message .= "• Track your workouts and progress\n";
    $message .= "• Get personalized workout recommendations\n";
    $message .= "• Earn points and achievements\n";
    $message .= "• Connect with your fitness community\n\n";
    $message .= "Start your fitness journey today by logging into your dashboard!\n\n";
    $message .= "Best regards,\nThe HealthMate Team\n";
    $message .= "---\nThis is an automated welcome email from HealthMate Fitness App.";
    
    // Try PHPMailer first, fallback to file-based
    $result = sendEmailWithPHPMailer($user_email, $subject, $message);
    if (!$result) {
        $result = sendEmailToFile($user_email, $subject, $message);
    }
    
    return $result;
}

/**
 * File-based email fallback
 */
function sendEmailToFile($to, $subject, $message, $from_email = 'noreply@healthmate.com', $from_name = 'HealthMate') {
    $email_data = [
        'to' => $to,
        'from' => $from_email,
        'from_name' => $from_name,
        'subject' => $subject,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $email_file = __DIR__ . '/../emails/' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.json';
    
    $emails_dir = __DIR__ . '/../emails/';
    if (!is_dir($emails_dir)) {
        mkdir($emails_dir, 0755, true);
    }
    
    return file_put_contents($email_file, json_encode($email_data, JSON_PRETTY_PRINT)) !== false;
}
?>
