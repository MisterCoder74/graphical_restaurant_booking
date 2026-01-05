<?php
/**
 * Test diretto della logica di prenotazione (senza web server)
 */

date_default_timezone_set('Europe/Rome');

echo "=== TEST LOGICA BOOKING SYSTEM ===\n\n";

// Backup dei file originali
if (file_exists('bookings.json')) {
    copy('bookings.json', 'bookings.json.backup');
}
if (file_exists('tables_conf.json')) {
    copy('tables_conf.json', 'tables_conf.json.backup');
}

// Test 1: Simulazione conflict detection
echo "TEST 1: Conflict Detection Logic\n";
echo "-----------------------------------------------------\n";

$testBookings = [
    [
        'tableName' => 'T1',
        'cognome' => 'Existing',
        'data' => '2025-01-10',
        'ora' => '19:00',
        'durata' => 2,
        'status' => 'attiva'
    ]
];

// Nuovo booking che si sovrappone
$newBooking = [
    'tableName' => 'T1',
    'data' => '2025-01-10',
    'ora' => '20:00',
    'durata' => 2
];

$newStart = DateTime::createFromFormat('Y-m-d H:i', $newBooking['data'] . ' ' . $newBooking['ora']);
$newEnd = clone $newStart;
$newEnd->modify("+{$newBooking['durata']} hours");

echo "Nuovo booking: {$newBooking['data']} {$newBooking['ora']} - " . $newEnd->format('H:i') . "\n";

$hasConflict = false;
foreach ($testBookings as $existing) {
    if ($existing['tableName'] === $newBooking['tableName'] && $existing['status'] === 'attiva') {
        $existingStart = DateTime::createFromFormat('Y-m-d H:i', $existing['data'] . ' ' . $existing['ora']);
        $existingDuration = isset($existing['durata']) ? $existing['durata'] : 2;
        $existingEnd = clone $existingStart;
        $existingEnd->modify("+{$existingDuration} hours");
        
        echo "Booking esistente: {$existing['data']} {$existing['ora']} - " . $existingEnd->format('H:i') . "\n";
        
        // Check overlap: (new_start < existing_end) AND (new_end > existing_start)
        if ($newStart < $existingEnd && $newEnd > $existingStart) {
            $hasConflict = true;
            echo "✓ CONFLITTO RILEVATO!\n";
            echo "  Condizioni: new_start (" . $newStart->format('H:i') . ") < existing_end (" . $existingEnd->format('H:i') . ") = " . ($newStart < $existingEnd ? 'true' : 'false') . "\n";
            echo "              new_end (" . $newEnd->format('H:i') . ") > existing_start (" . $existingStart->format('H:i') . ") = " . ($newEnd > $existingStart ? 'true' : 'false') . "\n";
        }
    }
}

if (!$hasConflict) {
    echo "✗ NESSUN CONFLITTO (inaspettato!)\n";
}

// Test 2: Booking senza conflitto
echo "\n\nTEST 2: Booking senza conflitto\n";
echo "-----------------------------------------------------\n";

$newBooking2 = [
    'tableName' => 'T1',
    'data' => '2025-01-10',
    'ora' => '21:00', // Esattamente quando finisce il primo (19:00-21:00)
    'durata' => 2
];

$newStart2 = DateTime::createFromFormat('Y-m-d H:i', $newBooking2['data'] . ' ' . $newBooking2['ora']);
$newEnd2 = clone $newStart2;
$newEnd2->modify("+{$newBooking2['durata']} hours");

echo "Nuovo booking: {$newBooking2['data']} {$newBooking2['ora']} - " . $newEnd2->format('H:i') . "\n";

$hasConflict2 = false;
foreach ($testBookings as $existing) {
    if ($existing['tableName'] === $newBooking2['tableName'] && $existing['status'] === 'attiva') {
        $existingStart = DateTime::createFromFormat('Y-m-d H:i', $existing['data'] . ' ' . $existing['ora']);
        $existingDuration = isset($existing['durata']) ? $existing['durata'] : 2;
        $existingEnd = clone $existingStart;
        $existingEnd->modify("+{$existingDuration} hours");
        
        echo "Booking esistente: {$existing['data']} {$existing['ora']} - " . $existingEnd->format('H:i') . "\n";
        
        if ($newStart2 < $existingEnd && $newEnd2 > $existingStart) {
            $hasConflict2 = true;
            echo "✗ CONFLITTO RILEVATO (inaspettato!)\n";
        }
    }
}

if (!$hasConflict2) {
    echo "✓ NESSUN CONFLITTO! (booking contiguo, non sovrapposto)\n";
}

// Test 3: Status transitions
echo "\n\nTEST 3: Status Transitions Validation\n";
echo "-----------------------------------------------------\n";

function testStatusTransition($from, $to, $expected) {
    // Transizioni valide
    $validTransitions = [
        'disponibile' => ['prenotato', 'occupato'],
        'prenotato' => ['occupato', 'disponibile'],
        'occupato' => ['disponibile'],
    ];
    
    $isValid = false;
    if ($from === $to) {
        $isValid = true;
    } elseif (isset($validTransitions[$from]) && in_array($to, $validTransitions[$from])) {
        $isValid = true;
    } elseif ($to === 'disponibile') {
        $isValid = true;
    }
    
    $result = $isValid === $expected ? "✓" : "✗";
    echo "$result  $from → $to: " . ($isValid ? "VALIDA" : "NON VALIDA") . " (atteso: " . ($expected ? "valida" : "non valida") . ")\n";
}

testStatusTransition('disponibile', 'prenotato', true);
testStatusTransition('disponibile', 'occupato', true);
testStatusTransition('prenotato', 'occupato', true);
testStatusTransition('prenotato', 'disponibile', true);
testStatusTransition('occupato', 'disponibile', true);
testStatusTransition('occupato', 'prenotato', false); // Non valida
testStatusTransition('disponibile', 'disponibile', true);

// Test 4: Duration calculation for expiry
echo "\n\nTEST 4: Duration-based expiry check\n";
echo "-----------------------------------------------------\n";

$currentTime = new DateTime();
$bookingTime = clone $currentTime;
$bookingTime->modify('-3 hours'); // Prenotazione iniziata 3 ore fa

$duration = 2; // 2 ore di durata
$endTime = clone $bookingTime;
$endTime->modify("+{$duration} hours");

echo "Ora attuale: " . $currentTime->format('Y-m-d H:i') . "\n";
echo "Inizio booking: " . $bookingTime->format('Y-m-d H:i') . "\n";
echo "Fine booking: " . $endTime->format('Y-m-d H:i') . "\n";
echo "Booking scaduto? " . ($endTime < $currentTime ? "✓ SÌ" : "✗ NO") . "\n";

$bookingTime2 = clone $currentTime;
$bookingTime2->modify('-1 hour');
$endTime2 = clone $bookingTime2;
$endTime2->modify("+{$duration} hours");

echo "\nBooking 2:\n";
echo "Inizio: " . $bookingTime2->format('Y-m-d H:i') . "\n";
echo "Fine: " . $endTime2->format('Y-m-d H:i') . "\n";
echo "Booking scaduto? " . ($endTime2 < $currentTime ? "✗ SÌ (inaspettato)" : "✓ NO (ancora attivo)") . "\n";

echo "\n=== FINE TEST ===\n";
echo "\nI file originali sono stati preservati come .backup\n";
?>
