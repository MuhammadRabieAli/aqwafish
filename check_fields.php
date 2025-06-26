<?php
require_once 'config.php';

// Check for specific fields in the fish table
$fields_to_check = [
    // Identifier fields
    'process_id',
    'sample_id',
    'museum_id',
    'collection_code',
    'field_id',
    'deposited_in',
    'specimen_linkout',
    
    // Genetic sequence fields
    'sequence_type',
    'sequence_id',
    'genbank_accession',
    'sequence_updated_at',
    'genome_type',
    'locus',
    'nucleotides_count',
    'dna_sequence'
];

$fish_check_sql = "SHOW COLUMNS FROM fish";
$fish_result = mysqli_query($conn, $fish_check_sql);

$found_fields = [];
while ($row = mysqli_fetch_assoc($fish_result)) {
    if (in_array($row['Field'], $fields_to_check)) {
        $found_fields[] = $row['Field'];
    }
}

// Check fish_datasets table exists
$tables_sql = "SHOW TABLES LIKE 'fish_datasets'";
$tables_result = mysqli_query($conn, $tables_sql);
$datasets_table_exists = mysqli_num_rows($tables_result) > 0;

// Output results
echo "Database Field Check Results:\n\n";
echo "Fields to check: " . count($fields_to_check) . "\n";
echo "Fields found: " . count($found_fields) . "\n\n";

echo "Missing fields:\n";
$missing_fields = array_diff($fields_to_check, $found_fields);
if (count($missing_fields) > 0) {
    foreach ($missing_fields as $field) {
        echo "- $field\n";
    }
} else {
    echo "None! All fields are present.\n";
}

echo "\nfish_datasets table exists: " . ($datasets_table_exists ? "Yes" : "No") . "\n";

if ($datasets_table_exists) {
    // Check fish_datasets table structure
    $datasets_check_sql = "SHOW COLUMNS FROM fish_datasets";
    $datasets_result = mysqli_query($conn, $datasets_check_sql);
    
    echo "\nfish_datasets table structure:\n";
    while ($row = mysqli_fetch_assoc($datasets_result)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

echo "\nDatabase check completed.\n";
?> 