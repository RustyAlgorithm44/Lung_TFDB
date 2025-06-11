<?php
header('Content-Type: application/json');

// Path to the CSV file
$filePath = '../files/scrna_seq/genes_from_rds.csv';

// Initialize an array to hold the gene names
$genes = [];

// Open the CSV file for reading
if (($handle = fopen($filePath, 'r')) !== false) {
    // Skip the header row
    $header = fgetcsv($handle);

    // Read the remaining rows and add to the array
    while (($row = fgetcsv($handle)) !== false) {
        $genes[] = $row[0]; // Assuming gene names are in the first column
    }

    // Close the file
    fclose($handle);
}

// Sort the genes array in ascending order
sort($genes);

// Output the result as JSON
echo json_encode($genes);
?>
