# Restaurant Booking System - Critical Improvements

## Overview

This document describes the critical functionality improvements implemented for the restaurant booking system to prevent double-bookings and ensure data consistency.

## Features Implemented

### 1. Booking Conflict Detection

**Location:** `save_booking.php`

#### Implementation Details:
- Before creating a new booking, the system validates that the table is not already booked for the requested time slot
- Checks for overlapping time slots using booking duration (start time + duration)
- Only considers "attiva" (active) bookings for conflict detection
- Returns clear, user-friendly error messages when conflicts are detected

#### Conflict Detection Logic:
```php
// A conflict exists if:
(new_start < existing_end) AND (new_end > existing_start)
```

#### Example Error Message:
```
"Conflitto di prenotazione: Il tavolo T1 è già prenotato per 2026-01-10 dalle 19:00 alle 21:00 (prenotazione di Rossi)"
```

#### Edge Cases Handled:
- Contiguous bookings (no overlap): 19:00-21:00 and 21:00-23:00 → ✓ ALLOWED
- Overlapping bookings: 19:00-21:00 and 20:00-22:00 → ✗ REJECTED
- Different tables, same time: ✓ ALLOWED
- Expired bookings are ignored for conflict detection

---

### 2. Booking Duration Management

**Location:** `save_booking.php`, `cancel_booking.php`, `complete_booking.php`, `update_table_status.php`

#### Implementation Details:
- Each booking now stores a `durata` (duration) field in hours
- Default duration: 2 hours (if not specified)
- Duration is used to calculate the end time for conflict detection
- Duration is validated (must be > 0)

#### Booking Data Structure:
```json
{
    "id": "booking_xyz",
    "tableName": "T1",
    "cognome": "Rossi",
    "data": "2026-01-10",
    "ora": "19:00",
    "persone": 4,
    "durata": 2,
    "timestamp": "2026-01-05T19:00:00+01:00",
    "status": "attiva"
}
```

#### Duration Usage:
- **Conflict Detection**: Calculate booking end time
- **Status Updates**: Determine if a booking has expired
- **Table Release**: Automatically free tables after booking duration expires

---

### 3. Table Status Consistency

**Location:** `update_table_status.php`, `cancel_booking.php`, `complete_booking.php`

#### Valid Status Workflow:
```
disponibile → prenotato → occupato → disponibile
```

#### Status Transition Rules:

| From State | To State | Valid? | Use Case |
|------------|----------|--------|----------|
| disponibile | prenotato | ✓ | New future booking |
| disponibile | occupato | ✓ | Immediate booking/walk-in |
| prenotato | occupato | ✓ | Booking time arrives |
| prenotato | disponibile | ✓ | Booking cancelled |
| occupato | disponibile | ✓ | Table freed |
| occupato | prenotato | ✗ | Invalid transition |

#### Status Validation Function:
```php
function isValidStatusTransition($oldStatus, $newStatus) {
    $validTransitions = [
        'disponibile' => ['prenotato', 'occupato'],
        'prenotato' => ['occupato', 'disponibile'],
        'occupato' => ['disponibile'],
    ];
    
    // Special rule: Always allow returning to disponibile
    if ($newStatus === 'disponibile') {
        return true;
    }
    
    return isset($validTransitions[$oldStatus]) && 
           in_array($newStatus, $validTransitions[$oldStatus]);
}
```

---

### 4. Automatic Table Release

**Location:** `cancel_booking.php`, `complete_booking.php`

#### Implementation Details:
- When a booking is cancelled or completed, the table status is automatically recalculated
- System checks for remaining active bookings for that table
- If no active bookings remain (or all have expired), table is set to `disponibile`
- Status updates are logged in table history

#### Cancellation Flow:
1. Mark booking as `cancellata` with timestamp
2. Check for other active bookings on the same table
3. If no active bookings, update table status to `disponibile`
4. Add history entry to table configuration
5. Save both files atomically

#### History Entry Example:
```json
{
    "type": "cancellazione_prenotazione",
    "old_status": "occupato",
    "new_status": "disponibile",
    "timestamp": "2026-01-05T19:00:00+01:00"
}
```

---

### 5. Duration-Based Expiry

**Location:** `update_table_status.php`

#### Implementation Details:
- Automatic status updates now consider booking duration
- Bookings that have passed their end time (start + duration) are ignored
- Tables are automatically freed when all bookings have expired

#### Logic:
```php
$bookingEndDateTime = clone $bookingStartDateTime;
$bookingEndDateTime->modify("+{$duration} hours");

// Skip expired bookings
if ($bookingEndDateTime < $currentDateTime) {
    continue; // Don't affect table status
}
```

---

## API Response Format

All endpoints maintain backward compatibility with existing response format:

### Success Response:
```json
{
    "success": true,
    "message": "Prenotazione salvata con successo",
    "bookingId": "booking_xyz",
    "tableStatus": "prenotato"
}
```

### Error Response (Conflict):
```json
{
    "success": false,
    "error": "Conflitto di prenotazione: Il tavolo T1 è già prenotato per 2026-01-10 dalle 19:00 alle 21:00 (prenotazione di Rossi)"
}
```

### Error Response (Invalid Transition):
```json
{
    "success": false,
    "error": "Transizione di stato non valida: occupato → prenotato"
}
```

---

## Modified Files

1. **save_booking.php**
   - Added duration field support (default: 2 hours)
   - Implemented conflict detection logic
   - Added duration-based end time calculation
   - Added validation for date/time format
   - Enhanced error messages

2. **cancel_booking.php**
   - Added automatic table status recalculation
   - Checks for remaining active bookings
   - Updates table configuration when booking is cancelled
   - Adds history entries for status changes

3. **complete_booking.php**
   - Added automatic table status recalculation
   - Checks for remaining active bookings
   - Updates table configuration when booking is completed
   - Adds history entries for status changes

4. **update_table_status.php**
   - Added duration support for status calculation
   - Implemented status transition validation function
   - Skip expired bookings (past end time)
   - Added validation before applying status changes

---

## Testing

### Test Files Created:

1. **test_booking_logic.php** - Unit tests for core logic
   - Conflict detection algorithm
   - Status transition validation
   - Duration-based expiry
   - Tests run independently of web server

2. **test_integration.php** - Integration tests
   - End-to-end booking flow
   - Conflict detection with real data
   - Cancellation and table release
   - Data persistence verification

### Running Tests:

```bash
# Run logic tests
php test_booking_logic.php

# Run integration tests
php test_integration.php
```

### Test Results:
All tests pass successfully:
- ✓ Conflict detection for overlapping bookings
- ✓ No conflict for contiguous bookings
- ✓ Status transition validation
- ✓ Duration-based expiry checks
- ✓ Table release after cancellation

---

## Usage Examples

### Creating a Booking with Duration:

```javascript
const bookingData = {
    tableName: 'T1',
    cognome: 'Rossi',
    data: '2026-01-10',
    ora: '19:00',
    persone: 4,
    durata: 2,  // 2 hours (optional, defaults to 2)
    timestamp: new Date().toISOString()
};

const response = await fetch('save_booking.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(bookingData)
});

const result = await response.json();
if (!result.success) {
    // Handle conflict or other error
    alert(result.error);
}
```

### Frontend Handling of Conflicts:

```javascript
if (!result.success && result.error.includes('Conflitto')) {
    // Show user-friendly conflict message
    showConflictDialog(result.error);
} else if (!result.success) {
    // Show generic error
    alert('Errore: ' + result.error);
}
```

---

## Backward Compatibility

### Duration Field:
- If `durata` is not provided in a booking request, defaults to 2 hours
- Existing bookings without `durata` field are treated as 2-hour bookings
- No breaking changes to existing API contracts

### Status Transitions:
- Validation is applied but doesn't break existing flows
- Invalid transitions are logged but don't cause hard failures
- Always allows transition to `disponibile` for safety

### Conflict Detection:
- Only checks against active (`attiva`) bookings
- Ignores cancelled (`cancellata`) and completed (`completata`) bookings
- Expired bookings (past end time) are automatically ignored

---

## Edge Cases Handled

1. **Contiguous Bookings**: 19:00-21:00 followed by 21:00-23:00 → ✓ ALLOWED
2. **Same Minute Start**: If booking ends at 21:00 and another starts at 21:00 → ✓ ALLOWED
3. **Multiple Active Bookings**: Only the nearest future booking affects table status
4. **Expired Bookings**: Past bookings don't cause conflicts or affect table status
5. **Invalid Date/Time**: Returns clear error message for malformed dates
6. **Missing Duration**: Defaults to 2 hours, no error
7. **Zero/Negative Duration**: Returns validation error
8. **Concurrent Cancellations**: Last cancellation wins, table status recalculated each time

---

## Performance Considerations

- Conflict detection loops through all active bookings (O(n))
- For small to medium restaurants (<100 active bookings), performance is negligible
- File locking prevents race conditions during concurrent writes
- Consider database migration for high-volume scenarios (>500 bookings/day)

---

## Future Enhancements

Potential improvements for future iterations:

1. **Database Migration**: Replace JSON files with MySQL/PostgreSQL
2. **Booking Duration UI**: Add duration selector in frontend forms
3. **Variable Default Duration**: Different defaults for lunch vs dinner
4. **Overbooking Protection**: System-wide booking limits
5. **Automatic Reminders**: Send notifications before booking time
6. **Waitlist System**: Queue bookings for fully-booked tables
7. **Multi-Table Bookings**: Support for large groups spanning multiple tables

---

## Maintenance Notes

- Backup files are created automatically during tests (.backup, .test_backup)
- Clean up test backup files periodically
- Monitor error logs for invalid status transitions
- Review booking history for unusual patterns

---

## Support

For issues or questions about the booking system improvements, refer to:
- Error logs: Check PHP error_log for detailed conflict information
- Test files: Run test scripts to verify functionality
- This documentation: Updated with each major change
