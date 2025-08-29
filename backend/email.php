<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Load email configuration
$emailConfig = require_once 'config/email.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit();
}

// Validate required fields
$requiredFields = ['to', 'subject', 'htmlBody', 'textBody', 'eventDetails'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// Extract data
$to = $input['to'];
$subject = $input['subject'];
$htmlBody = $input['htmlBody'];
$textBody = $input['textBody'];
$eventDetails = $input['eventDetails'];

// Validate email addresses
if (!is_array($to) || empty($to)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid recipients list']);
    exit();
}

foreach ($to as $email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Invalid email address: $email"]);
        exit();
    }
}

// Email headers
$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . $emailConfig['company_smtp']['from_name'] . ' <' . $emailConfig['company_smtp']['from_email'] . '>',
    'Reply-To: ' . ($eventDetails['organizer'] ?? $emailConfig['company_smtp']['from_email']),
    'X-Mailer: Mithi Calendar/1.0'
];

// Send emails using company SMTP
$successCount = 0;
$failedEmails = [];

foreach ($to as $email) {
    try {
        // Send email using company SMTP
        $mailSent = sendEmailViaCompanySMTP($email, $subject, $htmlBody, $textBody, $emailConfig);
        
        if ($mailSent) {
            $successCount++;
            error_log("Email sent successfully to: $email via company SMTP");
        } else {
            $failedEmails[] = $email;
            error_log("Failed to send email to: $email via company SMTP");
        }
    } catch (Exception $e) {
        $failedEmails[] = $email;
        error_log("Error sending email to $email: " . $e->getMessage());
    }
}

// Prepare response
$response = [
    'success' => $successCount > 0,
    'message' => "Sent $successCount out of " . count($to) . " invitations via company SMTP",
    'data' => [
        'totalRecipients' => count($to),
        'successfulSends' => $successCount,
        'failedSends' => count($failedEmails),
        'failedEmails' => $failedEmails,
        'smtp_server' => $emailConfig['company_smtp']['host']
    ]
];

if ($successCount === 0) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Failed to send any invitations via company SMTP';
}

echo json_encode($response);

/**
 * Send email using company SMTP server with CalDAV authentication
 */
function sendEmailViaCompanySMTP($to, $subject, $htmlBody, $textBody, $config) {
    // Check if company SMTP is enabled
    if (!$config['company_smtp']['enabled']) {
        error_log("Company SMTP is disabled");
        return false;
    }
    
    try {
        // Get CalDAV credentials if available
        $username = null;
        $password = null;
        
        if ($config['company_smtp']['use_caldav_auth']) {
            // Try to get credentials from CalDAV client
            $caldavClient = getCalDAVClient();
            if ($caldavClient) {
                $username = $caldavClient->getUsername();
                $password = $caldavClient->getPassword();
                error_log("Using CalDAV credentials for SMTP: " . $username);
            }
        }
        
        // Use fallback credentials if CalDAV auth failed
        if (!$username || !$password) {
            $username = $config['company_smtp']['fallback_username'];
            $password = $config['company_smtp']['fallback_password'];
            error_log("Using fallback credentials for SMTP: " . $username);
        }
        
        if (!$username || !$password) {
            error_log("No valid credentials available for SMTP");
            return false;
        }
        
        // Create SMTP connection
        $smtp = fsockopen(
            $config['company_smtp']['host'], 
            $config['company_smtp']['port'], 
            $errno, 
            $errstr, 
            $config['company_smtp']['timeout']
        );
        
        if (!$smtp) {
            error_log("Failed to connect to SMTP server: $errstr ($errno)");
            return false;
        }
        
        // Read server response
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP Server: $response");
        }
        
        // Send EHLO
        fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP EHLO: $response");
        }
        
        // Start TLS if required
        if ($config['company_smtp']['security'] === 'tls') {
            fputs($smtp, "STARTTLS\r\n");
            $response = fgets($smtp, 515);
            if ($config['company_smtp']['debug']) {
                error_log("SMTP STARTTLS: $response");
            }
            
            // Enable crypto
            if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                error_log("Failed to enable TLS");
                fclose($smtp);
                return false;
            }
            
            // Send EHLO again after TLS
            fputs($smtp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
            $response = fgets($smtp, 515);
            if ($config['company_smtp']['debug']) {
                error_log("SMTP EHLO after TLS: $response");
            }
        }
        
        // Authentication
        if ($config['company_smtp']['auth_type'] === 'LOGIN') {
            fputs($smtp, "AUTH LOGIN\r\n");
            $response = fgets($smtp, 515);
            if ($config['company_smtp']['debug']) {
                error_log("SMTP AUTH: $response");
            }
            
            fputs($smtp, base64_encode($username) . "\r\n");
            $response = fgets($smtp, 515);
            if ($config['company_smtp']['debug']) {
                error_log("SMTP USERNAME: $response");
            }
            
            fputs($smtp, base64_encode($password) . "\r\n");
            $response = fgets($smtp, 515);
            if ($config['company_smtp']['debug']) {
                error_log("SMTP PASSWORD: $response");
            }
        }
        
        // Send email
        fputs($smtp, "MAIL FROM: <" . $config['company_smtp']['from_email'] . ">\r\n");
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP MAIL FROM: $response");
        }
        
        fputs($smtp, "RCPT TO: <$to>\r\n");
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP RCPT TO: $response");
        }
        
        fputs($smtp, "DATA\r\n");
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP DATA: $response");
        }
        
        // Email content
        $emailContent = "From: " . $config['company_smtp']['from_name'] . " <" . $config['company_smtp']['from_email'] . ">\r\n";
        $emailContent .= "To: $to\r\n";
        $emailContent .= "Subject: $subject\r\n";
        $emailContent .= "MIME-Version: 1.0\r\n";
        $emailContent .= "Content-Type: text/html; charset=UTF-8\r\n";
        $emailContent .= "\r\n";
        $emailContent .= $htmlBody . "\r\n";
        $emailContent .= ".\r\n";
        
        fputs($smtp, $emailContent);
        $response = fgets($smtp, 515);
        if ($config['company_smtp']['debug']) {
            error_log("SMTP SEND: $response");
        }
        
        // Quit
        fputs($smtp, "QUIT\r\n");
        fclose($smtp);
        
        error_log("Email sent successfully via company SMTP to: $to");
        return true;
        
    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        if (isset($smtp) && is_resource($smtp)) {
            fclose($smtp);
        }
        return false;
    }
}

/**
 * Get CalDAV client instance to access user credentials
 */
function getCalDAVClient() {
    try {
        // Check if CalDAV client class exists
        if (!class_exists('CalDAVClient')) {
            require_once 'classes/CalDAVClient.php';
        }
        
        // Get CalDAV configuration
        // NOTE: For GitHub users: Copy caldav_demo.php to caldav.php and update with your credentials
        // The actual caldav.php file is gitignored to protect your credentials
        $caldavConfig = require_once 'config/caldav.php';
        
        // Create CalDAV client instance
        $caldavClient = new CalDAVClient(
            $caldavConfig['server_url'],
            $caldavConfig['username'],
            $caldavConfig['password']
        );
        
        return $caldavClient;
        
    } catch (Exception $e) {
        error_log("Failed to create CalDAV client: " . $e->getMessage());
        return null;
    }
}
?>
