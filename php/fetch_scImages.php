<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Check if genes are provided
if (!isset($_POST['selectedGenes']) || empty(trim($_POST['selectedGenes']))) {
    echo json_encode(['error' => 'Gene names are required']);
    exit;
}

// Check if annotation type is provided
if (!isset($_POST['annotationType']) || empty(trim($_POST['annotationType']))) {
    echo json_encode(['error' => 'Annotation type is required']);
    exit;
}

// Check if dataset name is provided
if (!isset($_POST['datasetName']) || empty(trim($_POST['datasetName']))) {
    echo json_encode(['error' => 'Dataset name is required']);
    exit;
}

// Get the new parameters
$tumorOnly = isset($_POST['tumorOnly']) && $_POST['tumorOnly'] === 'true';
$groupBySource = isset($_POST['groupBySource']) && $_POST['groupBySource'] === 'true';

// Split the genes string by commas and clean whitespace
$genes = explode(',', $_POST['selectedGenes']);
$genes = array_map('trim', $genes); // Trim whitespace

// Get the selected annotation type
$annotationType = $_POST['annotationType'];

// Get the selected dataset name
$datasetName = $_POST['datasetName'];

// Validate gene input
if (empty($genes) || count($genes) === 0) {
    echo json_encode(['error' => 'Invalid gene input']);
    exit;
}

// Define a mapping from dataset names to their respective RDS files
$datasetRDSMap = [
    'Data_Bischoff2021_Lung' => '/var/www/Lung_TFDB/files/scrna_seq/bischoff2021_lung.rds',
    'Data_Chan2021_Lung'     => '/var/www/Lung_TFDB/files/scrna_seq/chan2021_lung.rds',
    'Data_Guo2018_Lung'      => '/var/www/Lung_TFDB/files/scrna_seq/guo2018_lung.rds',
    'Data_Kim2020_Lung'      => '/var/www/Lung_TFDB/files/scrna_seq/kim2020_lung.rds',
    'Data_Laughney2020_Lung' => '/var/www/Lung_TFDB/files/scrna_seq/laughney2020_lung.rds',
    'Data_Maynard2020_Lung'  => '/var/www/Lung_TFDB/files/scrna_seq/maynard2020_lung.rds',
    'Data_Qian2020_Lung'     => '/var/www/Lung_TFDB/files/scrna_seq/qian2020_lung.rds',
    'Data_Song2019_Lung'     => '/var/www/Lung_TFDB/files/scrna_seq/song2019_lung.rds',
    'Data_Xing2021_Lung'     => '/var/www/Lung_TFDB/files/scrna_seq/xing2021_lung.rds',
    'Data_Zilionis2019_Lung' => '/var/www/Lung_TFDB/files/scrna_seq/zilionis2019_lung.rds',
    '10xGenomics'            => '/var/www/Lung_TFDB/files/scrna_seq/10xGenomics.rds',
];

// Validate if the provided datasetName exists in our map
if (!array_key_exists($datasetName, $datasetRDSMap)) {
    echo json_encode(['error' => 'Invalid dataset name provided for RDS file.']);
    exit;
}

$rdsFilePath = $datasetRDSMap[$datasetName];

// Determine the actual annotation column to use in R
$rAnnotationColumn = '';
if ($annotationType === 'DefaultAnnotation') {
    $rAnnotationColumn = 'cell_type';
} else {
    $rAnnotationColumn = $annotationType;
}

// Define paths
$rScriptPath = '/var/www/Lung_TFDB/plots/scRNA_plot_script.R'; // Temporary R script location
$logPath = '/var/www/Lung_TFDB/plots/scrna_log.txt'; // Log file path

// Determine optimal thread count based on system load
$cpu_cores = intval(shell_exec('nproc'));
$load = sys_getloadavg();
$current_load = $load[0]; // 1-minute load average
// Calculate available threads: (Cores - Load), ensure at least 1, max cap at 6 to prevent OOM
$worker_count = max(1, min(6, floor($cpu_cores - $current_load)));

// Check for QS file alternative
$qsFilePath = str_replace('.rds', '.qs', $rdsFilePath);
$useQs = file_exists($qsFilePath);
$finalFilePath = $useQs ? $qsFilePath : $rdsFilePath;

// Generate R script dynamically
// Modify the R script generation part
$rScriptContent = "
.libPaths('/home/guruguhan/R/x86_64-pc-linux-gnu-library/4.5')

library(Seurat)
library(ggplot2)
library(jsonlite)
library(readr)
library(base64enc)
library(patchwork) # For combining plots
library(dplyr)     # For data manipulation
library(future)    # For parallel processing
library(qs)        # For fast reading

# Load Seurat object
input_file <- '" . $finalFilePath . "'
if (grepl('\\\\.qs$', input_file)) {
    cat('R Debug: Loading QS file with " . $worker_count . " threads\\n', file = stderr())
    nsclc.seurat.obj <- qread(input_file, nthreads = " . $worker_count . ")
} else {
    cat('R Debug: Loading RDS file\\n', file = stderr())
    nsclc.seurat.obj <- read_rds(input_file)
}

# Create temporary files for base64 encoding
temp_dir <- tempdir()

# Filter for tumor only if requested
tumor_only <- " . ($tumorOnly ? 'TRUE' : 'FALSE') . "
group_by_source <- " . ($groupBySource ? 'TRUE' : 'FALSE') . "

# Initialize sourceplot_base64 as NULL
sourceplot_base64 <- NULL

# Check if source column exists
has_source <- 'source' %in% names(nsclc.seurat.obj@meta.data)

# Debug statements for R variables
cat('R Debug: tumor_only =', tumor_only, '\n', file = stderr())
cat('R Debug: group_by_source =', group_by_source, '\n', file = stderr())
cat('R Debug: has_source =', has_source, '\n', file = stderr())

# Check if 'source' column exists and contains relevant data for filtering
if (tumor_only && has_source) {
    cat('R Debug: Subsetting for Tumor Only with grepl\n', file = stderr())
    # Count rows before subsetting for debugging
    cat('R Debug: Dimensions before subset:', paste(dim(nsclc.seurat.obj), collapse = 'x'), '\n', file = stderr())

    # Use grepl for case-insensitive partial match
    nsclc.seurat.obj <- subset(nsclc.seurat.obj, subset = grepl('tumor|tumour', source, ignore.case = TRUE))

    # Count rows after subsetting for debugging
    cat('R Debug: Dimensions after subset:', paste(dim(nsclc.seurat.obj), collapse = 'x'), '\n', file = stderr())
}

# Set up parallel execution plan
# using multicore which relies on forking (efficient for read-only shared memory)
plan(multicore, workers = " . $worker_count . ")
cat('R Debug: Using " . $worker_count . " workers for parallel plotting\n', file = stderr())

# Define futures for each plot
# DimPlot
f_dimplot <- future({
    dimplot_file <- tempfile(pattern = 'dimplot_', tmpdir = temp_dir, fileext = '.png')
    png(dimplot_file, width = 7.2, height = 6, units = 'in', res = 300)
    print(DimPlot(nsclc.seurat.obj, reduction = 'umap', group.by = '" . $rAnnotationColumn . "', label = TRUE, repel = TRUE))
    invisible(dev.off())
    b64 <- base64encode(dimplot_file)
    unlink(dimplot_file)
    b64
}, seed = TRUE)

# FeaturePlot
f_featureplot <- future({
    featureplot_file <- tempfile(pattern = 'featureplot_', tmpdir = temp_dir, fileext = '.png')
    png(featureplot_file, width = 7.2, height = 6, units = 'in', res = 300)
    print(FeaturePlot(nsclc.seurat.obj, features = c('" . implode("', '", $genes) . "')))
    invisible(dev.off())
    b64 <- base64encode(featureplot_file)
    unlink(featureplot_file)
    b64
}, seed = TRUE)

# VlnPlot
f_vlnplot <- future({
    vlnplot_file <- tempfile(pattern = 'vlnplot_', tmpdir = temp_dir, fileext = '.png')
    png(vlnplot_file, width = 7.2, height = 6, units = 'in', res = 300)
    print(VlnPlot(nsclc.seurat.obj, features = c('" . implode("', '", $genes) . "'), group.by = '" . $rAnnotationColumn . "'))
    invisible(dev.off())
    b64 <- base64encode(vlnplot_file)
    unlink(vlnplot_file)
    b64
}, seed = TRUE)

# DotPlot
f_dotplot <- future({
    dotplot_file <- tempfile(pattern = 'dotplot_', tmpdir = temp_dir, fileext = '.png')
    png(dotplot_file, width = 7.2, height = 6, units = 'in', res = 300)
    print(DotPlot(nsclc.seurat.obj, features = c('" . implode("', '", $genes) . "'), group.by = '" . $rAnnotationColumn . "') + 
        RotatedAxis() +
        scale_color_gradient2(low = 'blue', mid = 'white', high = 'red') +
        theme(axis.text.x = element_text(angle = 45, hjust = 1)))
    invisible(dev.off())
    b64 <- base64encode(dotplot_file)
    unlink(dotplot_file)
    b64
}, seed = TRUE)

# Source plot (conditional)
f_sourceplot <- future({
    b64 <- NULL
    if (group_by_source && has_source) {
        tryCatch({
            sourceplot_file <- tempfile(pattern = 'sourceplot_', tmpdir = temp_dir, fileext = '.png')
            png(sourceplot_file, width = 7.2, height = 6, units = 'in', res = 300)
            print(DimPlot(nsclc.seurat.obj, reduction = 'umap', group.by = 'source', label = FALSE))
            dev.off()

            if (file.exists(sourceplot_file) && file.size(sourceplot_file) > 0) {
                b64 <- base64encode(sourceplot_file)
            }
            unlink(sourceplot_file)
        }, error = function(e) {
            # Handle error silently or return error string? better null
        })
    }
    b64
}, seed = TRUE)

# Collect results from futures
dimplot_base64 <- value(f_dimplot)
featureplot_base64 <- value(f_featureplot)
vlnplot_base64 <- value(f_vlnplot)
dotplot_base64 <- value(f_dotplot)
sourceplot_base64 <- value(f_sourceplot)

# Debug statements
cat('\nDebug: has_source =', has_source, '\n', file = stderr())
cat('Debug: group_by_source =', group_by_source, '\n', file = stderr())
if (!is.null(sourceplot_base64)) {
    cat('Debug: sourceplot generated\n', file = stderr())
} else {
    cat('Debug: sourceplot not generated\n', file = stderr())
}

# Output base64 encoded images as JSON
cat(toJSON(list(
    dimplot = dimplot_base64,
    featureplot = featureplot_base64,
    vlnplot = vlnplot_base64,
    dotplot = dotplot_base64,
    sourceplot = sourceplot_base64
), auto_unbox = TRUE))
";

// Write R script to a temporary file
file_put_contents($rScriptPath, $rScriptContent);

// Run the R script and capture both stdout and stderr
$command = "Rscript \"$rScriptPath\" 2>&1";
$output = shell_exec($command);

// Log the raw output for debugging purposes
file_put_contents($logPath, $output);

// Now, extract only the valid JSON part from the output
if (preg_match('/\{.*\}/s', $output, $matches)) {
    $jsonOutput = $matches[0]; // This should be the JSON string
    echo $jsonOutput;
} else {
    echo json_encode(['error' => 'Failed to generate plots', 'debug' => $output]);
}

// Cleanup
unlink($rScriptPath);
?>