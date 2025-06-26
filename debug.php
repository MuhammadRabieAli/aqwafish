<?php
// This is a debug script to check parameter counts

// Parameters in the SQL query
$sql = "INSERT INTO fish (name, scientific_name, family, environment, size_category, description, submitted_by, status,
              process_id, sample_id, museum_id, collection_code, field_id, deposited_in, specimen_linkout,
              sequence_type, sequence_id, genbank_accession, sequence_updated_at, genome_type, locus, nucleotides_count, dna_sequence) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

// Count question marks (placeholders)
$placeholder_count = substr_count($sql, '?');
echo "Number of placeholders (?) in SQL: " . $placeholder_count . "<br>";

// Type string
$type_string = 'ssssssisssssssssssssis';
echo "Length of type string: " . strlen($type_string) . "<br>";
echo "Type string: " . $type_string . "<br>";

// Count each type
$s_count = substr_count($type_string, 's');
$i_count = substr_count($type_string, 'i');
$d_count = substr_count($type_string, 'd');

echo "Number of string (s) parameters: " . $s_count . "<br>";
echo "Number of integer (i) parameters: " . $i_count . "<br>";
echo "Number of double (d) parameters: " . $d_count . "<br>";
echo "Total parameters in type string: " . ($s_count + $i_count + $d_count) . "<br>";

// List of parameters that would be bound
$params = [
    'name', 'scientific_name', 'family', 'environment', 'size_category', 'description', 'user_id',
    'process_id', 'sample_id', 'museum_id', 'collection_code', 'field_id', 'deposited_in', 'specimen_linkout',
    'sequence_type', 'sequence_id', 'genbank_accession', 'sequence_updated_at', 'genome_type', 'locus', 'nucleotides_count', 'dna_sequence'
];

echo "Number of parameters in list: " . count($params) . "<br>";
echo "Parameters:<br>";
echo "<ol>";
foreach ($params as $param) {
    echo "<li>" . htmlspecialchars($param) . "</li>";
}
echo "</ol>";

// Check if counts match
if ($placeholder_count == strlen($type_string) && $placeholder_count == count($params)) {
    echo "<strong style='color:green'>All counts match! No error expected.</strong>";
} else {
    echo "<strong style='color:red'>ERROR: Counts don't match!</strong><br>";
    echo "Placeholders: " . $placeholder_count . "<br>";
    echo "Type string length: " . strlen($type_string) . "<br>";
    echo "Parameter count: " . count($params) . "<br>";
}
?> 