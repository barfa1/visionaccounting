<?php
// Serve enrollments CSV if present
$baseDir = realpath(__DIR__ . '/../../');
if ($baseDir === false) {
  $baseDir = __DIR__ . '/../../';
}
$csvPath = $baseDir . '/enrollments/enrollments.csv';

if (!file_exists($csvPath) || !is_readable($csvPath)) {
  header('Content-Type: application/json; charset=utf-8');
  http_response_code(404);
  echo json_encode(['success' => false, 'message' => 'No enrollment data found']);
  exit;
}

// Ensure no accidental output (BOM/whitespace) is sent before headers
while (ob_get_level()) {
  ob_end_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="enrollments_' . date('Y-m-d') . '.csv"');
header('Content-Length: ' . filesize($csvPath));
header('Cache-Control: no-store, no-cache, must-revalidate');

// Output file contents
readfile($csvPath);
exit;
