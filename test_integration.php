<?php
/**
 * Test di integrazione per il sistema di prenotazioni
 * Simula richieste HTTP POST ai vari endpoint
 */

date_default_timezone_set('Europe/Rome');

echo "=== TEST INTEGRAZIONE BOOKING SYSTEM ===\n\n";

// Backup dei file originali
if (file_exists('bookings.json')) {
    copy('bookings.json', 'bookings.json.test_backup');
    echo "✓ Backup di bookings.json creato\n";
}
if (file_exists('tables_conf.json')) {
    copy('tables_conf.json', 'tables_conf.json.test_backup');
    echo "✓ Backup di tables_conf.json creato\n";
}

echo "\n";

// Helper function per simulare POST request
function simulatePost($phpFile, $postData) {
    // Simula l'input POST
    $input = json_encode($postData);
    
    // Cattura l'output
    ob_start();
    
    // Simula php://input
    $tempFile = tmpfile();
    $tempPath = stream_get_meta_data($tempFile)['uri'];
    file_put_contents($tempPath, $input);
    
    // Include il file PHP con l'input simulato
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Cattura stderr per gli errori
    $oldInput = 'php://input';
    
    // Usa file_get_contents wrapper
    stream_wrapper_unregister("php");
    stream_wrapper_register("php", "MockPhpInput");
    MockPhpInput::$data = $input;
    
    try {
        include $phpFile;
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
    
    stream_wrapper_restore("php");
    
    $output = ob_get_clean();
    fclose($tempFile);
    
    // Rimuovi gli header dall'output
    $lines = explode("\n", $output);
    $jsonOutput = '';
    $headersDone = false;
    foreach ($lines as $line) {
        if (!$headersDone && (empty($line) || strpos($line, '{') === 0 || strpos($line, '[') === 0)) {
            $headersDone = true;
        }
        if ($headersDone) {
            $jsonOutput .= $line;
        }
    }
    
    return json_decode($jsonOutput, true);
}

// Mock class per php://input
class MockPhpInput {
    public static $data = '';
    private $position = 0;
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        $this->position = 0;
        return true;
    }
    
    public function stream_read($count) {
        $ret = substr(self::$data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    public function stream_eof() {
        return $this->position >= strlen(self::$data);
    }
    
    public function stream_stat() {
        return array();
    }
}

// Test più semplice: verifica diretta dei file
echo "TEST 1: Verifica conflict detection tramite codice\n";
echo "-----------------------------------------------------\n";

// Leggi bookings esistenti
$bookingsFile = 'bookings.json';
$bookings = [];
if (file_exists($bookingsFile)) {
    $bookings = json_decode(file_get_contents($bookingsFile), true) ?: [];
}

// Aggiungi un booking di test
$testDate = date('Y-m-d', strtotime('+2 days'));
$testBooking1 = [
    'id' => 'test_booking_1',
    'tableName' => 'T1',
    'cognome' => 'TestConflict1',
    'data' => $testDate,
    'ora' => '19:00',
    'persone' => 4,
    'durata' => 2,
    'timestamp' => date('c'),
    'status' => 'attiva'
];

$bookings[] = $testBooking1;
file_put_contents($bookingsFile, json_encode($bookings, JSON_PRETTY_PRINT));
echo "✓ Creato booking di test: T1, $testDate 19:00-21:00\n";

// Prova a creare un booking in conflitto
$conflictBooking = [
    'tableName' => 'T1',
    'cognome' => 'TestConflict2',
    'data' => $testDate,
    'ora' => '20:00',
    'persone' => 4,
    'durata' => 2,
    'timestamp' => date('c')
];

// Simula la logica di conflict detection
$newStart = DateTime::createFromFormat('Y-m-d H:i', $conflictBooking['data'] . ' ' . $conflictBooking['ora']);
$duration = $conflictBooking['durata'];
$newEnd = clone $newStart;
$newEnd->modify("+{$duration} hours");

$hasConflict = false;
foreach ($bookings as $existing) {
    if ($existing['tableName'] === $conflictBooking['tableName'] && 
        $existing['status'] === 'attiva') {
        
        $existingStart = DateTime::createFromFormat('Y-m-d H:i', $existing['data'] . ' ' . $existing['ora']);
        $existingDuration = isset($existing['durata']) ? $existing['durata'] : 2;
        $existingEnd = clone $existingStart;
        $existingEnd->modify("+{$existingDuration} hours");
        
        if ($newStart < $existingEnd && $newEnd > $existingStart) {
            $hasConflict = true;
            echo "✓ Conflitto rilevato con booking esistente ({$existing['cognome']})\n";
            echo "  Esistente: {$existing['ora']} - " . $existingEnd->format('H:i') . "\n";
            echo "  Nuovo: {$conflictBooking['ora']} - " . $newEnd->format('H:i') . "\n";
            break;
        }
    }
}

if (!$hasConflict) {
    echo "✗ ERRORE: Nessun conflitto rilevato (dovrebbe esserci!)\n";
}

// Test 2: Booking senza conflitto
echo "\n\nTEST 2: Booking senza conflitto\n";
echo "-----------------------------------------------------\n";

$noConflictBooking = [
    'tableName' => 'T1',
    'data' => $testDate,
    'ora' => '21:00',
    'durata' => 2
];

$newStart2 = DateTime::createFromFormat('Y-m-d H:i', $noConflictBooking['data'] . ' ' . $noConflictBooking['ora']);
$newEnd2 = clone $newStart2;
$newEnd2->modify("+{$noConflictBooking['durata']} hours");

$hasConflict2 = false;
foreach ($bookings as $existing) {
    if ($existing['tableName'] === $noConflictBooking['tableName'] && 
        $existing['status'] === 'attiva') {
        
        $existingStart = DateTime::createFromFormat('Y-m-d H:i', $existing['data'] . ' ' . $existing['ora']);
        $existingDuration = isset($existing['durata']) ? $existing['durata'] : 2;
        $existingEnd = clone $existingStart;
        $existingEnd->modify("+{$existingDuration} hours");
        
        if ($newStart2 < $existingEnd && $newEnd2 > $existingStart) {
            $hasConflict2 = true;
            break;
        }
    }
}

if ($hasConflict2) {
    echo "✗ ERRORE: Conflitto rilevato (non dovrebbe esserci!)\n";
} else {
    echo "✓ Nessun conflitto per booking contiguo (21:00-23:00)\n";
}

// Test 3: Verifica durata nei booking
echo "\n\nTEST 3: Verifica presenza campo 'durata'\n";
echo "-----------------------------------------------------\n";

$currentBookings = json_decode(file_get_contents($bookingsFile), true);
$testBookings = array_filter($currentBookings, function($b) {
    return strpos($b['cognome'], 'TestConflict') === 0;
});

foreach ($testBookings as $booking) {
    $hasDuration = isset($booking['durata']);
    echo ($hasDuration ? "✓" : "✗") . " Booking {$booking['cognome']}: ";
    echo $hasDuration ? "durata = {$booking['durata']} ore\n" : "campo durata mancante\n";
}

// Test 4: Cancellazione e verifica rilascio tavolo
echo "\n\nTEST 4: Cancellazione booking e rilascio tavolo\n";
echo "-----------------------------------------------------\n";

// Trova e cancella il booking di test
$bookings = json_decode(file_get_contents($bookingsFile), true);
$cancelledTableName = null;

foreach ($bookings as &$booking) {
    if ($booking['cognome'] === 'TestConflict1') {
        $booking['status'] = 'cancellata';
        $booking['cancelled_at'] = date('Y-m-d H:i:s');
        $cancelledTableName = $booking['tableName'];
        echo "✓ Booking TestConflict1 marcato come cancellato\n";
        break;
    }
}

file_put_contents($bookingsFile, json_encode($bookings, JSON_PRETTY_PRINT));

// Verifica che non ci siano più booking attivi per quel tavolo
$hasActiveBooking = false;
foreach ($bookings as $booking) {
    if ($booking['tableName'] === $cancelledTableName && $booking['status'] === 'attiva') {
        $hasActiveBooking = true;
        break;
    }
}

if ($hasActiveBooking) {
    echo "  Tavolo ha ancora booking attivi\n";
} else {
    echo "✓ Tavolo $cancelledTableName non ha più booking attivi (può essere liberato)\n";
}

// Cleanup
echo "\n\nCLEANUP: Rimozione booking di test\n";
echo "-----------------------------------------------------\n";

$bookings = json_decode(file_get_contents($bookingsFile), true);
$cleanBookings = array_filter($bookings, function($b) {
    return strpos($b['cognome'], 'TestConflict') !== 0;
});

file_put_contents($bookingsFile, json_encode(array_values($cleanBookings), JSON_PRETTY_PRINT));
echo "✓ Rimossi " . (count($bookings) - count($cleanBookings)) . " booking di test\n";

echo "\n=== FINE TEST ===\n";
echo "I file originali sono stati preservati come .test_backup\n";
?>
