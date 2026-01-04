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
    // Leggi i dati dalla richiesta
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        throw new Exception('ID prenotazione mancante');
    }
    
    $bookingId = $input['id'];
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
    
    // Trova la prenotazione da cancellare
    $found = false;
    for ($i = 0; $i < count($bookings); $i++) {
        // Gestisci sia ID espliciti che generati dal frontend
        $currentId = isset($bookings[$i]['id']) ? $bookings[$i]['id'] : "booking_" . time() . "_" . $i;
        
        if ($currentId === $bookingId || 
            (isset($bookings[$i]['id']) && $bookings[$i]['id'] === $bookingId)) {
            
            $bookings[$i]['status'] = 'cancellata';
            $bookings[$i]['cancelled_at'] = date('Y-m-d H:i:s');
            $bookings[$i]['id'] = $currentId; // Assicura che l'ID sia presente
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        throw new Exception('Prenotazione non trovata con ID: ' . $bookingId);
    }
    
    // Salva il file aggiornato
    $result = file_put_contents($bookingsFile, json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    if ($result === false) {
        throw new Exception('Impossibile salvare il file prenotazioni');
    }
    
    // Risposta di successo
    echo json_encode([
        'success' => true, 
        'message' => 'Prenotazione cancellata con successo',
        'booking_id' => $bookingId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>