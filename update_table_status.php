<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
// Debug temporaneo
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Verifica che i file esistano
if (!file_exists('tables_conf.json')) {
    echo json_encode(['success' => false, 'error' => 'tables_conf.json non trovato']);
    exit;
}

if (!file_exists('bookings.json')) {
    echo json_encode(['success' => false, 'error' => 'bookings.json non trovato']);
    exit;
}
date_default_timezone_set('Europe/Rome');
try {
    $tablesFile = 'tables_conf.json';
    $bookingsFile = 'bookings.json';
    
    // Leggi i file
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
    
    // Leggi le prenotazioni attive
    $bookings = [];
    if (file_exists($bookingsFile)) {
        $bookingsContent = file_get_contents($bookingsFile);
        if ($bookingsContent !== false) {
            $existingBookings = json_decode($bookingsContent, true);
            if ($existingBookings !== null) {
                $bookings = array_filter($existingBookings, function($booking) {
                    return isset($booking['status']) && $booking['status'] === 'attiva';
                });
            }
        }
    }
    
    $currentDateTime = new DateTime();
    $updatedTables = 0;
    
    // Aggiorna lo stato di ogni tavolo
    if (isset($tablesData['tables'])) {
        foreach ($tablesData['tables'] as &$table) {
            $oldStatus = $table['currentStatus'];
            $newStatus = 'disponibile'; // Default
            
            // Cerca prenotazioni attive per questo tavolo
            foreach ($bookings as $booking) {
    if ($booking['tableName'] === $table['name']) {
        $bookingDateTime = DateTime::createFromFormat('Y-m-d H:i', $booking['data'] . ' ' . $booking['ora']);
        
        if ($bookingDateTime) {
            $isForToday = $bookingDateTime->format('Y-m-d') === $currentDateTime->format('Y-m-d');
            $hoursUntilBooking = ($bookingDateTime->getTimestamp() - $currentDateTime->getTimestamp()) / 3600;
            error_log("Tavolo: {$table['name']}, Ore mancanti: $hoursUntilBooking, Status attuale: {$table['currentStatus']}, Nuovo status: $newStatus");
            if ($isForToday && $hoursUntilBooking <= 2 && $hoursUntilBooking >= -1) {
                // Se è per oggi e mancano meno di 2 ore (o è già iniziata da meno di 1 ora)
                $newStatus = 'occupato';
                break; // IMPORTANTE: esci dal loop, questo ha priorità
            } elseif ($bookingDateTime > $currentDateTime && $hoursUntilBooking > 2) {
    $newStatus = 'prenotato';
                // Non fare break qui perché potrebbe esserci una prenotazione più vicina
            }
        }
    }
}
            
            // Aggiorna lo status se è cambiato
            if ($table['currentStatus'] !== $newStatus) {
                $table['currentStatus'] = $newStatus;
                $updatedTables++;
                
                // Aggiungi entry nello storico
                if (!isset($table['history'])) {
                    $table['history'] = [];
                }
                $table['history'][] = [
                    'type' => 'aggiornamento_automatico',
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'timestamp' => $currentDateTime->format('c')
                ];
            }
        }
    }
    
    // Salva il file aggiornato solo se ci sono stati cambiamenti
    if ($updatedTables > 0) {
        if (file_put_contents($tablesFile, json_encode($tablesData, JSON_PRETTY_PRINT)) === false) {
            throw new Exception('Errore nel salvataggio della configurazione tavoli');
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Stati tavoli aggiornati automaticamente",
        'updatedTables' => $updatedTables,
        'totalTables' => count($tablesData['tables'] ?? []),
        'timestamp' => $currentDateTime->format('c')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>