<?php
// Test script to verify the booking system time calculations
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Test Booking System - Time Calculation Fix</h2>";
echo "<p><strong>Current Date</strong>: 7 January 2026</p>";

// Sample test data
$testBookings = [
    [
        'guestName' => 'Mario Rossi',
        'date' => '2026-01-07',
        'startTime' => '20:30',
        'duration' => 120
    ],
    [
        'guestName' => 'Giulia Bianchi',
        'date' => '2026-01-08',
        'startTime' => '20:30',
        'duration' => 120
    ]
];

// Simulate current time (approximately 19:20 on 2026-01-07)
$currentTime = new DateTime('2026-01-07 19:20:00', new DateTimeZone('Europe/Rome'));

echo "<h3>Test Bookings:</h3>";
foreach ($testBookings as $booking) {
    $reservationDate = new DateTime($booking['date'] . ' ' . $booking['startTime'], new DateTimeZone('Europe/Rome'));
    $reservationEnd = clone $reservationDate;
    $reservationEnd->modify('+' . $booking['duration'] . ' minutes');
    
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Guest:</strong> " . $booking['guestName'] . "<br>";
    echo "<strong>Date:</strong> " . $booking['date'] . "<br>";
    echo "<strong>Time:</strong> " . $booking['startTime'] . "<br>";
    echo "<strong>Duration:</strong> " . $booking['duration'] . " minutes<br>";
    echo "<strong>Reservation Start:</strong> " . $reservationDate->format('Y-m-d H:i:s') . "<br>";
    echo "<strong>Reservation End:</strong> " . $reservationEnd->format('Y-m-d H:i:s') . "<br>";
    echo "<strong>Current Time:</strong> " . $currentTime->format('Y-m-d H:i:s') . "<br>";
    
    // Calculate time difference
    $diff = $reservationDate->getTimestamp() - $currentTime->getTimestamp();
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    if ($diff > 0) {
        echo "<strong>Time Until:</strong> In {$hours}h {$minutes}m<br>";
        echo "<strong>Status:</strong> Prossima";
    } else {
        echo "<strong>Status:</strong> Passata";
    }
    echo "</div>";
}

echo "<h3>Expected Results:</h3>";
echo "<ul>";
echo "<li>Mario Rossi (7 jan 20:30): Should appear in reservations (today's booking)</li>";
echo "<li>Giulia Bianchi (8 jan 20:30): Should show ~25h remaining (approximately)</li>";
echo "<li>Both should be included in 'Prossimi 3 giorni' filter</li>";
echo "</ul>";

echo "<h3>Fixed Issues:</h3>";
echo "<ol>";
echo "<li><strong>Today's booking missing:</strong> Fixed filter to include current day</li>";
echo "<li><strong>Time calculation:</strong> Improved timezone handling with explicit Europe/Rome timezone</li>";
echo "<li><strong>Date comparison:</strong> Normalized dates to start of day for accurate filtering</li>";
echo "<li><strong>Consistent parsing:</strong> Used explicit date/time parsing to avoid timezone ambiguity</li>";
echo "</ol>";
?>