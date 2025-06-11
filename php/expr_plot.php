<?php
set_time_limit(10); // Set maximum execution time to 10 seconds

require 'db_connect.php';

ini_set('memory_limit', '900M');

// Fetch POST data and validate it
$dataset = isset($_POST['dataset']) ? $_POST['dataset'] : '';
$geneName = isset($_POST['geneName']) ? $_POST['geneName'] : '';

$jitterEnabled = isset($_POST['addJitter']) ? 'TRUE' : 'FALSE';
$jitterColor = isset($_POST['jitterColor']) ? $_POST['jitterColor'] : '#000000';
$statTest = $_POST['statTest'] ?? 't.test';

if (empty($dataset) || empty($geneName)) {
    die("All input fields are required.");
}

// Determine the tables based on cancer type
switch ($dataset) {
    case 'adeno':
        $rnaTable = 'adeno_rna_seq';
        $normalTable = 'adeno_rna_seq_normal';
        break;
    case 'squam':
        $rnaTable = 'squam_rna_seq';
        $normalTable = 'squam_rna_seq_normal';
        break;
    case 'gse102287':
        $rnaTable = 'gse102287_tumor_exp';
        $normalTable = 'gse102287_normal_exp';
        break;
    case 'gse19188':
        $rnaTable = 'gse19188_tumor_exp';
        $normalTable = 'gse19188_normal_exp';
        break;
    case 'all':
        $rnaTable = 'batch_corrected_tumor_data';
        $normalTable = 'batch_corrected_normal_data';
        break;    
}

// Fetch gene expression data
$sql = "SELECT * FROM $rnaTable WHERE Hugo_Symbol = '$geneName'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("No data found for the specified gene in tumor table.");
}

$tumorData = $result->fetch_assoc();
unset($tumorData['Hugo_Symbol']); // Remove gene symbol column

$sql = "SELECT * FROM $normalTable WHERE Hugo_Symbol = '$geneName'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("No data found for the specified gene in normal table.");
}

$normalData = $result->fetch_assoc();
unset($normalData['Hugo_Symbol']); // Remove gene symbol column

// Create a temporary directory for this request
$tempDir = sys_get_temp_dir() . '/expr_plots_' . uniqid();
mkdir($tempDir, 0777, true);

// Prepare data for R script
$dataFile = $tempDir . '/data.csv';
$file = fopen($dataFile, 'w');
fputcsv($file, array('type', 'sample', 'expression'));
foreach ($tumorData as $sample => $expression) {
    fputcsv($file, array('Tumor', $sample, $expression));
}
foreach ($normalData as $sample => $expression) {
    fputcsv($file, array('Normal', $sample, $expression));
}
fclose($file);

// Count samples
$tumorCount = count($tumorData);
$normalCount = count($normalData);

// Path to R script
$rScript = $tempDir . '/plot_expr.R';
$violinImage = $tempDir . '/violin.png';
$boxImage = $tempDir . '/box.png';

$rCode = "
.libPaths('C:/Users/Lung-TFDB/AppData/Local/R/win-library/4.5')
args <- commandArgs(trailingOnly = TRUE)
data_file <- args[1]
violin_image <- args[2]
box_image <- args[3]
tumor_count <- as.numeric(args[4])
normal_count <- as.numeric(args[5])
add_jitter <- as.logical(args[6])  # new jitter option
jitter_color <- args[7]  # color for jitter
stat_test <- args[8]  # statistical test choice

library(ggplot2)
library(ggpubr)

data <- read.csv(data_file)

# Calculate the max expression value
max_expression <- max(data\$expression, na.rm = TRUE)

mean_tumor <- mean(data\$expression[data\$type == 'Tumor'])
mean_normal <- mean(data\$expression[data\$type == 'Normal'])
fold_change <- mean_tumor / mean_normal
fold_change_label <- paste('Fold Change:', round(fold_change, 2))

# Create custom labels with sample counts
tumor_label <- paste('Tumor (n=', tumor_count, ')', sep = '')
normal_label <- paste('Normal (n=', normal_count, ')', sep = '')
legend_labels <- c('Tumor' = tumor_label, 'Normal' = normal_label)

stat_method <- ifelse(stat_test == 'kruskal.test', 'kruskal.test', 't.test')

# Violin plot
violin_plot <- ggplot(data, aes(x = type, y = expression, fill = type)) + 
  geom_violin(trim = FALSE, alpha = 0.7) +
  scale_fill_manual(values = c('#66CCCC', '#FF9999'), labels = legend_labels) +  
  labs(x = NULL, y = 'Expression', fill = 'Type') +
  theme_minimal() +
  theme(legend.position = 'bottom', legend.title = element_text(size = 10), legend.text = element_text(size = 8),
        axis.text.x = element_blank(), axis.text.y = element_text(size = 10), 
        axis.title.x = element_text(size = 12), axis.title.y = element_text(size = 12),
        plot.title = element_text(hjust = 0.5, size = 16)) +
  stat_compare_means(method = stat_method, label.x = 1.18, label.y = max_expression + 0.06 * max_expression, size = 3.5) +
  annotate('text', x = 2.33, y = max_expression + 0.015 * max_expression, 
           label = fold_change_label, size = 3.5, color = 'black')

# Add jitter if selected by the user
if (add_jitter) {
  violin_plot <- violin_plot + geom_jitter(width = 0.2, alpha = 0.3, color = jitter_color)
}

ggsave(violin_image, plot = violin_plot, width = 5.5, height = 4, bg = 'white')

# Box plot
box_plot <- ggplot(data, aes(x = type, y = expression, fill = type)) +
  geom_boxplot(alpha = 0.7, color = 'black', size = 0.5, position = position_dodge(width = 0.75)) +
  scale_fill_manual(values = c('#66CCCC', '#FF9999'), labels = legend_labels) +
  labs(x = NULL, y = 'Expression', fill = 'Type') +
  theme_minimal() +
  theme(legend.position = 'bottom', legend.title = element_text(size = 10), legend.text = element_text(size = 8),
        axis.text.x = element_blank(), axis.text.y = element_text(size = 10), 
        axis.title.x = element_text(size = 12), axis.title.y = element_text(size = 12),
        plot.title = element_text(hjust = 0.5, size = 16)) +
  stat_compare_means(method = stat_method, label.x = 1.18, label.y = max_expression + 0.06 * max_expression, size = 3.5) + 
  annotate('text', x = 2.33, y = max_expression + 0.02 * max_expression, 
           label = fold_change_label, size = 3.5, color = 'black')

# Add jitter to box plot if selected
if (add_jitter) {
  box_plot <- box_plot + geom_jitter(width = 0.2, alpha = 0.3, color = jitter_color)
}

ggsave(box_image, plot = box_plot, width = 5.5, height = 4, bg = 'white')
";
file_put_contents($rScript, $rCode);

// Execute R script
$command = "Rscript \"$rScript\" \"$dataFile\" \"$violinImage\" \"$boxImage\" $tumorCount $normalCount $jitterEnabled \"$jitterColor\" \"$statTest\"";
//$command = "Rscript \" $rScript $dataFile $violinImage $boxImage $tumorCount $normalCount $jitterEnabled $jitterColor $statTest";
//$rCommand = "Rscript \"$rScriptFile\" 2>&1";
exec($command, $output, $return_var);
//file_put_contents("../plots/expr_log.txt", implode("\n", $output)); // Log R output for debugging

if ($return_var != 0) {
    // Clean up temp files before dying
    array_map('unlink', glob("$tempDir/*"));
    rmdir($tempDir);
    die("Error generating the plot.");
}

// Read the generated images
$violinImageData = file_get_contents($violinImage);
$boxImageData = file_get_contents($boxImage);

// Convert to base64
$violinBase64 = base64_encode($violinImageData);
$boxBase64 = base64_encode($boxImageData);

// Clean up temp files
array_map('unlink', glob("$tempDir/*"));
rmdir($tempDir);

// Return the images with download links that use the base64 data
echo "<div class='plot-container'>";
echo "<div class='plot'><h3>Violin Plot</h3>";
echo "<a href='data:image/png;base64,$violinBase64' download='violin_plot_$geneName.png'><img src='data:image/png;base64,$violinBase64' alt='Violin Plot'></a>";
echo "</div>";
echo "<div class='plot'><h3>Box Plot</h3>";
echo "<a href='data:image/png;base64,$boxBase64' download='box_plot_$geneName.png'><img src='data:image/png;base64,$boxBase64' alt='Box Plot'></a>";
echo "</div>";
echo "</div>";

$conn->close();
?>