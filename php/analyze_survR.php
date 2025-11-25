<?php
set_time_limit(300); // Increase time limit for R execution

// Error reporting for debugging (can be turned off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db_connect.php';
ini_set('memory_limit', '900M');

// --- Helper Functions ---

function log_message($message) {
    $log_file = '../plots/R_analysis.log';
    file_put_contents($log_file, date('[Y-m-d H:i:s]') . " " . $message . "\n", FILE_APPEND);
}

function fetchRows($conn, $query) {
    $result = $conn->query($query);
    if (!$result) {
        log_message("Query failed: " . $conn->error);
        die("Query failed: " . $conn->error);
    }
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $result->free();
    return $rows;
}

function median($values) {
    sort($values);
    $count = count($values);
    $middle = floor(($count - 1) / 2);
    if ($count % 2) {
        return $values[$middle];
    } else {
        return ($values[$middle] + $values[$middle + 1]) / 2.0;
    }
}

// --- Input Handling ---

$dataset = isset($_POST['dataset']) ? $_POST['dataset'] : '';
$geneName = isset($_POST['geneName']) ? strtoupper($_POST['geneName']) : ''; // Ensure gene name is uppercase
$analysisType = isset($_POST['analysisType']) ? $_POST['analysisType'] : '';

log_message("New Analysis Request: Dataset=$dataset, Gene=$geneName, Type=$analysisType");

if (empty($dataset) || empty($geneName) || empty($analysisType)) {
    die("All input fields are required.");
}

// --- Dataset Configuration ---
// Copying switch-case logic from analyze_surv.php

switch ($dataset) {
    case 'adeno':
        $rnaTable = 'adeno_rna_seq';
        $clinTable = 'adeno_clin';
        $mutTable = 'adeno_mutated';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'DSS_MONTHS', 'DSS_STATUS', 'DFS_MONTHS', 'DFS_STATUS', 'PFS_MONTHS', 'PFS_STATUS'];
        break;
    case 'squam':
        $rnaTable = 'squam_rna_seq';
        $clinTable = 'squam_clin';
        $mutTable = 'squam_mutated';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'DSS_MONTHS', 'DSS_STATUS', 'DFS_MONTHS', 'DFS_STATUS', 'PFS_MONTHS', 'PFS_STATUS'];
        break;
    case 'oncosg':
        $rnaTable = 'oncosg_rna_seq';
        $clinTable = 'oncosg_clin';
        $mutTable = 'oncosg_mutated';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
        break;
    case 'mskcc':
        // $rnaTable = '?'; // Not available
        $clinTable = 'mskcc_clin';
        $mutTable = 'mskcc_mutated';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
        break;
    case 'mskcc2020':
        // $rnaTable = '?'; // Not available
        $clinTable = 'mskcc2020_clin';
        $mutTable = 'mskcc2020_mutated';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_MONTHS', 'RFS_STATUS'];
        break;
    case 'mskcc2022_adeno':
        // $rnaTable = '?'; // Not available
        $clinTable = 'mskcc2022_adeno_clin';
        $mutTable = 'mskcc2022_mutated';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
        break;
    case 'mskcc2022_squam':
        // $rnaTable = '?'; // Not available
        $clinTable = 'mskcc2022_squam_clin';
        $mutTable = 'mskcc2022_mutated';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
        break;
    case 'gse102287':
        $rnaTable = 'gse102287_tumor_exp';
        $clinTable = 'gse102287_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS'];
        break;
    case 'gse14814_adeno':
        $rnaTable = 'gse14814_adeno_exp';
        $clinTable = 'gse14814_adeno_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS', 'DSS_MONTHS', 'DSS_STATUS'];
        break;
    case 'gse14814_squam':
        $rnaTable = 'gse14814_squam_exp';
        $clinTable = 'gse14814_squam_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS', 'DSS_MONTHS', 'DSS_STATUS'];
        break;
    case 'gse157011':
        $rnaTable = 'gse157011_exp';
        $clinTable = 'gse157011_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS'];
        break;
    case 'gse19188_adeno':
        $rnaTable = 'gse19188_adeno_exp';
        $clinTable = 'gse19188_adeno_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS'];
        break;
    case 'gse19188_squam':
        $rnaTable = 'gse19188_squam_exp';
        $clinTable = 'gse19188_squam_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS'];
        break;
    case 'gse29013_adeno':
        $rnaTable = 'gse29013_adeno_exp';
        $clinTable = 'gse29013_adeno_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS', 'PFS_MONTHS', 'PFS_STATUS'];
        break;
    case 'gse29013_squam':
        $rnaTable = 'gse29013_squam_exp';
        $clinTable = 'gse29013_squam_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS', 'PFS_MONTHS', 'PFS_STATUS'];
        break;
    case 'gse30219_adeno':
        $rnaTable = 'gse30219_adeno_exp';
        $clinTable = 'gse30219_adeno_clin';
        // $mutTable = '?'; // Not available
        $columns = ['DFS_MONTHS', 'DFS_STATUS'];
        break;
    case 'gse30219_squam':
        $rnaTable = 'gse30219_squam_exp';
        $clinTable = 'gse30219_squam_clin';
        // $mutTable = '?'; // Not available
        $columns = ['DFS_MONTHS', 'DFS_STATUS'];
        break;
    case 'gse31210':
        $rnaTable = 'gse31210_exp';
        $clinTable = 'gse31210_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_STATUS', 'RFS_MONTHS'];
        break;
    case 'gse3141_adeno':
        $rnaTable = 'gse3141_adeno_exp';
        $clinTable = 'gse3141_adeno_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS'];
        break;
    case 'gse3141_squam':
        $rnaTable = 'gse3141_squam_exp';
        $clinTable = 'gse3141_squam_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS'];
        break;
    case 'gse37745_adeno':
        $rnaTable = 'gse37745_adeno_exp';
        $clinTable = 'gse37745_adeno_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_STATUS', 'RFS_MONTHS'];
        break;
    case 'gse37745_squam':
        $rnaTable = 'gse37745_squam_exp';
        $clinTable = 'gse37745_squam_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_STATUS', 'RFS_MONTHS'];
        break;
    case 'gse4573':
        $rnaTable = 'gse4573_exp';
        $clinTable = 'gse4573_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS'];
        break;
    case 'gse50081_adeno':
        $rnaTable = 'gse50081_adeno_exp';
        $clinTable = 'gse50081_adeno_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_MONTHS', 'RFS_STATUS'];
        break;
    case 'gse50081_squam':
        $rnaTable = 'gse50081_squam_exp';
        $clinTable = 'gse50081_squam_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_MONTHS', 'RFS_STATUS'];
        break;
    case 'gse68465':
        $rnaTable = 'gse68465_exp';
        $clinTable = 'gse68465_clin';
        // $mutTable = '?'; // Not available
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_MONTHS', 'RFS_STATUS'];
        break;
    case 'all':
        $rnaTable = 'all_exp';
        $clinTable = 'all_clin';
        $mutTable = 'all_mutated';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'DSS_MONTHS', 'DSS_STATUS', 'DFS_MONTHS', 'DFS_STATUS', 'PFS_MONTHS', 'PFS_STATUS', 'RFS_MONTHS', 'RFS_STATUS'];
        break;
    default:
        die("Invalid cancer type specified.");
}

// --- Data Preparation ---

$unique_id = uniqid();
$data_for_r_path = "../plots/{$unique_id}_data.csv";

if ($analysisType === 'expr') {
    if (!isset($rnaTable)) {
        die("Expression data not available for this dataset.");
    }
    
    // Fetch Expression Data
    $queryRNA = "SELECT * FROM $rnaTable WHERE Hugo_Symbol = '$geneName'";
    $dataRNA = fetchRows($conn, $queryRNA);

    if (empty($dataRNA)) {
        die("Gene name $geneName not found in Expression data.");
    }

    $rowRNA = $dataRNA[0];
    unset($rowRNA['Hugo_Symbol']);
    $values = array_values($rowRNA);
    $medianValue = median($values);

    // Split into High/Low
    $expression_groups = [];
    foreach ($rowRNA as $patientId => $value) {
        $cleanId = substr(str_replace('-', '.', $patientId), 0, 12);
        $expression_groups[$cleanId] = ($value > $medianValue) ? 'High' : 'Low';
    }

    // Fetch Clinical Data
    $columnsStr = implode(', ', array_merge(['ids'], $columns));
    $queryClin = "SELECT $columnsStr FROM $clinTable";
    $dataClin = fetchRows($conn, $queryClin);

    // Merge Data
    $final_data = [];
    foreach ($dataClin as $clin_row) {
        $clin_id = str_replace('-', '.', $clin_row['ids']);
        // Try exact match or substring match
        if (isset($expression_groups[$clin_id])) {
             $final_data[] = array_merge($clin_row, ['group' => $expression_groups[$clin_id]]);
        }
    }

} elseif ($analysisType === 'mut') {
    if (!isset($mutTable)) {
        die("Mutation data not available for this dataset.");
    }

    // Fetch Mutation Data
    $queryMut = "SELECT * FROM $mutTable WHERE Hugo_Symbol = '$geneName'";
    $dataMut = fetchRows($conn, $queryMut);

    $mutSamples = [];
    foreach ($dataMut as $sample) {
        $cleanId = substr(str_replace('-', '.', $sample['Tumor_Sample_Barcode']), 0, 12);
        $mutSamples[$cleanId] = 'Mutated';
    }

    // Fetch Clinical Data
    $columnsStr = implode(', ', array_merge(['ids'], $columns));
    $queryClin = "SELECT $columnsStr FROM $clinTable";
    $dataClin = fetchRows($conn, $queryClin);

    // Merge Data
    $final_data = [];
    foreach ($dataClin as $clin_row) {
        $clin_id = str_replace('-', '.', $clin_row['ids']);
        $group = isset($mutSamples[$clin_id]) ? 'Mutated' : 'Non-mutated';
        $final_data[] = array_merge($clin_row, ['group' => $group]);
    }
}

if (empty($final_data)) {
    die("No overlapping data found between clinical and genomic data.");
}

// Write to CSV
$fp = fopen($data_for_r_path, 'w');
fputcsv($fp, array_keys($final_data[0]));
foreach ($final_data as $fields) {
    fputcsv($fp, $fields);
}
fclose($fp);
log_message("Data written to $data_for_r_path");


// --- R Script Generation ---

$r_script_path = "../plots/{$unique_id}_script.R";

// Define plot types to generate based on columns available
$plot_types = [];
if (in_array('OS_MONTHS', $columns)) $plot_types['OS'] = ['time' => 'OS_MONTHS', 'status' => 'OS_STATUS'];
if (in_array('DSS_MONTHS', $columns)) $plot_types['DSS'] = ['time' => 'DSS_MONTHS', 'status' => 'DSS_STATUS'];
if (in_array('DFS_MONTHS', $columns)) $plot_types['DFS'] = ['time' => 'DFS_MONTHS', 'status' => 'DFS_STATUS'];
if (in_array('PFS_MONTHS', $columns)) $plot_types['PFS'] = ['time' => 'PFS_MONTHS', 'status' => 'PFS_STATUS'];
if (in_array('RFS_MONTHS', $columns)) $plot_types['RFS'] = ['time' => 'RFS_MONTHS', 'status' => 'RFS_STATUS'];

// Special cases for specific datasets (mirroring analyze_surv.php logic)
if ($dataset === 'gse30219_adeno' || $dataset === 'gse30219_squam') {
    $plot_types = ['DFS' => ['time' => 'DFS_MONTHS', 'status' => 'DFS_STATUS']];
}

$r_code = '
.libPaths(c("C:/Users/Lung-TFDB/AppData/Local/R/win-library/4.5", "C:/Users/Guruguhan/AppData/Local/R/win-library/4.5"))
library(survival)
library(survminer)
library(ggplot2)

data <- read.csv("' . $data_for_r_path . '", stringsAsFactors = FALSE, check.names = FALSE)

# Function to run analysis and plot
run_analysis <- function(time_col, status_col, type, output_file) {
    
    # Filter missing data
    sub_data <- data[!is.na(data[[time_col]]) & !is.na(data[[status_col]]), ]
    
    print(paste("Processing type:", type))
    print(paste("Time col:", time_col, "Status col:", status_col))
    print("Class of sub_data:")
    print(class(sub_data))
    print("Unique status values before processing:")
    print(unique(sub_data[[status_col]]))
    
    # Ensure status is numeric (0/1)
    # Handle cases where status might be "1:DECEASED" etc
    if (is.character(sub_data[[status_col]]) || is.factor(sub_data[[status_col]])) {
        sub_data[[status_col]] <- ifelse(grepl("1", as.character(sub_data[[status_col]])), 1, 0)
    }
    sub_data[[status_col]] <- as.numeric(as.character(sub_data[[status_col]]))
    print("Status conversion done")

    if(nrow(sub_data) < 5) { return(NULL) }

    # Set factor levels
    if ("' . $analysisType . '" == "mut") {
        sub_data$group <- factor(sub_data$group, levels = c("Non-mutated", "Mutated"))
    } else {
        sub_data$group <- factor(sub_data$group, levels = c("Low", "High"))
    }
    print("Factor levels set")

    formula <- as.formula(paste("Surv(", time_col, ", ", status_col, ") ~ group"))
    print(paste("Formula:", paste("Surv(", time_col, ", ", status_col, ") ~ group")))
    
    #title <- paste("Survival Analysis (' . $geneName . ' - ", type, ")", sep="")
    #title <- paste("Survival Analysis (' . $geneName . ')", sep="")
    title <- ""

    fit <- survfit(formula, data = sub_data)
    # Workaround for ggsurvplot scoping issue
    fit$call$data <- quote(sub_data)
    fit$call$formula <- formula
    
    print("Survfit done")
    
    # Calculate P-value
    diff <- survdiff(formula, data = sub_data)
    pval <- 1 - pchisq(diff$chisq, length(diff$n) - 1)
    pval_txt <- ifelse(pval < 0.001, "p < 0.001", paste("p =", round(pval, 3)))
    print("P-value calculated")

    # Plot
    png(output_file, width = 8, height = 6.5, units = "in", res = 300)
    print("Starting ggsurvplot")
    p <- ggsurvplot(
    fit,
    data = sub_data,
    pval = TRUE,
    conf.int = TRUE,
    #risk.table = TRUE,
    #risk.table.col = "strata",  # Color by strata
    #risk.table.height = 0.05,   # Adjust height as needed
    #risk.table.y.text = FALSE,  # Remove y-axis text for cleaner look
    ggtheme = theme_bw(),
    palette = c("#E7B800", "#2E9FDF"),
    title = title,
    legend.title = "' . $geneName . '",
    legend.labs = levels(sub_data$group),
    # Fix for the unknown labels warning:
    risk.table.title = "Number at risk"
    )

    print("ggsurvplot object created")
    print(p)
    print("Plot printed")
    dev.off()
}
';

// Add calls to run_analysis for each plot type
foreach ($plot_types as $type => $cols) {
    $output_file = "../plots/{$unique_id}_{$type}.png";
    // Escape backslashes for R string
    $output_file_r = str_replace('\\', '/', $output_file);
    
    $r_code .= '
tryCatch({
    run_analysis("' . $cols['time'] . '", "' . $cols['status'] . '", "' . $type . '", "' . $output_file_r . '")
}, error = function(e) {
    print(paste("Error processing ' . $type . ':", e$message))
})
';
}

file_put_contents($r_script_path, $r_code);
log_message("R script generated at $r_script_path");

// --- Execution ---

$command = "Rscript \"$r_script_path\" 2>&1";
log_message("Executing: $command");
$output = shell_exec($command);
log_message("R Output: " . $output);

// --- Output Display ---

echo "<div class='plot-container'>";
$plots_found = false;

foreach ($plot_types as $type => $cols) {
    $plot_file = "../plots/{$unique_id}_{$type}.png";
    
    if (file_exists($plot_file)) {
        $plots_found = true;
        $imageData = file_get_contents($plot_file);
        $base64Image = base64_encode($imageData);
        $fileName = "{$geneName}_{$dataset}_{$type}.png";

        echo "<div class='plot'>";
        echo "<h3>Survival Analysis ($type)</h3>";
        echo "<a href='data:image/png;base64,$base64Image' download='$fileName'>";
        echo "<img src='data:image/png;base64,$base64Image' alt='Survival Plot $type'>";
        echo "</a>";
        echo "</div>";

        unlink($plot_file);
    }
}
echo "</div>";

if (!$plots_found) {
    echo "<div class='data-message'>No plots were generated. This might be due to insufficient data or an error in analysis.</div>";
    echo "<pre style='display:none'>$output</pre>"; // Hidden debug info
}

// Cleanup
if (file_exists($data_for_r_path)) unlink($data_for_r_path);
if (file_exists($r_script_path)) unlink($r_script_path);

$conn->close();
?>