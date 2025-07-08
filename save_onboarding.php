<?php
header("Content-Type: application/json");

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Required fields for validation
$required = [
    'user_id', 'gender', 'birthdate', 'body_type',
    'current_weight', 'target_weight', 'goal',
    'preferred_sets', 'preferred_reps'
];

// Check for missing fields
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing field: $field"]);
        exit;
    }
}

// Optional fields
$height         = trim($data['height'] ?? '');
$dietPreference = trim($data['diet_preference'] ?? '');
$allergies      = trim($data['allergies'] ?? '');

// Injury validation
$hasInjury     = !empty($data['has_injury']) ? 1 : 0;
$injuryDetails = trim($data['injury_details'] ?? '');

if ($hasInjury) {
    if (!preg_match("/^[a-zA-Z\s]{3,}$/", $injuryDetails)) {
        echo json_encode(['success' => false, 'message' => "Invalid injury details"]);
        exit;
    }
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "repeat_app");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => "Database connection failed"]);
    exit;
}

// Check if user exists and already onboarded
$check = $conn->prepare("SELECT is_onboarded FROM users WHERE id = ?");
$check->bind_param("i", $data['user_id']);
$check->execute();
$result = $check->get_result();
$user = $result->fetch_assoc();
$check->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => "User not found"]);
    exit;
}

if ((int)$user['is_onboarded'] === 1) {
    echo json_encode(['success' => false, 'message' => "User already onboarded"]);
    exit;
}

// Insert onboarding data
$stmt = $conn->prepare("
    INSERT INTO onboarding_data (
        user_id, gender, birthdate, body_type, current_weight,
        target_weight, height, goal, preferred_sets, preferred_reps,
        has_injury, injury_details, diet_preference, allergies
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "issssssssissss",
    $data['user_id'],
    $data['gender'],
    $data['birthdate'],
    $data['body_type'],
    $data['current_weight'],
    $data['target_weight'],
    $height,
    $data['goal'],
    $data['preferred_sets'],
    $data['preferred_reps'],
    $hasInjury,
    $injuryDetails,
    $dietPreference,
    $allergies
);

if ($stmt->execute()) {
    // Mark user as onboarded
    $update = $conn->prepare("UPDATE users SET is_onboarded = 1 WHERE id = ?");
    $update->bind_param("i", $data['user_id']);
    $update->execute();
    $update->close();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save onboarding data']);
}

$stmt->close();
$conn->close();
?>
