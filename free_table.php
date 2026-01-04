<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

try {
// Legge il body JSON dalla richiesta
$input = file_get_contents('php://input');
$requestData = json_decode($input, true);

if (!$requestData || !isset($requestData['tableName'])) {
throw new Exception('Nome tavolo non specificato');
}

$tableName = $requestData['tableName'];
$tablesFile = 'tables_conf.json';
$bookingsFile = 'bookings.json';

// --- Aggiornamento TAVOLI ---

if (!file_exists($tablesFile)) {
throw new Exception("File '$tablesFile' non trovato");
}

$tablesContent = file_get_contents($tablesFile);
if ($tablesContent === false) {
throw new Exception("Impossibile leggere '$tablesFile'");
}

$tablesData = json_decode($tablesContent, true);
if (!is_array($tablesData) || !isset($tablesData['tables'])) {
throw new Exception("Formato non valido in '$tablesFile'");
}

$tableFound = false;
foreach ($tablesData['tables'] as &$table) {
if ($table['name'] === $tableName) {
$table['currentStatus'] = 'disponibile';
$table['history'][] = [
'type' => 'liberazione',
'timestamp' => date('c'),
'message' => 'Tavolo liberato automaticamente'
];
$tableFound = true;
break;
}
}

if (!$tableFound) {
throw new Exception("Tavolo '$tableName' non trovato in '$tablesFile'");
}

// Scrive il file aggiornato con lock per evitare conflitti
$fp = fopen($tablesFile, 'c+');
if (!$fp) {
throw new Exception("Impossibile aprire '$tablesFile' in scrittura");
}
flock($fp, LOCK_EX);
ftruncate($fp, 0);
fwrite($fp, json_encode($tablesData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

// --- Aggiornamento PRENOTAZIONI (bookings.json) ---

if (file_exists($bookingsFile)) {
$bookingsContent = file_get_contents($bookingsFile);
if ($bookingsContent !== false) {
$bookings = json_decode($bookingsContent, true);
if (is_array($bookings)) {
$updated = false;
foreach ($bookings as &$booking) {
if (
$booking['tableName'] === $tableName &&
isset($booking['status']) &&
$booking['status'] === 'attiva'
) {
$booking['status'] = 'completata';
$booking['completedAt'] = date('c');
$updated = true;
}
}
if ($updated) {
file_put_contents(
$bookingsFile,
json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
}
}
}
}

echo json_encode([
'success' => true,
'message' => "Tavolo '$tableName' liberato con successo",
]);
} catch (Exception $e) {
http_response_code(500);
echo json_encode([
'success' => false,
'error' => $e->getMessage(),
]);
}
?>