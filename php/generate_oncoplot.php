<?php

require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dataset = $_POST['dataset'];

    // Validate dataset
    $datasets = [
        'adeno', 'squam', 'oncosg', 'mskcc', 'mskcc2020',
        'mskcc2022_adeno', 'mskcc2022_squam', 'all'
    ];

    if (!in_array($dataset, $datasets)) {
        die("Invalid dataset selection.");
    }

    // Determine table name
    if ($dataset === 'mskcc2022_adeno' || $dataset === 'mskcc2022_squam') {
        $tableName = 'mskcc2022_mutated';
    } else {
        $tableName = $dataset . '_mutated';
    }

    // Query entire mutation table (no gene filtering)
    $query = "SELECT * FROM $tableName";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 0) {
        die("No mutation data found.");
    }

    // Create directory if it doesn't exist
    $plotsDir = '../plots/';
    if (!is_dir($plotsDir)) {
        mkdir($plotsDir, 0777, true);
    }

    // Remove old plots before generating new ones
    array_map('unlink', glob($plotsDir . "*.pdf"));

    // Generate unique filenames
    $pdfFile = $plotsDir . 'mutation_plots_' . uniqid() . '.pdf';
    $rScriptFile = $plotsDir . 'script_' . uniqid() . '.R';

    // Write TSV file
    $tsvFile = $plotsDir . 'mutation_data_' . uniqid() . '.tsv';
    $file = fopen($tsvFile, 'w');
    $header = array_keys(mysqli_fetch_assoc($result));
    fputcsv($file, $header, "\t");
    mysqli_data_seek($result, 0);
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($file, $row, "\t");
    }
    fclose($file);
    mysqli_free_result($result);
    mysqli_close($conn);

    // Write R script file to generate PDF
    $rCode = ".libPaths('C:/Users/Lung-TFDB/AppData/Local/R/win-library/4.5')\n" .
        "library(maftools)\n" .
        "maf <- read.maf(maf='$tsvFile')\n" .
        "pdf('$pdfFile', width=9, height=6)\n" .

        "old_par <- par(no.readonly = TRUE)\n" .
        "plotmafSummary(maf = maf, addStat = 'median', rmOutlier = TRUE, dashboard = TRUE, titvRaw = FALSE, top = 15)\n" .
        "par(old_par)\n" .

        "old_par <- par(no.readonly = TRUE)\n" .
        "mafbarplot(maf = maf, fontSize = 1, legendfontSize = 1)\n" .
        "par(old_par)\n" .

        "old_par <- par(no.readonly = TRUE)\n" .
        "oncoplot(maf, top=20)\n" .
        "par(old_par)\n" .

        "old_par <- par(no.readonly = TRUE)\n" .
        "pws = pathways(maf = maf, plotType = 'bar')\n" .
        "plotPathways(maf = maf, pathlist = pws)\n" .
        "par(old_par)\n" .

        "dev.off()\n";

    file_put_contents($rScriptFile, $rCode);

    // Construct and execute R command
    //$rCommand = "\"C:\\Program Files\\R\\R-4.5.0\\bin\\Rscript.exe\" \"$rScriptFile\" 2>&1";
    $rCommand = "Rscript \"$rScriptFile\" 2>&1";
    exec($rCommand, $output, $return_var);
    //file_put_contents("../plots/maftools_log.txt", implode("\n", $output)); // Log R output for debugging

    // Check if PDF was generated
    if (file_exists($pdfFile)) {
        // Return the file path as JSON
        $webPath = 'plots/' . basename($pdfFile);
        echo json_encode(['pdfFile' => $webPath]);
        //echo json_encode(['pdfFile' => $pdfFile]);
    } else {
        die("Error generating PDF.");
    }

    // Cleanup
    chmod($tsvFile, 0777); // Set the file to be writable
    unlink($tsvFile);

    chmod($rScriptFile, 0777); // Set the file to be writable
    unlink($rScriptFile);
}
?>