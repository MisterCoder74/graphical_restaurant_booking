<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
date_default_timezone_set('Europe/Rome');

try {
    // Leggi i dati JSON dal body della richiesta
    $input = file_get_contents('php://input');
    $bookingData = json_decode($input, true);
    
    if (!$bookingData) {
        throw new Exception('Dati prenotazione non validi');
    }
    
    // Validazione dati richiesti
    $requiredFields = ['tableName', 'cognome', 'data', 'ora', 'persone'];
    foreach ($requiredFields as $field) {
        if (!isset($bookingData[$field]) || empty($bookingData[$field])) {
            throw new Exception("Campo obbligatorio mancante: $field");
        }
    }
    
    $tableName = $bookingData['tableName'];
    $bookingsFile = 'bookings.json';
    $tablesFile = 'tables_conf.json';
    
    // Leggi il file delle prenotazioni (o crea un array vuoto se non esiste)
    $bookings = [];
    if (file_exists($bookingsFile)) {
        $bookingsContent = file_get_contents($bookingsFile);
        if ($bookingsContent !== false) {
            $existingBookings = json_decode($bookingsContent, true);
            if ($existingBookings !== null) {
                $bookings = $existingBookings;
            }
        }
    }
    
    // Aggiungi la nuova prenotazione
    $newBooking = [
        'id' => uniqid('booking_', true),
        'tableName' => $tableName,
        'cognome' => $bookingData['cognome'],
        'data' => $bookingData['data'],
        'ora' => $bookingData['ora'],
        'persone' => (int)$bookingData['persone'],
        'timestamp' => $bookingData['timestamp'] ?? date('c'),
        'status' => 'attiva'
    ];
    
    $bookings[] = $newBooking;
    
    // Salva il file delle prenotazioni
    if (file_put_contents($bookingsFile, json_encode($bookings, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Errore nel salvataggio del file prenotazioni');
    }
    
    // Aggiorna il file di configurazione tavoli
    if (!file_exists($tablesFile)) {
        throw new Exception('File configurazione tavoli non trovato');
    }
    
    $tablesContent = file_get_contents($tablesFile);
    if ($tablesContent === false) {
        throw new Exception('Impossibile leggere il file configurazione tavoli');
    }
    
    $tablesData = json_decode($tablesContent, true);
    if ($tablesData === null) {
        throw new Exception('Formato file configurazione tavoli non valido');
    }
    
    // CORREZIONE: Determina lo status del tavolo in base alla data/ora della prenotazione
    $bookingDateTime = DateTime::createFromFormat('Y-m-d H:i', $bookingData['data'] . ' ' . $bookingData['ora']);
    $currentDateTime = new DateTime();
    
    // Considera "oggi" se la prenotazione è per oggi e mancano meno di 2 ore
    $isForToday = $bookingDateTime->format('Y-m-d') === $currentDateTime->format('Y-m-d');
    $hoursUntilBooking = ($bookingDateTime->getTimestamp() - $currentDateTime->getTimestamp()) / 3600;
    
    // Determina il nuovo status
    $newStatus = 'disponibile'; // Default
    
    if ($isForToday && $hoursUntilBooking <= 2 && $hoursUntilBooking >= -1) {
        // Se è per oggi e mancano meno di 2 ore (o è già iniziata da meno di 1 ora)
        $newStatus = 'occupato';
    } elseif ($bookingDateTime->format('Y-m-d') === $currentDateTime->format('Y-m-d')) {
        // Se è per oggi ma ancora lontana nel tempo
        $newStatus = 'prenotato';
    } elseif ($bookingDateTime > $currentDateTime) {
        // Se è per una data futura
        $newStatus = 'prenotato';
    }
    
    // Trova e aggiorna il tavolo
    $tableFound = false;
    if (isset($tablesData['tables'])) {
        foreach ($tablesData['tables'] as &$table) {
            if ($table['name'] === $tableName) {
                $table['currentStatus'] = $newStatus;
                if (!isset($table['history'])) {
                    $table['history'] = [];
                }
                $table['history'][] = [
                    'type' => 'prenotazione',
                    'cognome' => $bookingData['cognome'],
                    'data' => $bookingData['data'],
                    'ora' => $bookingData['ora'],
                    'persone' => (int)$bookingData['persone'],
                    'timestamp' => $newBooking['timestamp']
                ];
                $tableFound = true;
                break;
            }
        }
    }
    
    if (!$tableFound) {
        throw new Exception("Tavolo '$tableName' non trovato nella configurazione");
    }
    
    // Salva il file di configurazione aggiornato
    if (file_put_contents($tablesFile, json_encode($tablesData, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Errore nel salvataggio della configurazione tavoli');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Prenotazione salvata con successo',
        'bookingId' => $newBooking['id'],
        'tableStatus' => $newStatus
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>