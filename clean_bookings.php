<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Gestisci richieste OPTIONS per CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verifica che sia una richiesta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito']);
    exit();
}

try {
    $bookingsFile = 'bookings.json';
    
    // Verifica che il file esista
    if (!file_exists($bookingsFile)) {
        throw new Exception('File prenotazioni non trovato');
    }
    
    // Leggi le prenotazioni esistenti
    $bookingsJson = file_get_contents($bookingsFile);
    if ($bookingsJson === false) {
        throw new Exception('Impossibile leggere il file prenotazioni');
    }
    
    $bookings = json_decode($bookingsJson, true);
    if ($bookings === null) {
        throw new Exception('File prenotazioni corrotto');
    }
    
    // Conta le prenotazioni prima della pulizia
    $totalBefore = count($bookings);
    $completedCount = 0;
    $cancelledCount = 0;
    
    foreach ($bookings as $booking) {
        if (isset($booking['status'])) {
            if ($booking['status'] === 'completata') $completedCount++;
            if ($booking['status'] === 'cancellata') $cancelledCount++;
        }
    }
    
    // Filtra solo le prenotazioni attive
    $activeBookings = array_filter($bookings, function($booking) {
        return !isset($booking['status']) || $booking['status'] === 'attiva';
    });
    
    // Reindicizza l'array per evitare buchi negli indici
    $activeBookings = array_values($activeBookings);
    
    $totalAfter = count($activeBookings);
    $removedCount = $totalBefore - $totalAfter;
    
    // Salva il file aggiornato
    $result = file_put_contents($bookingsFile, json_encode($activeBookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    if ($result === false) {
        throw new Exception('Impossibile salvare il file prenotazioni');
    }
    
    // Risposta di successo con statistiche
    echo json_encode([
        'success' => true, 
        'message' => 'Pulizia completata con successo',
        'stats' => [
            'total_before' => $totalBefore,
            'total_after' => $totalAfter,
            'removed_count' => $removedCount,
            'completed_removed' => $completedCount,
            'cancelled_removed' => $cancelledCount
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>