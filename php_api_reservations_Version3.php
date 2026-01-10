<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // CORS preflight
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Resolve data file path relative to this php file's directory (root-agnostic)
$reservationsFile = __DIR__ . '/../data/reservations.json';

function loadReservations() {
    global $reservationsFile;
    if (!file_exists($reservationsFile)) {
        return ['reservations' => []];
    }
    $content = file_get_contents($reservationsFile);
    return json_decode($content, true) ?: ['reservations' => []];
}

function saveReservations($data) {
    global $reservationsFile;
    // Ensure folder exists
    $dir = dirname($reservationsFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($reservationsFile, json_encode($data, JSON_PRETTY_PRINT));
}

function validateBookingConflict($newBooking, $existingBookings) {
    $newStart = strtotime($newBooking['date'] . ' ' . $newBooking['startTime']);
    $newDuration = isset($newBooking['duration']) ? intval($newBooking['duration']) : 60;
    $newEnd = $newStart + $newDuration * 60;
    foreach ($existingBookings as $b) {
        if ((int)$b['tableId'] !== (int)$newBooking['tableId']) continue;
        $bStart = strtotime($b['date'] . ' ' . $b['startTime']);
        $bDuration = isset($b['duration']) ? intval($b['duration']) : 60;
        $bEnd = $bStart + $bDuration * 60;
        // Overlap check
        if ($newStart < $bEnd && $newEnd > $bStart) {
            return true;
        }
    }
    return false;
}

// Simple router for actions
switch ($action) {
    case 'list':
        $data = loadReservations();
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'save':
        // Expects JSON body or form data
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
        $data = loadReservations();
        $reservations = $data['reservations'] ?? [];
        // Basic validation
        $new = $input;
        if (empty($new['id'])) {
            $new['id'] = uniqid('res_');
        }
        if (validateBookingConflict($new, $reservations)) {
            echo json_encode(['success' => false, 'error' => 'conflict']);
            break;
        }
        // Replace if exists
        $found = false;
        foreach ($reservations as &$r) {
            if ($r['id'] === $new['id']) {
                $r = $new;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $reservations[] = $new;
        }
        saveReservations(['reservations' => $reservations]);
        echo json_encode(['success' => true, 'data' => $new]);
        break;

    case 'delete':
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'missing_id']);
            break;
        }
        $data = loadReservations();
        $reservations = $data['reservations'] ?? [];
        $reservations = array_values(array_filter($reservations, function ($r) use ($id) {
            return ($r['id'] ?? '') !== $id;
        }));
        saveReservations(['reservations' => $reservations]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'unknown_action']);
        break;
}