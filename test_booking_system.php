<?php
/**
 * Test script per verificare le funzionalità del sistema di prenotazione
 * Test: conflict detection, duration management, status validation
 */

date_default_timezone_set('Europe/Rome');
header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST SISTEMA PRENOTAZIONI ===\n\n";

// Funzione helper per fare richieste POST
function makeRequest($url, $data) {
    $jsonData = json_encode($data);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Test 1: Booking Conflict Detection - Sovrapposizione totale
echo "TEST 1: Conflict Detection - Sovrapposizione totale\n";
echo "-----------------------------------------------------\n";

$booking1 = [
    'tableName' => 'T1',
    'cognome' => 'TestRossi',
    'data' => date('Y-m-d', strtotime('+1 day')),
    'ora' => '19:00',
    'persone' => 4,
    'durata' => 2,
    'timestamp' => date('c')
];

$booking2 = [
    'tableName' => 'T1',
    'cognome' => 'TestBianchi',
    'data' => date('Y-m-d', strtotime('+1 day')),
    'ora' => '20:00', // Sovrapposizione: 19:00-21:00 vs 20:00-22:00
    'persone' => 4,
    'durata' => 2,
    'timestamp' => date('c')
];

echo "Creazione prima prenotazione (T1, {$booking1['data']} 19:00-21:00)...\n";
$result1 = makeRequest('http://localhost/save_booking.php', $booking1);
echo "Risultato: " . ($result1['success'] ? "✓ SUCCESSO" : "✗ ERRORE") . "\n";
if (isset($result1['error'])) echo "Errore: {$result1['error']}\n";

echo "\nCreazione seconda prenotazione (T1, {$booking2['data']} 20:00-22:00) - DOVREBBE FALLIRE...\n";
$result2 = makeRequest('http://localhost/save_booking.php', $booking2);
echo "Risultato: " . ($result2['success'] ? "✗ ERRORE (non dovrebbe avere successo)" : "✓ SUCCESSO (conflitto rilevato)") . "\n";
if (isset($result2['error'])) {
    echo "Messaggio di errore: {$result2['error']}\n";
    echo "✓ Conflitto correttamente rilevato!\n";
}

// Test 2: Booking senza sovrapposizione
echo "\n\nTEST 2: Booking senza sovrapposizione\n";
echo "-----------------------------------------------------\n";

$booking3 = [
    'tableName' => 'T1',
    'cognome' => 'TestVerdi',
    'data' => date('Y-m-d', strtotime('+1 day')),
    'ora' => '21:00', // Dopo la prima prenotazione (19:00-21:00)
    'persone' => 4,
    'durata' => 2,
    'timestamp' => date('c')
];

echo "Creazione prenotazione senza conflitto (T1, {$booking3['data']} 21:00-23:00)...\n";
$result3 = makeRequest('http://localhost/save_booking.php', $booking3);
echo "Risultato: " . ($result3['success'] ? "✓ SUCCESSO (nessun conflitto)" : "✗ ERRORE") . "\n";
if (isset($result3['error'])) echo "Errore: {$result3['error']}\n";

// Test 3: Tavolo diverso - stessa ora
echo "\n\nTEST 3: Tavolo diverso - stessa ora\n";
echo "-----------------------------------------------------\n";

$booking4 = [
    'tableName' => 'T2',
    'cognome' => 'TestGialli',
    'data' => date('Y-m-d', strtotime('+1 day')),
    'ora' => '19:00', // Stessa ora di booking1, ma tavolo diverso
    'persone' => 4,
    'durata' => 2,
    'timestamp' => date('c')
];

echo "Creazione prenotazione su tavolo diverso (T2, {$booking4['data']} 19:00-21:00)...\n";
$result4 = makeRequest('http://localhost/save_booking.php', $booking4);
echo "Risultato: " . ($result4['success'] ? "✓ SUCCESSO (tavolo diverso)" : "✗ ERRORE") . "\n";
if (isset($result4['error'])) echo "Errore: {$result4['error']}\n";

// Test 4: Verifica durata salvata
echo "\n\nTEST 4: Verifica durata salvata nei booking\n";
echo "-----------------------------------------------------\n";

if (file_exists('bookings.json')) {
    $bookings = json_decode(file_get_contents('bookings.json'), true);
    $testBookings = array_filter($bookings, function($b) {
        return strpos($b['cognome'], 'Test') === 0 && $b['status'] === 'attiva';
    });
    
    echo "Prenotazioni di test trovate: " . count($testBookings) . "\n";
    foreach ($testBookings as $booking) {
        $hasDuration = isset($booking['durata']);
        echo "  - {$booking['cognome']} (Tavolo {$booking['tableName']}): ";
        echo $hasDuration ? "✓ Durata: {$booking['durata']} ore" : "✗ Durata mancante";
        echo "\n";
    }
}

// Test 5: Cancellazione e rilascio tavolo
echo "\n\nTEST 5: Cancellazione e rilascio tavolo\n";
echo "-----------------------------------------------------\n";

if (isset($result1['bookingId'])) {
    echo "Cancellazione prenotazione TestRossi...\n";
    $cancelResult = makeRequest('http://localhost/cancel_booking.php', ['id' => $result1['bookingId']]);
    echo "Risultato: " . ($cancelResult['success'] ? "✓ SUCCESSO" : "✗ ERRORE") . "\n";
    if (isset($cancelResult['error'])) echo "Errore: {$cancelResult['error']}\n";
    
    // Verifica stato tavolo
    if (file_exists('tables_conf.json')) {
        $tables = json_decode(file_get_contents('tables_conf.json'), true);
        $table = null;
        foreach ($tables['tables'] as $t) {
            if ($t['name'] === 'T1') {
                $table = $t;
                break;
            }
        }
        
        if ($table) {
            echo "Stato tavolo T1: {$table['currentStatus']}\n";
            echo "Note: Lo stato potrebbe non essere 'disponibile' se ci sono altre prenotazioni attive\n";
        }
    }
}

// Cleanup: Rimuovi prenotazioni di test
echo "\n\nPULIZIA: Rimozione prenotazioni di test\n";
echo "-----------------------------------------------------\n";

if (file_exists('bookings.json')) {
    $bookings = json_decode(file_get_contents('bookings.json'), true);
    $cleanBookings = array_filter($bookings, function($b) {
        return strpos($b['cognome'], 'Test') !== 0;
    });
    
    $removed = count($bookings) - count($cleanBookings);
    file_put_contents('bookings.json', json_encode(array_values($cleanBookings), JSON_PRETTY_PRINT));
    echo "Rimosse $removed prenotazioni di test\n";
}

echo "\n=== FINE TEST ===\n";
?>
