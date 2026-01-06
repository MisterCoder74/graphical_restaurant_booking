<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$reservationsFile = '../data/reservations.json';
$layoutFile = '../data/tables_layout.json';

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
    $perTableUtilization = [];
    
    // Calculate total capacity
    foreach ($tables as $table) {
        $tableCapacity = $table['seats'] * $days * 24; // seats * days * hours per day
        $totalCapacity += $tableCapacity;
        $perTableUtilization[$table['id']] = 0;
    }
    
    if ($totalCapacity === 0) {
        return ['overall' => 0, 'perTable' => $perTableUtilization];
    }
    
    // Calculate used capacity
    $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
    
    foreach ($reservations as $reservation) {
        if ($reservation['status'] === 'cancelled') {
            continue;
        }
        
        $reservationDate = $reservation['date'];
        if ($reservationDate >= $cutoffDate) {
            $hoursUsed = $reservation['duration'] / 60; // convert minutes to hours
            
            $usedCapacity += $reservation['numberOfGuests'] * $hoursUsed;
            
            // Per table utilization
            if (isset($perTableUtilization[$reservation['tableId']])) {
                $perTableUtilization[$reservation['tableId']] += $reservation['numberOfGuests'] * $hoursUsed;
            }
        }
    }
    
    $overallRate = ($usedCapacity / $totalCapacity) * 100;
    
    // Calculate per table rates
    foreach ($tables as $table) {
        $tableCapacity = $table['seats'] * $days * 24;
        if ($tableCapacity > 0) {
            $perTableUtilization[$table['id']] = ($perTableUtilization[$table['id']] / $tableCapacity) * 100;
        }
    }
    
    return [
        'overall' => round($overallRate, 2),
        'perTable' => $perTableUtilization
    ];
}

function calculateBusiestHours($reservations, $days = 7) {
    $hourlyBookings = array_fill(0, 24, 0);
    $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
    
    foreach ($reservations as $reservation) {
        if ($reservation['status'] === 'cancelled') {
            continue;
        }
        
        $reservationDate = $reservation['date'];
        if ($reservationDate >= $cutoffDate) {
            $hour = intval(explode(':', $reservation['startTime'])[0]);
            $hourlyBookings[$hour]++;
        }
    }
    
    // Find busiest hours
    $busiestHours = [];
    $maxBookings = max($hourlyBookings);
    
    for ($hour = 0; $hour < 24; $hour++) {
        if ($hourlyBookings[$hour] > 0) {
            $busiestHours[] = [
                'hour' => sprintf('%02d:00', $hour),
                'bookings' => $hourlyBookings[$hour],
                'percentage' => $maxBookings > 0 ? round(($hourlyBookings[$hour] / $maxBookings) * 100, 1) : 0
            ];
        }
    }
    
    // Sort by bookings descending
    usort($busiestHours, function($a, $b) {
        return $b['bookings'] <=> $a['bookings'];
    });
    
    return $busiestHours;
}

function calculateCompletionRates($reservations, $days = 30) {
    $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
    $stats = [
        'total' => 0,
        'completed' => 0,
        'cancelled' => 0,
        'noShow' => 0
    ];
    
    foreach ($reservations as $reservation) {
        if ($reservation['date'] >= $cutoffDate) {
            $stats['total']++;
            
            switch ($reservation['status']) {
                case 'completed':
                    $stats['completed']++;
                    break;
                case 'cancelled':
                    $stats['cancelled']++;
                    break;
                case 'upcoming':
                    // Check if it's a no-show (past reservation time)
                    $reservationDateTime = $reservation['date'] . ' ' . $reservation['startTime'];
                    if (strtotime($reservationDateTime) < time()) {
                        $stats['noShow']++;
                    } else {
                        // Still upcoming
                    }
                    break;
            }
        }
    }
    
    // Calculate percentages
    $total = $stats['total'];
    $completedRate = $total > 0 ? round(($stats['completed'] / $total) * 100, 1) : 0;
    $cancelledRate = $total > 0 ? round(($stats['cancelled'] / $total) * 100, 1) : 0;
    $noShowRate = $total > 0 ? round(($stats['noShow'] / $total) * 100, 1) : 0;
    
    return [
        'total' => $stats['total'],
        'completed' => $completedRate,
        'cancelled' => $cancelledRate,
        'noShow' => $noShowRate
    ];
}

function calculateAdditionalMetrics($reservations, $days = 7) {
    $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
    $totalGuests = 0;
    $partySizes = [];
    $dailyBookings = [];
    
    foreach ($reservations as $reservation) {
        if ($reservation['status'] === 'cancelled') {
            continue;
        }
        
        $reservationDate = $reservation['date'];
        if ($reservationDate >= $cutoffDate) {
            $totalGuests += $reservation['numberOfGuests'];
            $partySizes[] = $reservation['numberOfGuests'];
            
            // Count bookings per day
            if (!isset($dailyBookings[$reservationDate])) {
                $dailyBookings[$reservationDate] = 0;
            }
            $dailyBookings[$reservationDate]++;
        }
    }
    
    $averagePartySize = !empty($partySizes) ? round(array_sum($partySizes) / count($partySizes), 1) : 0;
    
    // Find peak day
    $peakDay = null;
    $maxBookings = 0;
    foreach ($dailyBookings as $date => $count) {
        if ($count > $maxBookings) {
            $maxBookings = $count;
            $peakDay = $date;
        }
    }
    
    $peakDayName = $peakDay ? date('l', strtotime($peakDay)) : null;
    
    return [
        'totalGuests' => $totalGuests,
        'averagePartySize' => $averagePartySize,
        'peakDay' => $peakDayName,
        'peakDayBookings' => $maxBookings
    ];
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $reservations = loadReservations();
        $tables = loadLayout();
        
        $response = [
            'utilizationRate' => calculateUtilizationRate($reservations, $tables),
            'busiestHours' => calculateBusiestHours($reservations),
            'completionRates' => calculateCompletionRates($reservations),
            'additionalMetrics' => calculateAdditionalMetrics($reservations)
        ];
        
        echo json_encode(['success' => true, 'data' => $response]);
    } else {
        throw new Exception('Metodo non supportato');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>