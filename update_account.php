<?php
header('Content-Type: application/json');
require 'db_connection.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User authentication required.']);
    exit;
}

$user_id = intval($input['user_id']);
$name = isset($input['name']) ? trim($input['name']) : null;
$current_password = isset($input['current_password']) ? $input['current_password'] : null;
$new_password = isset($input['new_password']) ? $input['new_password'] : null;

try {
    $conn->begin_transaction();
    
    // Update name if provided
    if ($name !== null) {
        $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $user_id);
        $stmt->execute();
    }
    
    // Update password if all required fields are provided
    if ($current_password !== null && $new_password !== null) {
        // Verify current password first
        $check = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $result = $check->get_result();
        $user = $result->fetch_assoc();
        
        if (!password_verify($current_password, $user['password'])) {
            throw new Exception('Current password is incorrect');
        }
        
        // Check if new password is same as current
        if (password_verify($new_password, $user['password'])) {
            throw new Exception('New password must be different from current password');
        }
        
        if (strlen($new_password) < 8) {
            throw new Exception('New password must be at least 8 characters');
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed_password, $user_id);
        $update->execute();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Account updated successfully',
        'data' => ['name' => $name]
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Update failed: ' . $e->getMessage()
    ]);
}

$conn->close();
?>