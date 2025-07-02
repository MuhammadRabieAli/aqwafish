<?php
// This is a direct test script to bypass our sanitizing functions
// Database configuration (copied directly to avoid any function issues)
$conn = mysqli_connect('localhost', 'root', '', 'aqwa');

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "<h1>Direct DNA Test</h1>";

// The DNA sequence to test
$test_sequence = "ACTGCCTTAAGCCTCCTCATTCGAGCAGAACTAAAGCCAACCTGGCTCCCTTTAGGTGATGATAAAATTATATAGTTATCGTTACGACGACTGGCCTTGTAATGAATTTTCTATAGTTATACCAATTATATTGGAGGGCTTTGGAAACTGGAATATTAAGTGAGCCCCCGACATAGCATTTCCCCCGAATGAACAACATAAGCTCTTAAACTCCCATTTTACCCCATTTAGGCCTCATCTGGGGTCGAAGAGGGTGCAGGACCCGGGTGAAGTGTTTACCCCCTAGGCCGAAACTAGGCCCACTGTCGAGAGGCCCTGAAGCCAACTTCCTTTTTCATTAGCCGGTGTTCATCACATCCCTGGGCTATCGCCCTTTTTTTAAAGCCCCCCTGGAATTTCACAATCAGAACTCCTTGAAGAGCCTTGTTTGACTGCCTTTAGGCCTCTCTCCCTCGGGTGTTTGAAGGCTTCTTAACTACTCTACAGAGCCGGAATTTAAACACTACCTTCT";

echo "<p>Test sequence length: " . strlen($test_sequence) . "</p>";

// Update a specific fish record with this DNA sequence
// Modify the fish_id as needed
$fish_id = 1;

// Manually prepare and execute the query
$update_sql = "UPDATE fish SET dna_sequence = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_sql);

if (!$stmt) {
    die("Error preparing statement: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, 'si', $test_sequence, $fish_id);
$result = mysqli_stmt_execute($stmt);

if ($result) {
    echo "<p style='color:green'>Update successful!</p>";
} else {
    echo "<p style='color:red'>Update failed: " . mysqli_stmt_error($stmt) . "</p>";
}

// Check if we can retrieve the sequence correctly
$check_sql = "SELECT dna_sequence FROM fish WHERE id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, 'i', $fish_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

if ($row = mysqli_fetch_assoc($check_result)) {
    $saved_sequence = $row['dna_sequence'];
    echo "<p>Saved sequence length: " . strlen($saved_sequence) . "</p>";
    
    if ($saved_sequence === $test_sequence) {
        echo "<p style='color:green'>Saved sequence matches test sequence!</p>";
    } else {
        echo "<p style='color:red'>Saved sequence does not match test sequence!</p>";
        if ($saved_sequence === '0') {
            echo "<p>The saved value is literally '0'</p>";
        } elseif (empty($saved_sequence)) {
            echo "<p>The saved value is empty</p>";
        } else {
            echo "<p>First 50 chars of saved sequence: " . htmlspecialchars(substr($saved_sequence, 0, 50)) . "...</p>";
        }
    }
} else {
    echo "<p style='color:red'>Failed to retrieve sequence</p>";
}

// Check the actual database schema for the dna_sequence field
$schema_sql = "SHOW COLUMNS FROM fish LIKE 'dna_sequence'";
$schema_result = mysqli_query($conn, $schema_sql);

if ($schema_row = mysqli_fetch_assoc($schema_result)) {
    echo "<h2>DNA Sequence Field Schema:</h2>";
    echo "<pre>";
    print_r($schema_row);
    echo "</pre>";
}

// Also check if the database has any strict mode settings
$sql_mode_query = "SELECT @@sql_mode";
$mode_result = mysqli_query($conn, $sql_mode_query);
$mode_row = mysqli_fetch_array($mode_result);
echo "<h2>MySQL SQL Mode:</h2>";
echo "<p>" . ($mode_row[0] ?: "No strict mode set") . "</p>";
?> 