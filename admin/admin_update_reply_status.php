<?php
session_start();

// Ensure only logged-in admins can access this
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Unauthorized access.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
require '../php/db_connect.php';

if (isset($_POST['action']) && $_POST['action'] == 'mark_replied' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Set as replied AND read
    $stmt = $conn->prepare("UPDATE contact_form SET is_replied = 1, replied_at = CURRENT_TIMESTAMP, is_read = 1, read_at = IFNULL(read_at, CURRENT_TIMESTAMP) WHERE id = ?");
    $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo "Success";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>