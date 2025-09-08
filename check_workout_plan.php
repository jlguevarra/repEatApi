<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Include database connection
include 'db_connection.php';

// Initialize response array
$response = ["success" => false, "has_plan" => false];

try {
    // Check if user_id is provided
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $response["message"] = "User ID is required";
        echo json_encode($response);
        exit;
    }

    $user_id = $_POST['user_id'];

    // Check if user has a workout plan
    $sql = "SELECT id FROM user_workout_plans WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response["success"] = true;
        $response["has_plan"] = true;
        $response["message"] = "User has a workout plan";
    } else {
        $response["success"] = true;
        $response["has_plan"] = false;
        $response["message"] = "User does not have a workout plan";
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $response["message"] = "Exception: " . $e->getMessage();
}

echo json_encode($response);

if (isset($conn)) {
    $conn->close();
}
exit;
?>