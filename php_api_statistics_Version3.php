<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Resolve data file paths relative to this php file's directory (root-agnostic)
$reservationsFile = __DIR__ . '/../data/reservations.json';
$layoutFile = __DIR__ . '/../data/tables_layout.json';

function loadReservations() {
    global $reservationsFile;
    if (!file_exists($reservationsFile)) {
        return [];
    }
    $content = file_get_contents($reservationsFile);
    $data = json_decode($content, true);
    return $data['reservations'] ?? [];
}

function loadLayout() {
    global $layoutFile;
    if (!file_exists($layoutFile)) {
        return ['tables' => []];
    }
    $content = file_get_contents($layoutFile);
    $data = json_decode($content, true);
    return $data['tables'] ?? [];
}

function calculateUtilizationRate($reservations, $tables, $days = 7) {
    $totalCapacity = 0;
    $usedCapacity = 0;
    foreach ($tables as $t) {
        $totalCapacity += intval($t['capacity'] ?? 4);
    }
    // naive calculation over last $days days: count unique seats used in reservations
    $now = time();
    $windowStart = $now - $days * 24 * 3600;
    foreach ($reservations as $r) {
        $resTime = strtotime(($r['date'] ?? '') . ' ' . ($r['startTime'] ?? '00:00'));
        if ($resTime >= $windowStart && $resTime <= $now) {
            $usedCapacity += intval($r['numberOfGuests'] ?? ($r['guests'] ?? 1));
        }
    }
    if ($totalCapacity <= 0) return 0;
    $rate = ($usedCapacity / ($totalCapacity * $days)) * 100;
    return round(min(100, max(0, $rate)), 2);
}

// Simple statistics endpoint
$reservations = loadReservations();
$tables = loadLayout();

$stats = [
    'totalTables' => count($tables),
    'activeReservations' => count(array_filter($reservations, function ($r) {
        return ($r['status'] ?? '') !== 'completed' && ($r['status'] ?? '') !== 'cancelled';
    })),
    'todayGuests' => array_reduce($reservations, function ($carry, $r) {
        if (($r['date'] ?? '') === date('Y-m-d')) {
            $carry += intval($r['numberOfGuests'] ?? ($r['guests'] ?? 0));
        }
        return $carry;
    }, 0),
    'occupiedTables' => 0,
    'utilization' => calculateUtilizationRate($reservations, $tables, 7)
];

// determine occupied tables (current)
$occupied = [];
$now = time();
foreach ($reservations as $r) {
    $resTime = strtotime(($r['date'] ?? '') . ' ' . ($r['startTime'] ?? '00:00'));
    $duration = intval($r['duration'] ?? 60) * 60;
    if ($resTime <= $now && ($resTime + $duration) >= $now) {
        $tid = intval($r['tableId'] ?? -1);
        if ($tid > 0) $occupied[$tid] = true;
    }
}
$stats['occupiedTables'] = count($occupied);

echo json_encode(['success' => true, 'data' => $stats]);