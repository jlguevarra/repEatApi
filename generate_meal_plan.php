<?php
header('Content-Type: application/json');
require 'db_connection.php';

if (!isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$user_id = intval($_POST['user_id']);

// Fetch user profile and onboarding data
$query = $conn->prepare("
    SELECT goal, diet_preference, allergies
    FROM onboarding_data
    WHERE user_id = ?
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data found for this user.']);
    exit;
}

$goal = strtolower($data['goal']);
$diet = strtolower($data['diet_preference']);
$allergies = array_map('trim', explode(',', strtolower($data['allergies'])));

// Sample logic to generate meals
$meals = [
    'breakfast' => 'Oatmeal with fruits',
    'lunch' => 'Grilled chicken with rice and vegetables',
    'dinner' => 'Baked salmon with sweet potatoes'
];

// Adjust meals based on diet preference
if ($diet === 'vegetarian') {
    $meals['lunch'] = 'Chickpea salad with quinoa';
    $meals['dinner'] = 'Vegetable stir-fry with tofu';
} elseif ($diet === 'keto') {
    $meals['breakfast'] = 'Scrambled eggs with avocado';
    $meals['lunch'] = 'Grilled chicken Caesar salad';
    $meals['dinner'] = 'Zucchini noodles with beef';
}

// Remove meals with allergens
foreach ($meals as $key => $meal) {
    foreach ($allergies as $allergen) {
        if (stripos($meal, $allergen) !== false) {
            $meals[$key] = 'Allergy-safe alternative meal';
            break;
        }
    }
}

echo json_encode([
    'success' => true,
    'goal' => $goal,
    'diet_preference' => $diet,
    'allergies' => $allergies,
    'meal_plan' => $meals
]);
