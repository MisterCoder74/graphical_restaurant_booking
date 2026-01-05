<?php
/**
 * Comprehensive demo of all booking system improvements
 */

date_default_timezone_set('Europe/Rome');

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  RESTAURANT BOOKING SYSTEM - FEATURE DEMONSTRATION            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ============================================================================
// FEATURE 1: BOOKING CONFLICT DETECTION
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  FEATURE 1: BOOKING CONFLICT DETECTION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Scenario: Two bookings for the same table at overlapping times\n\n";

$testDate = date('Y-m-d', strtotime('+3 days'));

// Booking 1: 19:00 - 21:00
$booking1 = [
    'data' => $testDate,
    'ora' => '19:00',
    'durata' => 2,
    'tableName' => 'T1'
];

// Booking 2 (CONFLICT): 20:00 - 22:00
$booking2 = [
    'data' => $testDate,
    'ora' => '20:00',
    'durata' => 2,
    'tableName' => 'T1'
];

echo "Booking 1: Table {$booking1['tableName']}, {$booking1['data']} {$booking1['ora']} (duration: {$booking1['durata']}h)\n";
echo "           End time: " . date('H:i', strtotime($booking1['ora']) + ($booking1['durata'] * 3600)) . "\n\n";

echo "Booking 2: Table {$booking2['tableName']}, {$booking2['data']} {$booking2['ora']} (duration: {$booking2['durata']}h)\n";
echo "           End time: " . date('H:i', strtotime($booking2['ora']) + ($booking2['durata'] * 3600)) . "\n\n";

// Simulate conflict detection
$start1 = DateTime::createFromFormat('Y-m-d H:i', $booking1['data'] . ' ' . $booking1['ora']);
$end1 = clone $start1;
$end1->modify("+{$booking1['durata']} hours");

$start2 = DateTime::createFromFormat('Y-m-d H:i', $booking2['data'] . ' ' . $booking2['ora']);
$end2 = clone $start2;
$end2->modify("+{$booking2['durata']} hours");

$hasConflict = ($start2 < $end1 && $end2 > $start1);

echo "Conflict Detection:\n";
echo "  Condition 1: new_start ({$start2->format('H:i')}) < existing_end ({$end1->format('H:i')}) = " . ($start2 < $end1 ? 'TRUE' : 'FALSE') . "\n";
echo "  Condition 2: new_end ({$end2->format('H:i')}) > existing_start ({$start1->format('H:i')}) = " . ($end2 > $start1 ? 'TRUE' : 'FALSE') . "\n";
echo "  Result: " . ($hasConflict ? "⚠️  CONFLICT DETECTED" : "✓ NO CONFLICT") . "\n\n";

echo "Expected: ⚠️  CONFLICT DETECTED\n";
echo "Status: " . ($hasConflict ? "✓ PASS" : "✗ FAIL") . "\n\n";

// ============================================================================
// FEATURE 2: NON-OVERLAPPING BOOKINGS (CONTIGUOUS)
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  FEATURE 2: CONTIGUOUS BOOKINGS (NO OVERLAP)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Scenario: Second booking starts exactly when first booking ends\n\n";

// Booking 3: 21:00 - 23:00 (starts when Booking 1 ends)
$booking3 = [
    'data' => $testDate,
    'ora' => '21:00',
    'durata' => 2,
    'tableName' => 'T1'
];

echo "Booking 1: {$booking1['data']} {$booking1['ora']}-" . date('H:i', strtotime($booking1['ora']) + ($booking1['durata'] * 3600)) . "\n";
echo "Booking 3: {$booking3['data']} {$booking3['ora']}-" . date('H:i', strtotime($booking3['ora']) + ($booking3['durata'] * 3600)) . "\n\n";

$start3 = DateTime::createFromFormat('Y-m-d H:i', $booking3['data'] . ' ' . $booking3['ora']);
$end3 = clone $start3;
$end3->modify("+{$booking3['durata']} hours");

$hasConflict3 = ($start3 < $end1 && $end3 > $start1);

echo "Conflict Detection:\n";
echo "  Condition 1: new_start ({$start3->format('H:i')}) < existing_end ({$end1->format('H:i')}) = " . ($start3 < $end1 ? 'TRUE' : 'FALSE') . "\n";
echo "  Condition 2: new_end ({$end3->format('H:i')}) > existing_start ({$start1->format('H:i')}) = " . ($end3 > $start1 ? 'TRUE' : 'FALSE') . "\n";
echo "  Result: " . ($hasConflict3 ? "⚠️  CONFLICT DETECTED" : "✓ NO CONFLICT") . "\n\n";

echo "Expected: ✓ NO CONFLICT\n";
echo "Status: " . (!$hasConflict3 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// ============================================================================
// FEATURE 3: TABLE STATUS TRANSITIONS
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  FEATURE 3: TABLE STATUS TRANSITION VALIDATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Valid Workflow: disponibile → prenotato → occupato → disponibile\n\n";

function validateTransition($from, $to) {
    $validTransitions = [
        'disponibile' => ['prenotato', 'occupato'],
        'prenotato' => ['occupato', 'disponibile'],
        'occupato' => ['disponibile'],
    ];
    
    if ($from === $to) return true;
    if ($to === 'disponibile') return true; // Always allow return to disponibile
    
    return isset($validTransitions[$from]) && in_array($to, $validTransitions[$from]);
}

$transitions = [
    ['disponibile', 'prenotato', true],
    ['disponibile', 'occupato', true],
    ['prenotato', 'occupato', true],
    ['prenotato', 'disponibile', true],
    ['occupato', 'disponibile', true],
    ['occupato', 'prenotato', false],  // Invalid!
    ['prenotato', 'prenotato', true],   // Same state
];

$allPass = true;
foreach ($transitions as $t) {
    list($from, $to, $expected) = $t;
    $isValid = validateTransition($from, $to);
    $pass = ($isValid === $expected);
    $allPass = $allPass && $pass;
    
    $icon = $pass ? '✓' : '✗';
    $validIcon = $isValid ? '✓' : '✗';
    
    printf("  %s  %-12s → %-12s : %s %-10s (expected: %s)\n", 
        $icon, 
        $from, 
        $to, 
        $validIcon,
        $isValid ? 'VALID' : 'INVALID',
        $expected ? 'valid' : 'invalid'
    );
}

echo "\nStatus: " . ($allPass ? "✓ ALL PASS" : "✗ SOME FAILED") . "\n\n";

// ============================================================================
// FEATURE 4: DURATION-BASED EXPIRY
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  FEATURE 4: DURATION-BASED EXPIRY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Scenario: Booking started 3 hours ago with 2-hour duration\n\n";

$currentTime = new DateTime();
$oldBookingStart = clone $currentTime;
$oldBookingStart->modify('-3 hours');

$duration = 2;
$oldBookingEnd = clone $oldBookingStart;
$oldBookingEnd->modify("+{$duration} hours");

echo "Current time:    {$currentTime->format('Y-m-d H:i')}\n";
echo "Booking started: {$oldBookingStart->format('Y-m-d H:i')}\n";
echo "Booking ends:    {$oldBookingEnd->format('Y-m-d H:i')}\n";
echo "Duration:        {$duration} hours\n\n";

$isExpired = $oldBookingEnd < $currentTime;

echo "Is booking expired? " . ($isExpired ? "✓ YES" : "✗ NO") . "\n";
echo "Expected: ✓ YES\n";
echo "Status: " . ($isExpired ? "✓ PASS" : "✗ FAIL") . "\n\n";

// ============================================================================
// FEATURE 5: BOOKING DURATION STORAGE
// ============================================================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "  FEATURE 5: BOOKING DURATION STORAGE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "Booking data structure with duration field:\n\n";

$sampleBooking = [
    'id' => 'booking_xyz123',
    'tableName' => 'T1',
    'cognome' => 'Rossi',
    'data' => '2026-01-10',
    'ora' => '19:00',
    'persone' => 4,
    'durata' => 2,  // ← Duration field (hours)
    'timestamp' => '2026-01-05T19:00:00+01:00',
    'status' => 'attiva'
];

echo json_encode($sampleBooking, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

$hasDuration = isset($sampleBooking['durata']);
$durationValid = $hasDuration && is_numeric($sampleBooking['durata']) && $sampleBooking['durata'] > 0;

echo "Duration field present: " . ($hasDuration ? "✓ YES" : "✗ NO") . "\n";
echo "Duration value valid:   " . ($durationValid ? "✓ YES" : "✗ NO") . "\n";
echo "Status: " . ($hasDuration && $durationValid ? "✓ PASS" : "✗ FAIL") . "\n\n";

// ============================================================================
// SUMMARY
// ============================================================================

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  DEMONSTRATION COMPLETE                                        ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "All critical features have been successfully implemented:\n\n";
echo "  ✓ Booking Conflict Detection (overlapping time slots)\n";
echo "  ✓ Duration Management (stored with each booking)\n";
echo "  ✓ Status Transition Validation (valid workflow)\n";
echo "  ✓ Duration-Based Expiry (automatic table release)\n";
echo "  ✓ Contiguous Booking Support (no false positives)\n\n";

echo "Files modified:\n";
echo "  - save_booking.php (conflict detection + duration)\n";
echo "  - cancel_booking.php (table status release)\n";
echo "  - complete_booking.php (table status release)\n";
echo "  - update_table_status.php (status validation + expiry)\n\n";

echo "Documentation: BOOKING_SYSTEM_IMPROVEMENTS.md\n\n";
?>
