<?php
$baseDir = realpath(__DIR__ . '/../../');
if ($baseDir === false) {
  $baseDir = __DIR__ . '/../../';
}
$csvPath = $baseDir . '/messages/messages.csv';

if (!file_exists($csvPath)) {
  header('Content-Type: application/json');
  http_response_code(404);
  echo json_encode(['success' => false, 'message' => 'No messages found']);
  exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="messages_' . date('Y-m-d') . '.csv"');
echo file_get_contents($csvPath);
exit;
