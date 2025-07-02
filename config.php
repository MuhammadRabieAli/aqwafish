<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change to your MySQL username
define('DB_PASS', ''); // Change to your MySQL password
define('DB_NAME', 'aqwa');

// Establish database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    // Select the database
    mysqli_select_db($conn, DB_NAME);
} else {
    die("Error creating database: " . mysqli_error($conn));
}

// Set character set
mysqli_set_charset($conn, "utf8mb4");

// Path constants
define('UPLOAD_PATH', 'uploads/');
define('ROOT_PATH', dirname(__FILE__) . '/');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

// Session management
session_start();

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Function to redirect with a message
function redirect($location, $message = "", $message_type = "info") {
    if (!empty($message)) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $message_type;
    }
    header("Location: $location");
    exit;
}

// Function to display flash messages
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
        
        echo '<div class="alert alert-' . $message_type . '">' . $message . '</div>';
        
        // Clear the message
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

// Function to sanitize input data
function sanitize($data) {
    global $conn;
    
    // Debug log for DNA sequences
    if (is_string($data) && strlen($data) > 100) {
        error_log("Long string detected in sanitize: " . substr($data, 0, 50) . "...");
        
        // Check if this looks like a DNA sequence
        if (is_dna_sequence($data)) {
            error_log("DNA sequence identified: " . strlen($data) . " characters");
            // For DNA sequences, just trim without any escaping
            return process_dna_sequence($data);
        }
    }
    
    // For regular data, apply full sanitization
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Function to check if a string is likely a DNA sequence
function is_dna_sequence($str) {
    // DNA sequences should be predominantly composed of A, C, G, T characters
    // We'll be lenient and allow some non-ACGT characters (up to 5%)
    if (!is_string($str) || strlen($str) < 20) {
        return false;
    }
    
    // Count ACGT characters
    $acgt_count = preg_match_all('/[ACGTacgt]/', $str, $matches);
    $total_length = strlen($str);
    
    // If more than 95% of characters are ACGT, it's likely a DNA sequence
    return ($acgt_count / $total_length) > 0.95;
}

// Function to process DNA sequences
function process_dna_sequence($sequence) {
    // Remove any whitespace and non-DNA characters
    $clean_sequence = preg_replace('/[^ACGTacgt]/', '', $sequence);
    
    // Convert to uppercase for consistency
    $clean_sequence = strtoupper($clean_sequence);
    
    error_log("Processed DNA sequence: " . substr($clean_sequence, 0, 50) . "... (Length: " . strlen($clean_sequence) . ")");
    
    return $clean_sequence;
} 