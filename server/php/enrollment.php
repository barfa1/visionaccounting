<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
  exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$errors = [];

if (!isset($data['fullName']) || trim($data['fullName']) === '') {
  $errors[] = ['path' => ['fullName'], 'message' => 'Full name is required'];
}
if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
  $errors[] = ['path' => ['email'], 'message' => 'Valid email is required'];
}
if (!isset($data['phone']) || trim($data['phone']) === '') {
  $errors[] = ['path' => ['phone'], 'message' => 'Phone number is required'];
}
if (!isset($data['selectedCourse']) || trim($data['selectedCourse']) === '') {
  $errors[] = ['path' => ['selectedCourse'], 'message' => 'Course selection is required'];
}
if (!isset($data['preferredTiming']) || trim($data['preferredTiming']) === '') {
  $errors[] = ['path' => ['preferredTiming'], 'message' => 'Preferred timing is required'];
}

if (!empty($errors)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid enrollment data', 'errors' => $errors]);
  exit;
}

$enriched = $data;
$enriched['submittedAt'] = date('c');
$enriched['id'] = 'enrollment_' . round(microtime(true) * 1000) . '_' . substr(bin2hex(random_bytes(6)),0,9);

// Determine enrollments directory (two levels up from this file -> project root)
$baseDir = realpath(__DIR__ . '/../../');
if ($baseDir === false) {
  $baseDir = __DIR__ . '/../../';
}
$enrollmentsDir = $baseDir . '/enrollments';

if (!file_exists($enrollmentsDir)) {
  mkdir($enrollmentsDir, 0755, true);
}

// Save JSON file
$safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $enriched['fullName']);
$filename = "{$enriched['id']}_{$safeName}.json";
$filePath = $enrollmentsDir . '/' . $filename;
file_put_contents($filePath, json_encode($enriched, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Append to CSV
$csvPath = $enrollmentsDir . '/enrollments.csv';
$headers = 'Timestamp,Full Name,Email,Phone,Address,Selected Course,Preferred Timing,Previous Experience,Motivation\n';
$rowValues = [
  date('c'),
  $enriched['fullName'] ?? '',
  $enriched['email'] ?? '',
  $enriched['phone'] ?? '',
  $enriched['address'] ?? '',
  $enriched['selectedCourse'] ?? '',
  $enriched['preferredTiming'] ?? '',
  $enriched['previousExperience'] ?? '',
  isset($enriched['motivation']) ? str_replace(["\n", "\r", ',' ], [' ', ' ', ';'], $enriched['motivation']) : '',
];

$csvRow = array_map(function ($v) { return '"' . str_replace('"', '""', (string)$v) . '"'; }, $rowValues);
$csvLine = implode(',', $csvRow) . "\n";

if (!file_exists($csvPath)) {
  file_put_contents($csvPath, $headers . $csvLine);
} else {
  file_put_contents($csvPath, file_get_contents($csvPath) . $csvLine);
}

$results = ["Local: $filename"];

echo json_encode([
  'success' => true,
  'message' => 'Enrollment submitted successfully!',
  'enrollmentId' => $enriched['id'],
  'storage' => $results,
  'data' => $enriched
]);
exit;
