<?php
session_start();

// Ensure only logged-in admins can send replies
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $to = $_POST['to'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $messageId = intval($_POST['id']);

    if (empty($to) || empty($subject) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit();
    }

    // Modern HTML Email Header
    $headers = "MIME-Version: 1.0" . "
";
    $headers .= "Content-type:text/html;charset=UTF-8" . "
";
    $headers .= "From: Lung TFDB Support <noreply@lungtfdb.bioinference.in>" . "
";
    $headers .= "Reply-To: guruguhan@ssnccpr.org" . "
";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
            .footer { font-size: 0.8rem; color: #777; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Reply from Lung TFDB</h2>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
            <div class='footer'>
                <p>This is an automated response from <strong>Lung TFDB</strong>.</p>
                <p><a href='https://lungtfdb.bioinference.in'>lungtfdb.bioinference.in</a></p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Attempt to send
    if (mail($to, $subject, $emailBody, $headers)) {
        // Success! Now update the database status
        require 'db_connect.php';
        
        $stmt = $conn->prepare("UPDATE contact_form SET is_replied = 1, replied_at = CURRENT_TIMESTAMP, is_read = 1, read_at = IFNULL(read_at, CURRENT_TIMESTAMP) WHERE id = ?");
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        echo json_encode(['status' => 'success', 'message' => 'Email sent successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send email. Please check server SMTP configuration.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>