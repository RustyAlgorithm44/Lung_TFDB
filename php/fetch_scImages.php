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

// Split the genes string by commas and clean whitespace
$genes = explode(',', $_POST['selectedGenes']);
$genes = array_map('trim', $genes); // Trim whitespace

// Validate gene input
if (empty($genes) || count($genes) === 0) {
    echo json_encode(['error' => 'Invalid gene input']);
    exit;
}

// Define paths
$rScriptPath = '../plots/scRNA_plot_script.R'; // Temporary R script location
$logPath = '../plots/scrna_log.txt'; // Log file path

// Generate R script dynamically
$rScriptContent = "
 .libPaths('C:/Users/Lung-TFDB/AppData/Local/R/win-library/4.5')
#.libPaths('C:/Users/Guruguhan/AppData/Local/R/win-library/4.4')

library(Seurat)
library(ggplot2)
library(jsonlite)

# Load Seurat object
nsclc.seurat.obj <- readRDS('../files/scrna_seq/nsclc_seurat.rds')

# Create temporary files for base64 encoding
temp_dir <- tempdir()

# DimPlot
dimplot_file <- tempfile(pattern = 'dimplot_', tmpdir = temp_dir, fileext = '.png')
png(dimplot_file, width = 7.2, height = 6, units = 'in', res = 300)
DimPlot(nsclc.seurat.obj, reduction = 'umap', group.by = 'singleR.labels', label = TRUE, repel = TRUE)
invisible(dev.off())
dimplot_base64 <- base64enc::base64encode(dimplot_file)
unlink(dimplot_file)

# FeaturePlot
featureplot_file <- tempfile(pattern = 'featureplot_', tmpdir = temp_dir, fileext = '.png')
png(featureplot_file, width = 7.2, height = 6, units = 'in', res = 300)
FeaturePlot(nsclc.seurat.obj, features = c('" . implode("', '", $genes) . "'))
invisible(dev.off())
featureplot_base64 <- base64enc::base64encode(featureplot_file)
unlink(featureplot_file)

# VlnPlot
vlnplot_file <- tempfile(pattern = 'vlnplot_', tmpdir = temp_dir, fileext = '.png')
png(vlnplot_file, width = 7.2, height = 6, units = 'in', res = 300)
VlnPlot(nsclc.seurat.obj, features = c('" . implode("', '", $genes) . "'), group.by = 'singleR.labels')
invisible(dev.off())
vlnplot_base64 <- base64enc::base64encode(vlnplot_file)
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