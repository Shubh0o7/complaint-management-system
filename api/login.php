<?php
/**
 * API Endpoint: Login
 * Accepts POST requests with JSON or form data
 * Returns JSON response with success/error status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit();
}

require_once __DIR__ . '/../config.php';

// Get input data (supports both JSON body and form data)
$contentType = isset($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';

if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
} else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
}

// Validate input
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

// Query database for user
$stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
    exit();
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'] ?? 'user';

        // Determine redirect URL based on role
        $redirect = ($_SESSION['user_role'] === 'admin') ? 'admin_dashboard.php' : 'dashboard.php';

        echo json_encode([
            'success' => true,
            'message' => 'Login successful! Redirecting...',
            'redirect' => $redirect,
            'user' => [
                'id' => $user['id'],
                'name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'user'
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
    }
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
}

$stmt->close();
$conn->close();
?>