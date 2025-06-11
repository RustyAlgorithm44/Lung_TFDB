<?php

require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $dataset = $_POST['dataset'];
    $rowsPerPage = isset($_POST['rowsPerPage']) ? intval($_POST['rowsPerPage']) : 25;
    $searchGene = isset($_POST['searchGene']) ? $_POST['searchGene'] : ''; // Capture search term
    $limit = $rowsPerPage > 0 ? "LIMIT $rowsPerPage" : "";

    $searchCondition = "";
    if (!empty($searchGene)) {
        $searchGene = mysqli_real_escape_string($conn, $searchGene);
        $searchCondition = "AND gene_id LIKE '%$searchGene%'"; // Search by gene name (gene_id column)
    }

    if ($dataset === 'adeno' || $dataset === 'squam' || $dataset === 'oncosg' || $dataset === 'mskcc' || $dataset === 'mskcc2020' || $dataset === 'mskcc2022') {
        // Determine table name based on cancer type
        $tableName = $dataset . '_mut_data';

        // Query to fetch protein sequences with gene_id and protein_sequence columns
        $sql = "SELECT gene_id, protein_sequence FROM $tableName WHERE gene_id LIKE '%$searchGene%' $limit";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $count = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $sequenceLength = strlen($row['protein_sequence']);
                echo "<tr>";
                echo "<td>" . $count++ . "</td>";
                echo "<td>" . $row['gene_id'] . "</td>";
                echo "<td class='max-width-td'>";
                echo "<div class='sequence-container'>";
                echo "<span class='sequence-text'>" . $row['protein_sequence'] . "</span>";
                echo "<button class='copy-button' onclick='copySequence(this)'>Copy</button>";
                echo "</div>";
                echo "</td>";
                echo "<td>" . $sequenceLength . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No results found.</td></tr>";
        }
    } elseif ($dataset === 'gse102287' || $dataset === 'gse14814' || $dataset === 'gse157011' || $dataset === 'gse19188' || $dataset === 'gse29013' || $dataset === 'gse30219' || $dataset === 'gse31210' || $dataset === 'gse3141' || $dataset === 'gse37745' || $dataset === 'gse4573' || $dataset === 'gse50081' || $dataset === 'gse68465' || $dataset === 'all') {
        // Determine table name based on cancer type
        $tableName = $dataset . '_data';

        // Query to fetch protein sequences with gene_id and protein_sequence columns
        $sql = "SELECT gene_id, protein_sequence FROM $tableName WHERE gene_id LIKE '%$searchGene%' $limit";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            $count = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                $sequenceLength = strlen($row['protein_sequence']);
                echo "<tr>";
                echo "<td>" . $count++ . "</td>";
                echo "<td>" . $row['gene_id'] . "</td>";
                echo "<td class='max-width-td'>";
                echo "<div class='sequence-container'>";
                echo "<span class='sequence-text'>" . $row['protein_sequence'] . "</span>";
                echo "<button class='copy-button' onclick='copySequence(this)'>Copy</button>";
                echo "</div>";
                echo "</td>";
                echo "<td>" . $sequenceLength . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>No results found.</td></tr>";
        }
    } else {
        echo "<tr><td colspan='3'>Invalid cancer type.</td></tr>";
    }

    mysqli_close($conn);
}
?>
