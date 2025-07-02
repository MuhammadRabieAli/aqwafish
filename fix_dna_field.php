<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    die("Unauthorized access. You must be logged in as admin.");
}

echo "<h1>Fixing DNA Sequence Field</h1>";

// Check current schema
$check_sql = "SHOW COLUMNS FROM fish LIKE 'dna_sequence'";
$check_result = mysqli_query($conn, $check_sql);
$current_schema = mysqli_fetch_assoc($check_result);

echo "<h2>Current Schema:</h2>";
echo "<pre>";
print_r($current_schema);
echo "</pre>";

// Modify the dna_sequence field to be a LONGTEXT type
$alter_sql = "ALTER TABLE fish MODIFY COLUMN dna_sequence LONGTEXT";
if (mysqli_query($conn, $alter_sql)) {
    echo "<p style='color:green'>Successfully modified dna_sequence field to LONGTEXT type.</p>";
} else {
    echo "<p style='color:red'>Error modifying schema: " . mysqli_error($conn) . "</p>";
}

// Check updated schema
$check_updated_sql = "SHOW COLUMNS FROM fish LIKE 'dna_sequence'";
$check_updated_result = mysqli_query($conn, $check_updated_sql);
$updated_schema = mysqli_fetch_assoc($check_updated_result);

echo "<h2>Updated Schema:</h2>";
echo "<pre>";
print_r($updated_schema);
echo "</pre>";

// Test inserting a large DNA sequence
$test_sequence = "ACTGCCTTAAGCCTCCTCATTCGAGCAGAACTAAAGCCAACCTGGCTCCCTTTAGGTGATGATAAAATTATATAGTTATCGTTACGACGACTGGCCTTGTAATGAATTTTCTATAGTTATACCAATTATATTGGAGGGCTTTGGAAACTGGAATATTAAGTGAGCCCCCGACATAGCATTTCCCCCGAATGAACAACATAAGCTCTTAAACTCCCATTTTACCCCATTTAGGCCTCATCTGGGGTCGAAGAGGGTGCAGGACCCGGGTGAAGTGTTTACCCCCTAGGCCGAAACTAGGCCCACTGTCGAGAGGCCCTGAAGCCAACTTCCTTTTTCATTAGCCGGTGTTCATCACATCCCTGGGCTATCGCCCTTTTTTTAAAGCCCCCCTGGAATTTCACAATCAGAACTCCTTGAAGAGCCTTGTTTGACTGCCTTTAGGCCTCTCTCCCTCGGGTGTTTGAAGGCTTCTTAACTACTCTACAGAGCCGGAATTTAAACACTACCTTCT";

// Pick a fish to update - use ID 1 or adjust as needed
$fish_id = 1;

// Update using a direct query without prepared statement to avoid any binding issues
$update_sql = "UPDATE fish SET dna_sequence = '" . mysqli_real_escape_string($conn, $test_sequence) . "' WHERE id = " . (int)$fish_id;

if (mysqli_query($conn, $update_sql)) {
    echo "<p style='color:green'>Successfully updated fish ID $fish_id with test DNA sequence.</p>";
} else {
    echo "<p style='color:red'>Error updating fish: " . mysqli_error($conn) . "</p>";
}

// Verify the update
$verify_sql = "SELECT dna_sequence FROM fish WHERE id = " . (int)$fish_id;
$verify_result = mysqli_query($conn, $verify_sql);
$verify_row = mysqli_fetch_assoc($verify_result);

if ($verify_row && isset($verify_row['dna_sequence'])) {
    $saved_sequence = $verify_row['dna_sequence'];
    echo "<p>Saved sequence length: " . strlen($saved_sequence) . "</p>";
    
    if ($saved_sequence === $test_sequence) {
        echo "<p style='color:green'>Verification PASSED: Saved sequence matches test sequence!</p>";
    } else {
        echo "<p style='color:red'>Verification FAILED: Saved sequence does not match test sequence.</p>";
        if ($saved_sequence === '0') {
            echo "<p>The saved sequence is literally '0'</p>";
        } elseif (empty($saved_sequence)) {
            echo "<p>The saved sequence is empty</p>";
        } else {
            echo "<p>First 50 chars of saved sequence: " . htmlspecialchars(substr($saved_sequence, 0, 50)) . "...</p>";
            echo "<p>Test sequence: " . htmlspecialchars(substr($test_sequence, 0, 50)) . "...</p>";
        }
    }
} else {
    echo "<p style='color:red'>Failed to retrieve sequence</p>";
}

echo "<p><a href='admin/dashboard.php'>Back to Dashboard</a></p>";
?> 