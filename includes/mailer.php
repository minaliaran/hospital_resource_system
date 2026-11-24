<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
function send_system_email($to, $subject, $body, $conn) {
    global $smtp_enabled, $smtp_user, $smtp_pass;
    $to = trim($to);
    $sent_by = $_SESSION['user_id'] ?? null;
    $status = 'failed';
    if ($smtp_enabled && !empty($smtp_user) && !empty($smtp_pass)) {
        $mail_sent = send_smtp_email($to, $subject, $body, $smtp_user, $smtp_pass);
        $status = $mail_sent ? 'sent' : 'failed';
    } else {
        $headers = "From: HRCS System <no-reply@health.gov.lk>\r\n";
        $headers .= "Reply-To: no-reply@health.gov.lk\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        $mail_sent = @mail($to, $subject, $body, $headers);
        $status = $mail_sent ? 'sent' : 'failed';
    }
    $stmt = $conn->prepare("INSERT INTO email_logs (recipient, subject, message, status, sent_by, sent_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssi", $to, $subject, $body, $status, $sent_by);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
function send_smtp_email($to, $subject, $body, $from_email, $app_password) {
    $to = trim($to);
    $smtp_server = 'ssl://smtp.gmail.com';
    $port = 465;
    $app_password = str_replace(' ', '', $app_password);
    $username = base64_encode($from_email);
    $password = base64_encode($app_password);
    $socket = @fsockopen($smtp_server, $port, $errno, $errstr, 10);
    if (!$socket) {
        return false;
    }
    $read_reply = function($sock) {
        $reply = '';
        while ($line = fgets($sock, 512)) {
            $reply .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return $reply;
    };
    $read_reply($socket);
    fwrite($socket, "EHLO localhost\r\n");
    $read_reply($socket);
    fwrite($socket, "AUTH LOGIN\r\n");
    $read_reply($socket);
    fwrite($socket, $username . "\r\n");
    $read_reply($socket);
    fwrite($socket, $password . "\r\n");
    $auth_res = $read_reply($socket);
    if (strpos($auth_res, '235') === false) {
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return false;
    }
    fwrite($socket, "MAIL FROM: <$from_email>\r\n");
    $read_reply($socket);
    fwrite($socket, "RCPT TO: <$to>\r\n");
    $read_reply($socket);
    fwrite($socket, "DATA\r\n");
    $read_reply($socket);
    $headers = "From: HRCS System <$from_email>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    fwrite($socket, $headers . $body . "\r\n.\r\n");
    $data_res = $read_reply($socket);
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return (strpos($data_res, '250') !== false);
}
