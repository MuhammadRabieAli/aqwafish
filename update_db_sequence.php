<?php
require_once 'config.php';

// For direct script execution, bypass the login check
$direct_execution = true;

if (!$direct_execution && (!isLoggedIn() || !isAdmin())) {
    echo "Unauthorized access. You must be logged in as admin.";
    exit;
}

// Add genetic sequence columns to the fish table
$alter_sql = "ALTER TABLE fish 
              ADD COLUMN sequence_type VARCHAR(50) DEFAULT NULL,
              ADD COLUMN sequence_id VARCHAR(100) DEFAULT NULL,
              ADD COLUMN genbank_accession VARCHAR(50) DEFAULT NULL,
              ADD COLUMN sequence_updated_at DATE DEFAULT NULL,
              ADD COLUMN genome_type VARCHAR(100) DEFAULT NULL,
              ADD COLUMN locus VARCHAR(255) DEFAULT NULL,
              ADD COLUMN nucleotides_count INT DEFAULT NULL,
              ADD COLUMN dna_sequence TEXT DEFAULT NULL";

if (mysqli_query($conn, $alter_sql)) {
    echo "Fish table updated successfully with genetic sequence fields.<br>";
} else {
    echo "Error updating fish table: " . mysqli_error($conn) . "<br>";
}

echo "Database update completed.";
?> 