<?php
require_once 'config.php';

// Check if a user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    die("Unauthorized access. You must be logged in as admin.");
}

echo "<h1>DNA Sequence Test</h1>";

// Sample DNA sequence
$test_sequence = "ACTGCCTTAAGCCTCCTCATTCGAGCAGAACTAAAGCCAACCTGGCTCCCTTTAGGTGATGATAAAATTATATAGTTATCGTTACGACGACTGGCCTTGTAATGAATTTTCTATAGTTATACCAATTATATTGGAGGGCTTTGGAAACTGGAATATTAAGTGAGCCCCCGACATAGCATTTCCCCCGAATGAACAACATAAGCTCTTAAACTCCCATTTTACCCCATTTAGGCCTCATCTGGGGTCGAAGAGGGTGCAGGACCCGGGTGAAGTGTTTACCCCCTAGGCCGAAACTAGGCCCACTGTCGAGAGGCCCTGAAGCCAACTTCCTTTTTCATTAGCCGGTGTTCATCACATCCCTGGGCTATCGCCCTTTTTTTAAAGCCCCCCTGGAATTTCACAATCAGAACTCCTTGAAGAGCCTTGTTTGACTGCCTTTAGGCCTCTCTCCCTCGGGTGTTTGAAGGCTTCTTAACTACTCTACAGAGCCGGAATTTAAACACTACCTTCT";

echo "<pre>Test sequence length: " . strlen($test_sequence) . " characters</pre>";

// Test our detection function
$is_dna = is_dna_sequence($test_sequence);
echo "<p>is_dna_sequence() result: " . ($is_dna ? "YES" : "NO") . "</p>";

// Test our processing function
$processed = process_dna_sequence($test_sequence);
echo "<p>process_dna_sequence() result length: " . strlen($processed) . " characters</p>";
echo "<pre>First 50 chars: " . htmlspecialchars(substr($processed, 0, 50)) . "...</pre>";

// Test direct database insertion
$fish_id = 1; // Use a known fish ID
$test_sql = "UPDATE fish SET dna_sequence = ? WHERE id = ?";
$test_stmt = mysqli_prepare($conn, $test_sql);

if ($test_stmt) {
    mysqli_stmt_bind_param($test_stmt, 'si', $processed, $fish_id);
    $test_result = mysqli_stmt_execute($test_stmt);
    echo "<p>Direct database update: " . ($test_result ? "SUCCESS" : "FAILED") . "</p>";
    if (!$test_result) {
        echo "<p>Error: " . mysqli_stmt_error($test_stmt) . "</p>";
    }
    mysqli_stmt_close($test_stmt);
} else {
    echo "<p>Failed to prepare statement: " . mysqli_error($conn) . "</p>";
}

// Verify the data was saved correctly
$verify_sql = "SELECT dna_sequence FROM fish WHERE id = ?";
$verify_stmt = mysqli_prepare($conn, $verify_sql);
mysqli_stmt_bind_param($verify_stmt, 'i', $fish_id);
mysqli_stmt_execute($verify_stmt);
$verify_result = mysqli_stmt_get_result($verify_stmt);

if ($row = mysqli_fetch_assoc($verify_result)) {
    $saved_sequence = $row['dna_sequence'];
    echo "<p>Retrieved sequence length: " . strlen($saved_sequence) . " characters</p>";
    echo "<pre>First 50 chars: " . htmlspecialchars(substr($saved_sequence, 0, 50)) . "...</pre>";
    
    // Check if the saved sequence matches what we tried to save
    if ($saved_sequence === $processed) {
        echo "<p style='color:green;'>SUCCESS: Saved sequence matches processed sequence!</p>";
    } else {
        echo "<p style='color:red;'>ERROR: Saved sequence does not match processed sequence!</p>";
        
        if (empty($saved_sequence)) {
            echo "<p>The saved sequence is empty or null.</p>";
        } else if ($saved_sequence === '0') {
            echo "<p>The saved sequence is literally '0'.</p>";
        }
    }
} else {
    echo "<p>No fish record found with ID $fish_id</p>";
}

echo "<a href='admin/dashboard.php'>Back to Dashboard</a>";
?> 