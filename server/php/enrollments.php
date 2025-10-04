<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Method not allowed']);
  exit;
}

$baseDir = realpath(__DIR__ . '/../../');
if ($baseDir === false) {
  $baseDir = __DIR__ . '/../../';
}
$enrollmentsDir = $baseDir . '/enrollments';

if (!file_exists($enrollmentsDir)) {
  echo json_encode(['success' => true, 'count' => 0, 'enrollments' => []]);
  exit;
}

$files = array_values(array_filter(scandir($enrollmentsDir), function($f) use ($enrollmentsDir) {
  return is_file($enrollmentsDir . '/' . $f) && substr($f, -5) === '.json';
}));

$enrollments = [];
foreach ($files as $file) {
  $content = @file_get_contents($enrollmentsDir . '/' . $file);
  if ($content === false) continue;
  $json = json_decode($content, true);
  if (is_array($json)) $enrollments[] = $json;
}

usort($enrollments, function($a, $b) {
  $ta = isset($a['submittedAt']) ? strtotime($a['submittedAt']) : 0;
  $tb = isset($b['submittedAt']) ? strtotime($b['submittedAt']) : 0;
  return $tb <=> $ta;
});

echo json_encode(['success' => true, 'count' => count($enrollments), 'enrollments' => $enrollments]);
exit;
