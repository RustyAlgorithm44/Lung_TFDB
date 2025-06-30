<?php
header('Content-Type: application/json');

// Check if datasetName is provided
if (!isset($_GET['datasetName']) || empty(trim($_GET['datasetName']))) {
    echo json_encode(['error' => 'Dataset name is required']);
    exit;
}

$datasetName = $_GET['datasetName'];

// Define a mapping from dataset names to their respective gene CSV files
$datasetGeneMap = [
    'Data_Song2019_Lung' => '../files/scrna_seq/genes_song2019_lung.csv',
    'Data_Bischoff2021_Lung' => '../files/scrna_seq/genes_bischoff2021_lung.csv',
    'Data_Chan2021_Lung' => '../files/scrna_seq/genes_chan2021_lung.csv',
    'Data_Guo2018_Lung' => '../files/scrna_seq/genes_guo2018_lung.csv',
    'Data_Kim2020_Lung' => '../files/scrna_seq/genes_kim2020_lung.csv',
    'Data_Laughney2020_Lung' => '../files/scrna_seq/genes_laughney2020_lung.csv',
    'Data_Maynard2020_Lung' => '../files/scrna_seq/genes_maynard2020_lung.csv',
    'Data_Qian2020_Lung' => '../files/scrna_seq/genes_qian2020_lung.csv',
    'Data_Xing2021_Lung' => '../files/scrna_seq/genes_xing2021_lung.csv',
    'Data_Zilionis2019_Lung' => '../files/scrna_seq/genes_zilionis2019_lung.csv',
    '10xGenomics' => '../files/scrna_seq/genes_10xGenomics.csv',
    // Add more datasets as needed
];

// Validate if the provided datasetName exists in our map
if (!array_key_exists($datasetName, $datasetGeneMap)) {
    echo json_encode(['error' => 'Invalid dataset name provided']);
    exit;
}

$filePath = $datasetGeneMap[$datasetName];

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
} else {
    echo json_encode(['error' => 'Could not open gene file for the selected dataset.']);
    exit;
}

// Sort the genes array in ascending order
sort($genes);

// Output the result as JSON
echo json_encode($genes);
?>
