<?php
require_once 'config.php';
require_once 'phpmailer_mail.php';

if ($_POST['action'] == 'submit_feedback') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $subject = sanitizeInput($_POST['subject']);
    $message = sanitizeInput($_POST['message']);
    
    try {
        $pdo = getDBConnection();
        
        // Insert feedback into database
        $stmt = $pdo->prepare("INSERT INTO feedback (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);
        
        // Prepare feedback data for email
        $feedback_data = [
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message
        ];
        
        // Send email notification to admin
        $email_sent = sendFeedbackNotification($feedback_data);
        
        // Send confirmation email to user (non-blocking best-effort)
        $user_subject = "We received your message: " . $subject;
        $user_message = "Hi $name,\n\n";
        $user_message .= "Thanks for reaching out to HealthMate. We've received your message and will get back to you soon.\n\n";
        $user_message .= "Your message details:\n";
        $user_message .= "Subject: $subject\n";
        $user_message .= "Message: $message\n\n";
        $user_message .= "Best regards,\nThe HealthMate Team";
        // Use admin as sender, reply-to to admin; send to user
        if (!sendEmailWithPHPMailer($email, $user_subject, $user_message)) {
            // fallback to file-based logging if SMTP fails
            sendEmailToFile($email, $user_subject, $user_message);
        }
        
        if ($email_sent) {
            echo json_encode(['success' => true, 'message' => 'Thank you for your feedback! We\'ll get back to you soon.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Thank you for your feedback! We\'ll get back to you soon. (Note: Email notification failed)']);
        }
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to submit feedback: ' . $e->getMessage()]);
    }
}
?>
