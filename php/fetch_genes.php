<?php

require 'db_connect.php';

$dataset = $_POST['dataset'];
$gene_ids = array();

if ($dataset == 'adeno') {
    $sql1 = "SELECT gene_id FROM adeno_mut_data";
    $sql2 = "SELECT gene_id FROM adeno_cna_data";
} elseif ($dataset == 'squam') {
    $sql1 = "SELECT gene_id FROM squam_mut_data";
    $sql2 = "SELECT gene_id FROM squam_cna_data";
} elseif ($dataset == 'oncosg') {
    $sql1 = "SELECT gene_id FROM oncosg_mut_data";
    $sql2 = "SELECT gene_id FROM oncosg_cna_data";
} elseif ($dataset == 'mskcc') {
    $sql1 = "SELECT gene_id FROM mskcc_mut_data";
	$sql2 = "SELECT gene_id FROM mskcc_mut_data";
}
 elseif ($dataset == 'mskcc2020') {
    $sql1 = "SELECT gene_id FROM mskcc2020_mut_data";
	$sql2 = "SELECT gene_id FROM mskcc2020_cna_data";
}
 elseif ($dataset == 'mskcc2022_adeno' || $dataset == 'mskcc2022_squam') {
    $sql1 = "SELECT gene_id FROM mskcc2022_mut_data";
	$sql2 = "SELECT gene_id FROM mskcc2022_cna_data";
}
 elseif ($dataset == 'gse102287') {
    $sql1 = "SELECT gene_id FROM gse102287_data";
	$sql2 = "SELECT gene_id FROM gse102287_data";
}
 elseif ($dataset == 'gse14814' || $dataset == 'gse14814_adeno' || $dataset == 'gse14814_squam') {
    $sql1 = "SELECT gene_id FROM gse14814_data";
	$sql2 = "SELECT gene_id FROM gse14814_data";
}
 elseif ($dataset == 'gse157011') {
    $sql1 = "SELECT gene_id FROM gse157011_data";
	$sql2 = "SELECT gene_id FROM gse157011_data";
}
 elseif ($dataset == 'gse19188' || $dataset == 'gse19188_adeno' || $dataset == 'gse19188_squam') {
    $sql1 = "SELECT gene_id FROM gse19188_data";
	$sql2 = "SELECT gene_id FROM gse19188_data";
}
 elseif ($dataset == 'gse29013' || $dataset == 'gse29013_adeno' || $dataset == 'gse29013_squam') {
    $sql1 = "SELECT gene_id FROM gse29013_data";
	$sql2 = "SELECT gene_id FROM gse29013_data";
}
 elseif ($dataset == 'gse30219' || $dataset == 'gse30219_adeno' || $dataset == 'gse30219_squam') {
    $sql1 = "SELECT gene_id FROM gse30219_data";
	$sql2 = "SELECT gene_id FROM gse30219_data";
}
 elseif ($dataset == 'gse31210') {
    $sql1 = "SELECT gene_id FROM gse31210_data";
	$sql2 = "SELECT gene_id FROM gse31210_data";
}
 elseif ($dataset == 'gse3141' || $dataset == 'gse3141_adeno' || $dataset == 'gse3141_squam') {
    $sql1 = "SELECT gene_id FROM gse3141_data";
	$sql2 = "SELECT gene_id FROM gse3141_data";
}
 elseif ($dataset == 'gse37745' || $dataset == 'gse37745_adeno' || $dataset == 'gse37745_squam') {
    $sql1 = "SELECT gene_id FROM gse37745_data";
	$sql2 = "SELECT gene_id FROM gse37745_data";
}
 elseif ($dataset == 'gse4573') {
    $sql1 = "SELECT gene_id FROM gse4573_data";
	$sql2 = "SELECT gene_id FROM gse4573_data";
}
 elseif ($dataset == 'gse50081' || $dataset == 'gse50081_adeno' || $dataset == 'gse50081_squam') {
    $sql1 = "SELECT gene_id FROM gse50081_data";
	$sql2 = "SELECT gene_id FROM gse50081_data";
}
 elseif ($dataset == 'gse68465') {
    $sql1 = "SELECT gene_id FROM gse68465_data";
	$sql2 = "SELECT gene_id FROM gse68465_data";
}
 elseif ($dataset == 'all') {
    $sql1 = "SELECT gene_id FROM all_data";
	$sql2 = "SELECT gene_id FROM all_data";
}

$result1 = $conn->query($sql1);
$result2 = $conn->query($sql2);

if ($result1->num_rows > 0) {
    while ($row = $result1->fetch_assoc()) {
        $gene_ids[] = $row['gene_id'];
    }
}
if ($result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        $gene_ids[] = $row['gene_id'];
    }
}
$conn->close();

// Remove duplicates and encode as JSON
$gene_ids = array_unique($gene_ids);
echo json_encode(array_values($gene_ids));
?>