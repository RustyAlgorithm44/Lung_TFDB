<?php
session_start();

// Ensure only logged-in admins can access this
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Unauthorized access.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "localhost";
    $username_db = "root";
    $password_db = "";
    $dbname = "tfdb";

    $conn = new mysqli($servername, $username_db, $password_db, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (isset($_POST['action']) && $_POST['action'] == 'mark_replied' && isset($_POST['id'])) {
        $id = intval($_POST['id']);

        $stmt = $conn->prepare("UPDATE contact_form SET is_replied = 1, replied_at = CURRENT_TIMESTAMP WHERE id = ?");
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