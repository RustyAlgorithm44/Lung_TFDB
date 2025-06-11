<?php
// Database connection parameters
$host = 'localhost';
$user = 'tfdb_user';
$password = 'tfdb_guest';
$database = 'promoter';

// Create a new MySQL connection
$conn = new mysqli($host, $user, $password, $database);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all table names from the 'promoter' database
$sql = "SHOW TABLES";
$result = $conn->query($sql);

// Store table names in an array
$tableNames = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tableNames[] = strtoupper($row['Tables_in_' . $database]);  // Capitalize table names
    }
} else {
    echo "No tables found in the database.";
}

// Output the result as JSON
echo json_encode($tableNames);

// Close the connection
$conn->close();
?>