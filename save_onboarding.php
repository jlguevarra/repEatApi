<?php
header("Content-Type: application/json");
require 'db_connection.php'; // Ensure this path is correct

// Decode JSON input
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

// Log incoming data for debugging (Optional, remove in production)
// error_log("Received data: " . print_r($data, true));

// Required fields for validation
// Updated list based on Flutter app data structure
$required = [
    'user_id', 'gender', 'birthdate', 'height', 'body_type',
    'current_weight', 'target_weight', 'goal', // Removed preferred_sets, preferred_reps
    'has_injury', 'injury_details', 'diet_preference', 'allergies' // Added new fields
];

// Check for missing fields
foreach ($required as $field) {
    if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
        // Special handling for boolean fields or fields that might legitimately be "0"
        if (($field === 'has_injury' || $field === 'injury_details' || $field === 'height') && isset($data[$field])) {
            continue; // Allow empty string or "0" for these fields if they are set
        }
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Sanitize and prepare data
$user_id = intval($data['user_id']); // Ensure it's an integer
$gender = trim($data['gender']);
$birthdate = trim($data['birthdate']);
$height = trim($data['height']); // Can be empty string
$body_type = trim($data['body_type']);
$current_weight = floatval($data['current_weight']); // Ensure it's a float
$target_weight = floatval($data['target_weight']);   // Ensure it's a float
$goal = trim($data['goal']);

// Handle injury information
$has_injury = filter_var($data['has_injury'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0; // Convert to 1 or 0
$injury_details = trim($data['injury_details']);

// Handle new dietary information
$diet_preference = trim($data['diet_preference']);
$allergies_raw = trim($data['allergies']); // This is now a comma-separated string or "None"

// Validate basic data types and formats
if (!in_array($gender, ['Male', 'Female', 'Other'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid gender']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
    echo json_encode(['success' => false, 'message' => 'Invalid birthdate format']);
    exit;
}

// Validate weights
if ($current_weight <= 0 || $target_weight <= 0) {
    echo json_encode(['success' => false, 'message' => 'Weights must be positive numbers']);
    exit;
}

// Validate goal
$valid_goals = ['Muscle Gain', 'Weight Loss'];
if (!in_array($goal, $valid_goals)) {
    echo json_encode(['success' => false, 'message' => 'Invalid goal']);
    exit;
}

// Validate injury details if injury is reported
if ($has_injury) {
    if (empty($injury_details) || $injury_details === 'None') {
        echo json_encode(['success' => false, 'message' => 'Injury details required if injury is reported']);
        exit;
    }
    // Example validation for injury details (adjust regex as needed)
    if (!preg_match("/^[a-zA-Z\s]{3,}$/", $injury_details)) {
        echo json_encode(['success' => false, 'message' => 'Invalid injury details format']);
        exit;
    }
} else {
    $injury_details = 'None'; // Explicitly set to 'None' if no injury
}

// Validate diet preference
$valid_diets = ['High Protein', 'Low Carb', 'Low Fat', 'Low Sodium', 'Dairy Free'];
if (!in_array($diet_preference, $valid_diets)) {
    echo json_encode(['success' => false, 'message' => 'Invalid diet preference']);
    exit;
}

// Process allergies
// The Flutter app sends a comma-separated string like "Milk,Eggs" or "None"
$processed_allergies = '';
if (empty($allergies_raw) || $allergies_raw === 'None') {
    $processed_allergies = 'None';
} else {
    // Split the comma-separated string and sanitize each allergy
    $allergy_list = array_map('trim', explode(',', $allergies_raw));
    $valid_allergies = ['None', 'Peanuts', 'Tree Nuts', 'Milk', 'Eggs', 'Wheat', 'Soy', 'Fish', 'Shellfish'];

    // Filter out invalid allergies and ensure uniqueness
    $unique_allergies = array_unique(array_filter($allergy_list, function($allergy) use ($valid_allergies) {
        return in_array($allergy, $valid_allergies);
    }));

    if (empty($unique_allergies)) {
        $processed_allergies = 'None';
    } else {
        // If "None" was somehow mixed in (shouldn't happen with current Flutter logic), remove it
        $unique_allergies = array_diff($unique_allergies, ['None']);
        if (empty($unique_allergies)) {
             $processed_allergies = 'None';
        } else {
             $processed_allergies = implode(', ', $unique_allergies);
        }
    }
}



// Check if user exists and hasn't been onboarded yet
$check_stmt = $conn->prepare("SELECT is_onboarded FROM users WHERE id = ?");
if (!$check_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare failed (check): ' . $conn->error]);
    exit;
}
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$user = $check_result->fetch_assoc();
$check_stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

if ((int)$user['is_onboarded'] === 1) {
    echo json_encode(['success' => false, 'message' => 'User already onboarded']);
    exit;
}

// Begin transaction for data consistency
$conn->begin_transaction();

try {
    // Insert onboarding data into onboarding_data table
    $insert_stmt = $conn->prepare("
        INSERT INTO onboarding_data (
            user_id, gender, birthdate, body_type, current_weight,
            target_weight, height, goal,
            has_injury, injury_details, diet_preference, allergies
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$insert_stmt) {
        throw new Exception('Database prepare failed (insert): ' . $conn->error);
    }

    $insert_stmt->bind_param(
        "isssssssssss", // Updated type string: i=int, s=string, d=float
        $user_id,
        $gender,
        $birthdate,
        $body_type,
        $current_weight,
        $target_weight,
        $height, // s for string (can be empty)
        $goal,
        $has_injury, // i for integer (0 or 1)
        $injury_details,
        $diet_preference,
        $processed_allergies
    );

    if (!$insert_stmt->execute()) {
        throw new Exception('Failed to insert onboarding data: ' . $insert_stmt->error);
    }
    $insert_stmt->close();

    // Mark user as onboarded in users table
    $update_stmt = $conn->prepare("UPDATE users SET is_onboarded = 1 WHERE id = ?");
    if (!$update_stmt) {
        throw new Exception('Database prepare failed (update): ' . $conn->error);
    }
    $update_stmt->bind_param("i", $user_id);

    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update user onboarding status: ' . $update_stmt->error);
    }
    $update_stmt->close();

    // If everything is successful, commit the transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Onboarding data saved successfully'
    ]);

} catch (Exception $e) {
    // If an error occurs, rollback the transaction
    $conn->rollback();
    // Log the error message for debugging
    error_log("Onboarding Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save onboarding data: ' . $e->getMessage()
    ]);
}

// Close the database connection
$conn->close();
?>