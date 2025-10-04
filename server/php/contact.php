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
if (!isset($data['name']) || trim($data['name']) === '') {
  $errors[] = ['path' => ['name'], 'message' => 'Name is required'];
}
if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
  $errors[] = ['path' => ['email'], 'message' => 'Valid email is required'];
}
if (!isset($data['phone']) || strlen(trim($data['phone'])) < 5) {
  $errors[] = ['path' => ['phone'], 'message' => 'Phone is required'];
}
if (!isset($data['message']) || trim($data['message']) === '') {
  $errors[] = ['path' => ['message'], 'message' => 'Message is required'];
}

if (!empty($errors)) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Invalid data', 'errors' => $errors]);
  exit;
}

$enriched = $data;
$enriched['submittedAt'] = date('c');
$enriched['id'] = 'message_' . round(microtime(true) * 1000) . '_' . substr(bin2hex(random_bytes(6)),0,9);

$baseDir = realpath(__DIR__ . '/../../');
if ($baseDir === false) {
  $baseDir = __DIR__ . '/../../';
}
$dir = $baseDir . '/messages';
if (!file_exists($dir)) mkdir($dir, 0755, true);

$jsonName = $enriched['id'] . '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $enriched['name']) . '.json';
file_put_contents($dir . '/' . $jsonName, json_encode($enriched, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$csvPath = $dir . '/messages.csv';
if (!file_exists($csvPath)) {
  $header = 'Timestamp,Name,Email,Phone,Subject,Inquiry,Preferred Contact,Message\n';
  file_put_contents($csvPath, $header);
}
$rowValues = [
  date('c'),
  $enriched['name'],
  $enriched['email'],
  $enriched['phone'],
  $enriched['subject'] ?? '',
  $enriched['inquiry'] ?? '',
  $enriched['preferredContact'] ?? '',
  isset($enriched['message']) ? str_replace(["\n", "\r", ',' ], [' ', ' ', ';'], $enriched['message']) : ''
];
$csvRow = array_map(function ($v) { return '"' . str_replace('"', '""', (string)$v) . '"'; }, $rowValues);
file_put_contents($csvPath, file_get_contents($csvPath) . implode(',', $csvRow) . "\n");

echo json_encode(['success' => true, 'message' => 'Message submitted', 'id' => $enriched['id']]);
exit;
