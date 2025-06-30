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
    'Data_Bischoff2021_Lung' => '../files/scrna_seq/bischoff2021_lung.rds',
    'Data_Chan2021_Lung'     => '../files/scrna_seq/chan2021_lung.rds',
    'Data_Guo2018_Lung'      => '../files/scrna_seq/guo2018_lung.rds',
    'Data_Kim2020_Lung'      => '../files/scrna_seq/kim2020_lung.rds',
    'Data_Laughney2020_Lung' => '../files/scrna_seq/laughney2020_lung.rds',
    'Data_Maynard2020_Lung'  => '../files/scrna_seq/maynard2020_lung.rds',
    'Data_Qian2020_Lung'     => '../files/scrna_seq/qian2020_lung.rds',
    'Data_Song2019_Lung'     => '../files/scrna_seq/song2019_lung.rds',
    'Data_Xing2021_Lung'     => '../files/scrna_seq/xing2021_lung.rds',
    'Data_Zilionis2019_Lung' => '../files/scrna_seq/zilionis2019_lung.rds',
    '10xGenomics'            => '../files/scrna_seq/10xGenomics.rds',
    // Add more datasets as needed
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
    // For "Default annotation", you need to know the default column name in your Seurat object.
    $rAnnotationColumn = 'cell_type';
} else {
    $rAnnotationColumn = $annotationType;
}

// Define paths
$rScriptPath = '../plots/scRNA_plot_script.R'; // Temporary R script location
$logPath = '../plots/scrna_log.txt'; // Log file path

// Generate R script dynamically
$rScriptContent = "
.libPaths('C:/Users/Lung-TFDB/AppData/Local/R/win-library/4.5')
# .libPaths('C:/Users/Guruguhan/AppData/Local/R/win-library/4.4')

library(Seurat)
library(ggplot2)
library(jsonlite)
library(readr)
library(base64enc) # Ensure this library is loaded for base64 encoding

# Load Seurat object
nsclc.seurat.obj <- read_rds('" . $rdsFilePath . "')

# Create temporary files for base64 encoding
temp_dir <- tempdir()

# DimPlot
dimplot_file <- tempfile(pattern = 'dimplot_', tmpdir = temp_dir, fileext = '.png')
png(dimplot_file, width = 7.2, height = 6, units = 'in', res = 300)
DimPlot(nsclc.seurat.obj, reduction = 'umap', group.by = '" . $rAnnotationColumn . "', label = TRUE, repel = TRUE)
invisible(dev.off())
dimplot_base64 <- base64encode(dimplot_file)
unlink(dimplot_file)

# FeaturePlot
featureplot_file <- tempfile(pattern = 'featureplot_', tmpdir = temp_dir, fileext = '.png')
png(featureplot_file, width = 7.2, height = 6, units = 'in', res = 300)
FeaturePlot(nsclc.seurat.obj, features = c('" . implode("', '", $genes) . "'))
invisible(dev.off())
featureplot_base64 <- base64encode(featureplot_file)
unlink(featureplot_file)

# VlnPlot
vlnplot_file <- tempfile(pattern = 'vlnplot_', tmpdir = temp_dir, fileext = '.png')
png(vlnplot_file, width = 7.2, height = 6, units = 'in', res = 300)
VlnPlot(nsclc.seurat.obj, features = c('" . implode("', '", $genes) . "'), group.by = '" . $rAnnotationColumn . "')
invisible(dev.off())
vlnplot_base64 <- base64encode(vlnplot_file)
unlink(vlnplot_file)

# Output base64 encoded images as JSON
cat(toJSON(list(
    dimplot = dimplot_base64,
    featureplot = featureplot_base64,
    vlnplot = vlnplot_base64
), auto_unbox = TRUE))
";

// Write R script to a temporary file
file_put_contents($rScriptPath, $rScriptContent);

// Run the R script and capture both stdout and stderr
$command = "Rscript \"$rScriptPath\" 2>&1";
$output = shell_exec($command);

// Log the raw output for debugging purposes
file_put_contents($logPath, $output); // Log R output for debugging

// Now, extract only the valid JSON part from the output
if (preg_match('/\{.*\}/s', $output, $matches)) {
    $jsonOutput = $matches[0]; // This should be the JSON string
    echo $jsonOutput; // Echo only the JSON part
} else {
    echo json_encode(['error' => 'Failed to generate plots', 'debug' => $output]);
}

// Cleanup
unlink($rScriptPath); // Remove temporary R script
?>