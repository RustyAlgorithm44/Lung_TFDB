<?php
set_time_limit(10); // Set maximum execution time to 10 seconds

error_reporting(E_ERROR | E_PARSE); // Report simple running errors
error_reporting(0); // Turn off all error reporting

require 'db_connect.php';

ini_set('memory_limit', '900M');

// Fetch POST data and validate it
$dataset = isset($_POST['dataset']) ? $_POST['dataset'] : '';
$geneName = isset($_POST['geneName']) ? $_POST['geneName'] : '';
$analysisType = isset($_POST['analysisType']) ? $_POST['analysisType'] : '';

if (empty($dataset) || empty($geneName) || empty($analysisType)) {
    die("All input fields are required.");
}

// Determine the tables based on cancer type
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
#       $rnaTable = '?';
        $clinTable = 'mskcc_clin';
        $mutTable = 'mskcc_mutated';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
		break;
	case 'mskcc2020':
#       $rnaTable = '?';
        $clinTable = 'mskcc2020_clin';
        $mutTable = 'mskcc2020_mutated';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_MONTHS', 'RFS_STATUS'];
		break;
	case 'mskcc2022_adeno':
#       $rnaTable = '?';
        $clinTable = 'mskcc2022_adeno_clin';
        $mutTable = 'mskcc2022_mutated';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
		break;
	case 'mskcc2022_squam':
#       $rnaTable = '?';
        $clinTable = 'mskcc2022_squam_clin';
        $mutTable = 'mskcc2022_mutated';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
		break;
	case 'gse102287':
        $rnaTable = 'gse102287_tumor_exp';
        $clinTable = 'gse102287_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
		break;
	case 'gse14814_adeno':
        $rnaTable = 'gse14814_adeno_exp';
        $clinTable = 'gse14814_adeno_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'DSS_MONTHS', 'DSS_STATUS'];
		break;
	case 'gse14814_squam':
        $rnaTable = 'gse14814_squam_exp';
        $clinTable = 'gse14814_squam_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'DSS_MONTHS', 'DSS_STATUS'];
		break;
	case 'gse157011':
        $rnaTable = 'gse157011_exp';
        $clinTable = 'gse157011_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
		break;
	case 'gse19188_adeno':
        $rnaTable = 'gse19188_adeno_exp';
        $clinTable = 'gse19188_adeno_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
		break;
	case 'gse19188_squam':
        $rnaTable = 'gse19188_squam_exp';
        $clinTable = 'gse19188_squam_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
		break;
	case 'gse29013_adeno':
        $rnaTable = 'gse29013_adeno_exp';
        $clinTable = 'gse29013_adeno_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'PFS_MONTHS', 'PFS_STATUS'];
		break;
	case 'gse29013_squam':
        $rnaTable = 'gse29013_squam_exp';
        $clinTable = 'gse29013_squam_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'PFS_MONTHS', 'PFS_STATUS'];
		break;
	case 'gse30219_adeno':
        $rnaTable = 'gse30219_adeno_exp';
        $clinTable = 'gse30219_adeno_clin';
#       $mutTable = '?';
        $columns = ['DFS_MONTHS', 'DFS_STATUS'];
		break;
	case 'gse30219_squam':
        $rnaTable = 'gse30219_squam_exp';
        $clinTable = 'gse30219_squam_clin';
#       $mutTable = '?';
        $columns = ['DFS_MONTHS', 'DFS_STATUS'];
		break;
	case 'gse31210':
        $rnaTable = 'gse31210_exp';
        $clinTable = 'gse31210_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_STATUS', 'RFS_MONTHS'];
		break;
	case 'gse3141_adeno':
        $rnaTable = 'gse3141_adeno_exp';
        $clinTable = 'gse3141_adeno_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
		break;
	case 'gse3141_squam':
        $rnaTable = 'gse3141_squam_exp';
        $clinTable = 'gse3141_squam_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
		break;
	case 'gse37745_adeno':
        $rnaTable = 'gse37745_adeno_exp';
        $clinTable = 'gse37745_adeno_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_STATUS', 'RFS_MONTHS'];
		break;
	case 'gse37745_squam':
        $rnaTable = 'gse37745_squam_exp';
        $clinTable = 'gse37745_squam_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_STATUS', 'RFS_MONTHS'];
		break;
	case 'gse4573':
        $rnaTable = 'gse4573_exp';
        $clinTable = 'gse4573_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS'];
		break;
	case 'gse50081_adeno':
        $rnaTable = 'gse50081_adeno_exp';
        $clinTable = 'gse50081_adeno_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_MONTHS', 'RFS_STATUS'];
		break;
	case 'gse50081_squam':
        $rnaTable = 'gse50081_squam_exp';
        $clinTable = 'gse50081_squam_clin';
#       $mutTable = '?';
        $columns = ['OS_MONTHS', 'OS_STATUS', 'RFS_MONTHS', 'RFS_STATUS'];
		break;
	case 'gse68465':
        $rnaTable = 'gse68465_exp';
        $clinTable = 'gse68465_clin';
#       $mutTable = '?';
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

function fetchRows($conn, $query) {
    $result = $conn->query($query);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $result->free();
    return $rows;
}

if ($analysisType === 'expr') {
    // Fetch the row where the first column matches $geneName
    $queryRNA = "SELECT * FROM $rnaTable WHERE Hugo_Symbol = '$geneName'";
    $dataRNA = fetchRows($conn, $queryRNA);

    if (empty($dataRNA)) {
        die("Gene name $geneName not found in Expression data.");
    }

    // Extract the first row (only one row should match the gene name)
    $rowRNA = $dataRNA[0];

    // Remove the Hugo_Symbol column
    unset($rowRNA['Hugo_Symbol']);

    // Calculate the median of expression values
    $values = array_values($rowRNA);
    $medianValue = median($values);

    // Replace values with "High" or "Low"
    foreach ($rowRNA as $patientId => $value) {
        $rowRNA[$patientId] = ($value > $medianValue) ? 'High' : 'Low';
    }

    // Fetch the necessary columns from the clinical data
    $columnsStr = implode(', ', array_merge(['ids'], $columns));
    $queryClin = "SELECT $columnsStr FROM $clinTable";
    $dataClin = fetchRows($conn, $queryClin);

    // Prepare JSON strings without extra escaping
    $jsonRowRNA = json_encode($rowRNA, JSON_UNESCAPED_SLASHES);
    $jsonDataClin = json_encode($dataClin, JSON_UNESCAPED_SLASHES);

    // Paths to save the plots
    $plotFiles = [
        'OS' => '../plots/survival_plot_os.png'
    ];

    if ($dataset !== 'oncosg') {
    $plotFiles['DSS'] = '../plots/survival_plot_dss.png';
    $plotFiles['DFS'] = '../plots/survival_plot_dfs.png';
    $plotFiles['PFS'] = '../plots/survival_plot_pfs.png';
	}
	
	if ($dataset === 'gse31210' || $dataset === 'gse37745_adeno' || $dataset === 'gse37745_squam' || $dataset === 'gse50081_adeno' || $dataset === 'gse50081_squam' || $dataset === 'gse68465') {
    $plotFiles['RFS'] = '../plots/survival_plot_rfs.png';
	}

	// Delete the previous plot files if they exist
	foreach ($plotFiles as $plotFile) {
		if (file_exists($plotFile)) {
			unlink($plotFile);
		}
	}

    // Prepare Python script for survival analysis
    $pythonScript = "
import sys
sys.path.append(r'C:\Users\Lung-TFDB\AppData\Local\Programs\Python\Python313\Lib\site-packages')
import json
import pandas as pd
from lifelines import KaplanMeierFitter
from lifelines.statistics import logrank_test
import matplotlib.pyplot as plt
import numpy as np

plt.switch_backend('Agg')

def perform_survival_analysis(gene_data, clin_data, gene_name, time_col, status_col, plot_title, plot_path):
    gene_data = json.loads(gene_data)
    clin_data = json.loads(clin_data)

    gene_df = pd.DataFrame(list(gene_data.items()), columns=['ids', 'expression'])
    gene_df['ids'] = gene_df['ids'].str.replace('-', '.').str[:12]  # Replace '-' with '.' in gene IDs and take first 12 characters
    clin_df = pd.DataFrame(clin_data)
    clin_df['ids'] = clin_df['ids'].str.replace('-', '.')  # Replace '-' with '.' in clinical IDs

    merged_data = pd.merge(gene_df, clin_df, on='ids')

    # Ensure data is numeric and drop NaNs in necessary columns
    merged_data[time_col] = pd.to_numeric(merged_data[time_col], errors='coerce')
    merged_data[status_col] = merged_data[status_col].apply(lambda x: 1 if '1' in str(x) else 0)
    merged_data.dropna(subset=[time_col, status_col], inplace=True)

    kmf = KaplanMeierFitter()
    fig, ax = plt.subplots(figsize=(16.5, 12))

    # Plot High and Low survival curves
    for label, color in zip(['High', 'Low'], ['red', 'blue']):
        mask = merged_data['expression'] == label
        if merged_data[mask].empty:
            print(f'No data for expression level {label}')
            continue
        n_samples = len(merged_data[mask])
        kmf.fit(merged_data[mask][time_col], event_observed=merged_data[mask][status_col].astype(int), label=f'{label} (n={n_samples})')
        kmf.plot_survival_function(ax=ax, ci_show=True, color=color, ci_alpha=0.08)

    # Calculate p-value using log-rank test
    high_mask = merged_data['expression'] == 'High'
    low_mask = merged_data['expression'] == 'Low'
    results = logrank_test(merged_data[high_mask][time_col], merged_data[low_mask][time_col],
                           event_observed_A=merged_data[high_mask][status_col], event_observed_B=merged_data[low_mask][status_col])
    p_value = results.p_value

    plt.title(plot_title, fontweight='bold', fontsize=16)
    plt.xlabel('Time (Months)', fontweight='bold', fontsize=16)
    plt.ylabel('Survival Probability', fontweight='bold', fontsize=16)
    plt.text(0.95, 0.05, f'p-value: {p_value:.3e}', verticalalignment='bottom', horizontalalignment='right', transform=ax.transAxes, fontweight='bold', fontsize=16)
    legend = plt.legend(title='Expression', title_fontsize=14)
    legend.get_title().set_fontweight('bold')
    for text in legend.get_texts():
        text.set_fontweight('bold')
        text.set_fontsize(14)
    plt.xticks(fontsize=12)
    plt.yticks(fontsize=12)
    plt.savefig(plot_path, bbox_inches='tight')
    plt.close()

    return plot_path

gene_data = '$jsonRowRNA'
clin_data = '$jsonDataClin'
gene_name = '$geneName'

plot_paths = []

if '$dataset' == 'gse30219_adeno' or '$dataset' == 'gse30219_squam':
	plot_paths.append(
    perform_survival_analysis(gene_data, clin_data, gene_name, 'DFS_MONTHS', 'DFS_STATUS', f'Survival Analysis for Gene: {gene_name} (DFS)', r'{$plotFiles['DFS']}')
)
else:
	plot_paths.append(
	perform_survival_analysis(gene_data, clin_data, gene_name, 'OS_MONTHS', 'OS_STATUS', f'Survival Analysis for Gene: {gene_name} (OS)', r'{$plotFiles['OS']}')
)
if '$dataset' == 'gse29013_adeno' or '$dataset' == 'gse29013_squam':
    plot_paths.extend([
        perform_survival_analysis(gene_data, clin_data, gene_name, 'PFS_MONTHS', 'PFS_STATUS', f'Survival Analysis for Gene: {gene_name} (PFS)', r'{$plotFiles['PFS']}')
    ])
elif '$dataset' == 'gse31210' or '$dataset' == 'gse37745_adeno' or '$dataset' == 'gse37745_squam' or '$dataset' == 'gse50081_adeno' or '$dataset' == 'gse50081_squam'or '$dataset' == 'gse68465':
    plot_paths.extend([
        perform_survival_analysis(gene_data, clin_data, gene_name, 'RFS_MONTHS', 'RFS_STATUS', f'Survival Analysis for Gene: {gene_name} (RFS)', r'{$plotFiles['RFS']}')
    ])
elif '$dataset' != 'oncosg':
    plot_paths.extend([
        perform_survival_analysis(gene_data, clin_data, gene_name, 'DSS_MONTHS', 'DSS_STATUS', f'Survival Analysis for Gene: {gene_name} (DSS)', r'{$plotFiles['DSS']}'),
        perform_survival_analysis(gene_data, clin_data, gene_name, 'DFS_MONTHS', 'DFS_STATUS', f'Survival Analysis for Gene: {gene_name} (DFS)', r'{$plotFiles['DFS']}'),
        perform_survival_analysis(gene_data, clin_data, gene_name, 'PFS_MONTHS', 'PFS_STATUS', f'Survival Analysis for Gene: {gene_name} (PFS)', r'{$plotFiles['PFS']}')
    ])
";

    // Write the Python script to a temporary file
    $scriptFile = tempnam(sys_get_temp_dir(), 'surv_analysis_') . '.py';
    file_put_contents($scriptFile, $pythonScript);

    // Execute the Python script and capture output
    //$command = "python " . escapeshellarg($scriptFile);
    $command = "\"C:\\Users\\Lung-TFDB\\AppData\\Local\\Programs\\Python\\Python313\\python.exe\" " . escapeshellarg($scriptFile);
    $output = shell_exec($command . ' 2>&1'); // Capture both stdout and stderr
    file_put_contents('python_output.log', $output); // Write the output to a log file

	echo "<div class='plot-container'>";
	foreach ($plotFiles as $type => $plotFile) {
		if (file_exists($plotFile)) {
			// Extract the file name to use in the download attribute
			$fileName = basename($plotFile);
			echo "<div class='plot'><h3>Survival Analysis Plot for " . htmlspecialchars($type) . "</h3>";
			// Include geneName in the download attribute
			echo "<a href='$plotFile' download='" . htmlspecialchars($geneName) . "_$fileName'><img src='data:image/png;base64," . base64_encode(file_get_contents($plotFile)) . "' alt='Survival Analysis Plot ($type)'></a>";
			echo "</div>";
		}
	}
	echo "</div>";

    if ($dataset === 'oncosg') {
        echo "<p>Note: This dataset does not have information for DFS, DSS, or PFS.</p>";
    }

    // Clean up temporary file
    unlink($scriptFile);
}

if ($analysisType === 'mut') {
    // Fetch the sample IDs from the mutation table for the given gene
    $queryMut = "SELECT * FROM $mutTable WHERE Hugo_Symbol = '$geneName'";
    $dataMut = fetchRows($conn, $queryMut);

    $mutSamples = [];
    foreach ($dataMut as $sample) {
        $mutSamples[] = substr(str_replace('-', '.', $sample['Tumor_Sample_Barcode']), 0, 12);
    }

    // Create a row with "Mutated" or "Non-mutated"
    $rowMut = [];
    foreach ($mutSamples as $sample) {
        $rowMut[$sample] = 'Mutated';
    }

    // Get all unique samples from clinical data
    $queryClinSamples = "SELECT DISTINCT ids FROM $clinTable";
    $dataClinSamples = fetchRows($conn, $queryClinSamples);

    foreach ($dataClinSamples as $sample) {
        $id = str_replace('-', '.', $sample['ids']);
        if (!isset($rowMut[$id])) {
            $rowMut[$id] = 'Non-mutated';
        }
    }

    // Fetch the necessary columns from the clinical data
    $columnsStr = implode(', ', array_merge(['ids'], $columns));
    $queryClin = "SELECT $columnsStr FROM $clinTable";
    $dataClin = fetchRows($conn, $queryClin);

    // Prepare JSON strings without extra escaping
    $jsonRowMut = json_encode($rowMut, JSON_UNESCAPED_SLASHES);
    $jsonDataClin = json_encode($dataClin, JSON_UNESCAPED_SLASHES);

    // Paths to save the plots
    $plotFiles = [
        'OS' => '../plots/survival_plot_os.png'
    ];

    if ($dataset !== 'oncosg') {
    $plotFiles['DSS'] = '../plots/survival_plot_dss.png';
    $plotFiles['DFS'] = '../plots/survival_plot_dfs.png';
    $plotFiles['PFS'] = '../plots/survival_plot_pfs.png';
	}
    
    if ($dataset === 'mskcc2020') {
        $plotFiles['RFS'] = '../plots/survival_plot_pfs.png';
    }

    // Delete the previous plot files if they exist
    foreach ($plotFiles as $plotFile) {
        if (file_exists($plotFile)) {
            unlink($plotFile);
        }
    }

    // Prepare Python script for survival analysis
    $pythonScript = "
import sys
sys.path.append(r'C:\Users\Lung-TFDB\AppData\Local\Programs\Python\Python313\Lib\site-packages')
import json
import pandas as pd
from lifelines import KaplanMeierFitter
from lifelines.statistics import logrank_test
import matplotlib.pyplot as plt
import numpy as np

plt.switch_backend('Agg')

def perform_survival_analysis(gene_data, clin_data, gene_name, time_col, status_col, plot_title, plot_path):
    gene_data = json.loads(gene_data)
    clin_data = json.loads(clin_data)

    gene_df = pd.DataFrame(list(gene_data.items()), columns=['ids', 'mutation'])
    gene_df['ids'] = gene_df['ids'].str.replace('-', '.').str[:12]  # Replace '-' with '.' in gene IDs and take first 12 characters
    clin_df = pd.DataFrame(clin_data)
    clin_df['ids'] = clin_df['ids'].str.replace('-', '.')  # Replace '-' with '.' in clinical IDs

    merged_data = pd.merge(gene_df, clin_df, on='ids')

    # Ensure data is numeric and drop NaNs in necessary columns
    merged_data[time_col] = pd.to_numeric(merged_data[time_col], errors='coerce')
    merged_data[status_col] = merged_data[status_col].apply(lambda x: 1 if '1' in str(x) else 0)
    merged_data.dropna(subset=[time_col, status_col], inplace=True)

    kmf = KaplanMeierFitter()
    fig, ax = plt.subplots(figsize=(16.5, 12))

    # Plot Mutated and Non-mutated survival curves
    for label, color in zip(['Mutated', 'Non-mutated'], ['red', 'blue']):
        mask = merged_data['mutation'] == label
        if merged_data[mask].empty:
            print(f'No data for mutation status {label}')
            continue
        n_samples = len(merged_data[mask])
        kmf.fit(merged_data[mask][time_col], event_observed=merged_data[mask][status_col].astype(int), label=f'{label} (n={n_samples})')
        kmf.plot_survival_function(ax=ax, ci_show=True, color=color, ci_alpha=0.08)

    # Calculate p-value using log-rank test
    mutated_mask = merged_data['mutation'] == 'Mutated'
    non_mutated_mask = merged_data['mutation'] == 'Non-mutated'
    results = logrank_test(merged_data[mutated_mask][time_col], merged_data[non_mutated_mask][time_col],
                           event_observed_A=merged_data[mutated_mask][status_col], event_observed_B=merged_data[non_mutated_mask][status_col])
    p_value = results.p_value
	
    plt.title(plot_title, fontweight='bold', fontsize=16)
    plt.xlabel('Time (Months)', fontweight='bold', fontsize=16)
    plt.ylabel('Survival Probability', fontweight='bold', fontsize=16)
    plt.text(0.95, 0.05, f'p-value: {p_value:.3e}', verticalalignment='bottom', horizontalalignment='right', transform=ax.transAxes, fontweight='bold', fontsize=16)
    legend = plt.legend(title='Mutation', title_fontsize=14)
    legend.get_title().set_fontweight('bold')
    for text in legend.get_texts():
        text.set_fontweight('bold')
        text.set_fontsize(14)
    plt.xticks(fontsize=12)
    plt.yticks(fontsize=12)
    plt.savefig(plot_path, bbox_inches='tight')
    plt.close()

    return plot_path

gene_data = '$jsonRowMut'
clin_data = '$jsonDataClin'
gene_name = '$geneName'

plot_paths = [
    perform_survival_analysis(gene_data, clin_data, gene_name, 'OS_MONTHS', 'OS_STATUS', f'Survival Analysis for Gene: {gene_name} (OS)', r'{$plotFiles['OS']}')
]

if '$dataset' == 'mskcc2020':
    plot_paths.append([
        perform_survival_analysis(gene_data, clin_data, gene_name, 'RFS_MONTHS', 'RFS_STATUS', f'Survival Analysis for Gene: {gene_name} (RFS)', r'{$plotFiles['RFS']}')
    ])
	
if '$dataset' != 'oncosg':
    plot_paths.extend([
        perform_survival_analysis(gene_data, clin_data, gene_name, 'DSS_MONTHS', 'DSS_STATUS', f'Survival Analysis for Gene: {gene_name} (DSS)', r'{$plotFiles['DSS']}'),
        perform_survival_analysis(gene_data, clin_data, gene_name, 'DFS_MONTHS', 'DFS_STATUS', f'Survival Analysis for Gene: {gene_name} (DFS)', r'{$plotFiles['DFS']}'),
        perform_survival_analysis(gene_data, clin_data, gene_name, 'PFS_MONTHS', 'PFS_STATUS', f'Survival Analysis for Gene: {gene_name} (PFS)', r'{$plotFiles['PFS']}')
    ])
";

    // Write the Python script to a temporary file
    $scriptFile = tempnam(sys_get_temp_dir(), 'surv_analysis_') . '.py';
    file_put_contents($scriptFile, $pythonScript);

    // Execute the Python script and capture output
    $command = "python " . escapeshellarg($scriptFile);
    // $command = "\"C:\\Users\\Lung-TFDB\\AppData\\Local\\Programs\\Python\\Python313\\python.exe\" " . escapeshellarg($scriptFile);
    $output = shell_exec($command . ' 2>&1'); // Capture both stdout and stderr
    file_put_contents('python_output.log', $output); // Write the output to a log file

    	echo "<div class='plot-container'>";
	foreach ($plotFiles as $type => $plotFile) {
		if (file_exists($plotFile)) {
			// Extract the file name to use in the download attribute
			$fileName = basename($plotFile);
			echo "<div class='plot'><h3>Survival Analysis Plot for " . htmlspecialchars($type) . "</h3>";
			// Include geneName in the download attribute
			echo "<a href='$plotFile' download='" . htmlspecialchars($geneName) . "_$fileName'><img src='data:image/png;base64," . base64_encode(file_get_contents($plotFile)) . "' alt='Survival Analysis Plot ($type)'></a>";
			echo "</div>";
		}
	}
    echo "</div>";

    // Clean up temporary file
    unlink($scriptFile);
}

$conn->close();

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
?>