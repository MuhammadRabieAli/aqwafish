<?php
require_once 'config.php';

// Check if user is logged in and is admin for security
if (!isLoggedIn() || !isAdmin()) {
    die("Unauthorized access. You must be logged in as admin.");
}

echo "<h1>DNA Sequence Direct Update</h1>";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fish_id = isset($_POST['fish_id']) ? (int)$_POST['fish_id'] : 0;
    $dna_sequence = $_POST['dna_sequence'] ?? '';
    
    if (empty($fish_id) || empty($dna_sequence)) {
        echo "<p style='color:red'>Fish ID and DNA sequence are required!</p>";
    } else {
        // Process the DNA sequence directly
        $clean_sequence = strtoupper(preg_replace('/[^ACGTacgt]/', '', $dna_sequence));
        
        // Get the current fish data to display info
        $fish_sql = "SELECT name FROM fish WHERE id = ?";
        $fish_stmt = mysqli_prepare($conn, $fish_sql);
        mysqli_stmt_bind_param($fish_stmt, 'i', $fish_id);
        mysqli_stmt_execute($fish_stmt);
        $fish_result = mysqli_stmt_get_result($fish_stmt);
        $fish_data = mysqli_fetch_assoc($fish_result);
        
        if (!$fish_data) {
            echo "<p style='color:red'>Fish with ID $fish_id not found!</p>";
        } else {
            // Direct update using a regular query
            $update_sql = "UPDATE fish SET 
                          dna_sequence = '" . mysqli_real_escape_string($conn, $clean_sequence) . "',
                          nucleotides_count = " . strlen($clean_sequence) . ",
                          updated_at = CURRENT_TIMESTAMP
                          WHERE id = " . $fish_id;
            
            if (mysqli_query($conn, $update_sql)) {
                echo "<p style='color:green'>Successfully updated DNA sequence for Fish: " . htmlspecialchars($fish_data['name']) . " (ID: $fish_id)</p>";
                echo "<p>Sequence length: " . strlen($clean_sequence) . " nucleotides</p>";
                echo "<p>First 50 chars: " . htmlspecialchars(substr($clean_sequence, 0, 50)) . "...</p>";
                
                // Add edit link
                echo "<p><a href='edit_fish.php?id=$fish_id'>Go to Edit Fish Page</a></p>";
            } else {
                echo "<p style='color:red'>Error updating DNA sequence: " . mysqli_error($conn) . "</p>";
            }
        }
    }
}

// Fish selection form
echo "<h2>Select a Fish to Update</h2>";
$fish_list_sql = "SELECT id, name, scientific_name FROM fish ORDER BY name";
$fish_list_result = mysqli_query($conn, $fish_list_sql);

if (mysqli_num_rows($fish_list_result) > 0) {
    echo "<form method='post' action=''>";
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label for='fish_id'>Select Fish:</label><br>";
    echo "<select name='fish_id' id='fish_id' required style='width:100%;max-width:400px;padding:5px;'>";
    
    while ($fish = mysqli_fetch_assoc($fish_list_result)) {
        $display_name = htmlspecialchars($fish['name']);
        if (!empty($fish['scientific_name'])) {
            $display_name .= " (" . htmlspecialchars($fish['scientific_name']) . ")";
        }
        echo "<option value='" . $fish['id'] . "'>" . $display_name . "</option>";
    }
    
    echo "</select>";
    echo "</div>";
    
    echo "<div style='margin-bottom: 15px;'>";
    echo "<label for='dna_sequence'>DNA Sequence (only A, C, G, T characters will be preserved):</label><br>";
    echo "<textarea name='dna_sequence' id='dna_sequence' rows='10' style='width:100%;max-width:800px;' required></textarea>";
    echo "</div>";
    
    echo "<button type='submit' style='padding:10px 20px;background-color:#4CAF50;color:white;border:none;cursor:pointer;'>Update DNA Sequence</button>";
    echo "</form>";
} else {
    echo "<p>No fish found in the database.</p>";
}

echo "<p><a href='admin/dashboard.php'>Back to Dashboard</a></p>";
?> 