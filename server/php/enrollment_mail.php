<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
  // fallback to form-encoded POST
  $data = $_POST;
}

// Basic required fields check (you can relax this as needed)
$errors = [];
if (empty($data['fullName'])) $errors[] = 'fullName is required';
if (empty($data['email'])) $errors[] = 'email is required';
if (empty($data['phone'])) $errors[] = 'phone is required';

if (!empty($errors)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid data', 'errors' => $errors]);
  exit;
}

// Build HTML body
$body = "<h2>New Enrollment</h2>";
$body .= "<ul>";
$fields = [
  'fullName' => 'Full Name',
  'email' => 'Email',
  'phone' => 'Phone',
  'address' => 'Address',
  'selectedCourse' => 'Selected Course',
  'preferredTiming' => 'Preferred Timing',
  'previousExperience' => 'Previous Experience',
  'motivation' => 'Motivation',
];
foreach ($fields as $k => $label) {
  $val = isset($data[$k]) ? $data[$k] : '';
  $safe = htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  $body .= "<li><strong>{$label}:</strong> {$safe}</li>";
}
$body .= "</ul>";
$altBody = strip_tags(str_replace(["\r", "\n"], [' ', ' '], $body));

// SMTP credentials (hard-coded as requested)
$smtpHost = 'smtp.gmail.com';
$smtpUser = 'newenquiryva@gmail.com';
$smtpPass = 'olfdgqqfuklyrvvk';
$smtpPort = 587;
$fromEmail = 'newenquiryva@gmail.com';
$fromName = 'No Replay';
$toEmail = 'shukladheraj0608@gmail.com';
$toName = 'Dheeraj shukla';
$subject = 'NEW ENROLLMENT';

// Attempt to use PHPMailer if available via Composer autoload
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload)) {
  require_once $autoload;
}

if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
  try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    // Server settings
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpPort;

    // Recipients
    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($toEmail, $toName);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = $altBody;

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Email sent']);
    exit;
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mailer error', 'error' => $mail->ErrorInfo ?? $e->getMessage()]);
    exit;
  }
} else {
  // Fallback to PHP mail() if PHPMailer not available
  $headers = "MIME-Version: 1.0\r\n";
  $headers .= "Content-type: text/html; charset=UTF-8\r\n";
  $headers .= "From: {$fromName} <{$fromEmail}>\r\n";

  $sent = @mail($toEmail, $subject, $body, $headers);
  if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Email sent via mail()']);
    exit;
  } else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send email (mail())']);
    exit;
  }
}
