<?php
// Enhanced Email Configuration for HealthMate
require_once 'config.php';

// Configure PHP mail settings for Gmail SMTP
ini_set('SMTP', SMTP_HOST);
ini_set('smtp_port', SMTP_PORT);
ini_set('sendmail_from', SMTP_USERNAME);

/**
 * Send email using PHP's built-in mail function with proper SMTP configuration
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param string $from_email Sender email
 * @param string $from_name Sender name
 * @return bool Success status
 */
function sendEmail($to, $subject, $message, $from_email = 'noreply@healthmate.com', $from_name = 'HealthMate') {
    // Set additional headers for better email delivery
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "Return-Path: $from_email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: HealthMate Fitness App\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    return mail($to, $subject, $message, $headers);
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
    
    return sendEmail(ADMIN_EMAIL, $admin_subject, $admin_message);
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
    
    return sendEmail($user_email, $subject, $message);
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
    
    return sendEmail($user_email, $subject, $message);
}
?>
