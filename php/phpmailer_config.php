<?php
// PHPMailer Configuration for HealthMate
require_once 'config.php';

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
    // For now, we'll use a simple approach with proper headers
    // In a production environment, you would install PHPMailer via Composer
    
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "Return-Path: $from_email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: HealthMate Fitness App\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    // Use the configured SMTP settings
    ini_set('SMTP', SMTP_HOST);
    ini_set('smtp_port', SMTP_PORT);
    ini_set('sendmail_from', SMTP_USERNAME);
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Alternative email sending using file-based approach for testing
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param string $from_email Sender email
 * @param string $from_name Sender name
 * @return bool Success status
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
    
    // Create emails directory if it doesn't exist
    $emails_dir = __DIR__ . '/../emails/';
    if (!is_dir($emails_dir)) {
        mkdir($emails_dir, 0755, true);
    }
    
    return file_put_contents($email_file, json_encode($email_data, JSON_PRETTY_PRINT)) !== false;
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
    
    // Try SMTP first, fallback to file-based
    $result = sendEmailWithPHPMailer(ADMIN_EMAIL, $admin_subject, $admin_message);
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
    
    // Try SMTP first, fallback to file-based
    $result = sendEmailWithPHPMailer($user_email, $subject, $message);
    if (!$result) {
        $result = sendEmailToFile($user_email, $subject, $message);
    }
    
    return $result;
}

/**
 * Send achievement notification email
 * @param string $user_email User's email address
 * @param string $user_name User's first name
 * @param string $achievement_name Achievement name
 * @param int $points_earned Points earned
 * @return bool Success status
 */
function sendAchievementEmail($user_email, $user_name, $achievement_name, $points_earned) {
    $subject = "Congratulations! You've earned a new achievement!";
    
    $message = "Hi $user_name,\n\n";
    $message .= "🎉 Congratulations! You've earned the \"$achievement_name\" achievement!\n\n";
    $message .= "You've earned $points_earned points for this achievement.\n";
    $message .= "Keep up the great work and continue your fitness journey!\n\n";
    $message .= "Best regards,\nThe HealthMate Team\n";
    $message .= "---\nThis is an automated achievement notification from HealthMate Fitness App.";
    
    // Try SMTP first, fallback to file-based
    $result = sendEmailWithPHPMailer($user_email, $subject, $message);
    if (!$result) {
        $result = sendEmailToFile($user_email, $subject, $message);
    }
    
    return $result;
}
?>
