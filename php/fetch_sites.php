<?php
// Database credentials
$host = 'localhost';
$user = 'tfdb_user';
$password = 'tfdb_guest';
$database = 'promoter';

// Create a connection
$conn = new mysqli($host, $user, $password, $database);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the table name and other parameters
$table = isset($_POST['geneName']) ? strtoupper(trim($_POST['geneName'])) : '';
$filterGene = isset($_POST['filterGene']) ? strtoupper(trim($_POST['filterGene'])) : '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 100;
$offset = ($page - 1) * $limit;

// Check if the table exists in the database
$query = "SHOW TABLES LIKE '$table'";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    // Table exists, fetch the data
    $whereClause = $filterGene ? " WHERE Gene LIKE '%$filterGene%'" : ''; // Apply filtering
    $query = "SELECT * FROM `$table`" . $whereClause . " LIMIT $limit OFFSET $offset";
    $data_result = $conn->query($query);

    if ($data_result && $data_result->num_rows > 0) {
        // Output the table data
        echo "<table border='1' cellpadding='10'>";
        echo "<thead>
                <tr>
                    <th>TF_chr</th>
                    <th>TF_start</th>
                    <th>TF_end</th>
                    <th>SampleID</th>
                    <th>Promoter_chr</th>
                    <th>Promoter_start</th>
                    <th>Promoter_end</th>
                    <th>Gene</th>
                </tr>
              </thead>";
        echo "<tbody>";

        while ($row = $data_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['TF_chr'] . "</td>";
            echo "<td>" . $row['TF_start'] . "</td>";
            echo "<td>" . $row['TF_end'] . "</td>";
            echo "<td>" . $row['SampleID'] . "</td>";
            echo "<td>" . $row['Promoter_chr'] . "</td>";
            echo "<td>" . $row['Promoter_start'] . "</td>";
            echo "<td>" . $row['Promoter_end'] . "</td>";
            echo "<td>" . $row['Gene'] . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
    } else {
        echo "No data found in the table.";
    }

    // Optionally, send total row count for pagination (useful for calculating total pages)
    $countQuery = "SELECT COUNT(*) AS totalRows FROM `$table`" . $whereClause;
    $countResult = $conn->query($countQuery);
    $countData = $countResult->fetch_assoc();
    $totalRows = $countData['totalRows'];
    echo "<script>window.totalRows = $totalRows;</script>"; // Pass total rows count to JavaScript for pagination

} else {
    echo "Table not found: $table";
}

// Close the database connection
$conn->close();
?>