<?php
/**
 * Ojasva Events - Form Submission Handler
 * Stores inquiries directly into SQLite 'leads' table
 */

require_once __DIR__ . '/db.php';

// Set headers for JSON requests
$isJsonRequest = (
    (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
    (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isJsonRequest) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
        exit;
    }
    header('Location: index.html#plan');
    exit;
}

// Read input (handles both standard POST and JSON raw body)
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

$name     = trim($_POST['name'] ?? $jsonData['name'] ?? '');
$phone    = trim($_POST['phone'] ?? $jsonData['phone'] ?? '');
$email    = trim($_POST['email'] ?? $jsonData['email'] ?? '');
$event    = trim($_POST['event'] ?? $jsonData['event'] ?? '');
$date     = trim($_POST['date'] ?? $jsonData['date'] ?? '');
$venue    = trim($_POST['venue'] ?? $jsonData['venue'] ?? '');
$guests   = trim($_POST['guests'] ?? $jsonData['guests'] ?? '');
$package  = trim($_POST['package'] ?? $jsonData['package'] ?? '');
$budget   = trim($_POST['budget'] ?? $jsonData['budget'] ?? '');
$details  = trim($_POST['details'] ?? $jsonData['details'] ?? '');

// Validation
if (empty($name) || empty($phone) || empty($email) || empty($event)) {
    if ($isJsonRequest) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false, 
            'message' => 'Please provide your name, phone number, email address, and event type.'
        ]);
        exit;
    }
    die('Please provide all required fields: Name, Phone, Email, and Event Type. <a href="index.html#plan">Go Back</a>');
}

try {
    // 1. Insert into SQLite 'leads' table
    $stmt = $pdo->prepare("
        INSERT INTO leads (name, phone, email, event_type, event_date, venue, guests, package, budget, details, status)
        VALUES (:name, :phone, :email, :event_type, :event_date, :venue, :guests, :package, :budget, :details, 'New')
    ");

    $stmt->execute([
        ':name'       => $name,
        ':phone'      => $phone,
        ':email'      => $email,
        ':event_type' => $event,
        ':event_date' => $date ?: null,
        ':venue'      => $venue ?: null,
        ':guests'     => $guests ?: null,
        ':package'    => $package ?: null,
        ':budget'     => $budget ?: null,
        ':details'    => $details ?: null,
    ]);

    $newLeadId = $pdo->lastInsertId();

    if ($isJsonRequest) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => 'Enquiry successfully saved in SQLite database!',
            'lead_id' => $newLeadId
        ]);
        exit;
    }

    // Normal form submission redirect back with success status
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.html';
    $redirectUrl = strtok($referer, '?') . '?status=success#plan';
    header("Location: $redirectUrl");
    exit;

} catch (PDOException $e) {
    if ($isJsonRequest) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
    die("Database Error: " . htmlspecialchars($e->getMessage()));
}

