<?php

require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $dataset = $_POST['dataset'];
    $geneName = $_POST['geneName'];

    if ($dataset === 'adeno' || $dataset === 'squam' || $dataset ==='oncosg' || $dataset ==='mskcc' || $dataset ==='mskcc2020' || $dataset ==='all') {
        $tableName = $dataset . '_mutated'; // Determine table name based on cancer type
        $geneName = mysqli_real_escape_string($conn, $geneName);

        // Query to fetch data from the selected table where Hugo_Symbol matches the user input
        $query = "SELECT * FROM $tableName WHERE Hugo_Symbol = '$geneName'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            // Display the table with fetched rows
			echo "<h3>Mutation details for $geneName</h3>";
            echo '<table border=1>';
            echo '<tr><th>Chr. No</th><th>Start</th><th>End</th><th>Strand</th><th>Consequence</th><th>Variant_Classification</th><th>Variant_Type</th><th>Samples</th><th>HGVSc</th><th>HGVSp</th></tr>';
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<tr>';
                echo '<td>' . $row['Chromosome'] . '</td>';
                echo '<td>' . $row['Start_Position'] . '</td>';
                echo '<td>' . $row['End_Position'] . '</td>';
                echo '<td>' . $row['Strand'] . '</td>';
                echo '<td>' . $row['Consequence'] . '</td>';
                echo '<td>' . $row['Variant_Classification'] . '</td>';
                echo '<td>' . $row['Variant_Type'] . '</td>';
                echo '<td>' . $row['Tumor_Sample_Barcode'] . '</td>';
                echo '<td>' . $row['HGVSc'] . '</td>';
                echo '<td>' . $row['HGVSp'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo 'No results found.';
        }

        mysqli_free_result($result);
    } 
	elseif ($dataset ==='mskcc2022_adeno' || $dataset ==='mskcc2022_squam') {
        $tableName = 'mskcc2022_mutated'; // Determine table name based on cancer type
        $geneName = mysqli_real_escape_string($conn, $geneName);

        // Query to fetch data from the selected table where Hugo_Symbol matches the user input
        $query = "SELECT * FROM $tableName WHERE Hugo_Symbol = '$geneName'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            // Display the table with fetched rows
			echo "<h3>Mutation details for $geneName</h3>";
            echo '<table border=1>';
            echo '<tr><th>Chr. No</th><th>Start</th><th>End</th><th>Strand</th><th>Consequence</th><th>Variant_Classification</th><th>Variant_Type</th><th>Samples</th><th>HGVSc</th><th>HGVSp</th><th>Impact</th></tr>';
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<tr>';
                echo '<td>' . $row['Chromosome'] . '</td>';
                echo '<td>' . $row['Start_Position'] . '</td>';
                echo '<td>' . $row['End_Position'] . '</td>';
                echo '<td>' . $row['Strand'] . '</td>';
                echo '<td>' . $row['Consequence'] . '</td>';
                echo '<td>' . $row['Variant_Classification'] . '</td>';
                echo '<td>' . $row['Variant_Type'] . '</td>';
                echo '<td>' . $row['Tumor_Sample_Barcode'] . '</td>';
                echo '<td>' . $row['HGVSc'] . '</td>';
                echo '<td>' . $row['HGVSp'] . '</td>';
                echo '<td>' . $row['IMPACT'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo 'No results found.';
        }

        mysqli_free_result($result);
    } else {
        echo 'Invalid cancer type.';
    }

    mysqli_close($conn);
}
?>