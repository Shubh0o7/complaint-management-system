<?php
/**
 * API Endpoint: Register
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
    $full_name = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';
} else {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
}

// Validate input
if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields.']);
    exit();
}

if (strlen($full_name) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Full name must be at least 2 characters.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit();
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long.']);
    exit();
}

if ($password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit();
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
    exit();
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Hash password and insert user
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'user')");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
    exit();
}

$stmt->bind_param('sss', $full_name, $email, $hashed_password);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;
    
    // Automatically log in the user after registration
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $full_name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = 'user';

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Redirecting to dashboard...',
        'redirect' => 'dashboard.php',
        'user' => [
            'id' => $user_id,
            'name' => $full_name,
            'email' => $email,
            'role' => 'user'
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}

$stmt->close();
$conn->close();
?>