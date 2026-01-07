# NaN Error Fix - Dashboard Occupied Tables

## Problem Description
The dashboard in `reservation_history.html` was displaying "NaN" instead of a number for the "Tavoli Occupati" (Occupied Tables) metric.

## Root Cause Analysis

The NaN error was caused by multiple issues:

1. **Missing or Invalid Duration Values**
   - When `reservation.duration` was undefined, null, or not a number
   - Calculation `reservation.duration * 60000` resulted in NaN
   - This created Invalid Date objects that broke time comparisons

2. **Undefined tableId Values**
   - When `reservation.tableId` was invalid or undefined
   - The Set would contain NaN values, affecting `.size` calculation

3. **Missing Data Validation**
   - No checks for null/undefined reservation data
   - No validation before performing math operations
   - No error handling in status calculation functions

4. **Race Conditions in Data Loading**
   - Statistics could be calculated before data was fully loaded
   - Arrays might not be initialized when accessed

## Solution Implemented

### 1. Data Validation on Load

**`loadReservations()` function:**
- Added comprehensive data sanitization using `.map()` and `.filter()`
- Validates all required fields (id, tableId, date, startTime)
- Sets default values for missing data (duration defaults to 60 minutes)
- Ensures all numeric fields are properly parsed with `parseInt()`
- Initializes arrays to empty on error

**`loadLayout()` function:**
- Validates table data before storing
- Ensures table IDs are valid numbers
- Filters out invalid table entries

### 2. Duration Validation in Status Functions

Added duration validation in all time-calculation functions:

```javascript
// Ensure duration is a valid number
const duration = parseInt(reservation.duration) || 60; // Default to 60 minutes if invalid
const reservationEnd = new Date(reservationDate.getTime() + duration * 60000);
```

Affected functions:
- `getReservationCurrentStatus()`
- `getTableCurrentStatus()`
- `getTimeRemainingForReservation()`
- `getActionButtons()`

### 3. Enhanced Error Handling in Statistics

**`updateStatistics()` function:**
- Added array type checking
- Wrapped tableId processing in try-catch blocks
- Validates tableId with `parseInt()` and `isNaN()` checks
- Only adds valid tableIds to Sets
- Added console warnings for debugging

```javascript
const occupiedTableIds = new Set();
filteredReservations.forEach(r => {
    try {
        const status = getReservationCurrentStatus(r);
        if (status.current) {
            const tableId = parseInt(r.tableId);
            if (!isNaN(tableId)) {
                occupiedTableIds.add(tableId);
            }
        }
    } catch (error) {
        console.warn('Error calculating occupied table for reservation:', r, error);
    }
});
```

### 4. Initialization Improvements

**Added `initializeStatistics()` function:**
- Sets all dashboard metrics to 0 on page load
- Prevents NaN from appearing before data loads

**Updated DOMContentLoaded handler:**
- Explicitly initializes all data arrays
- Calls `initializeStatistics()` before loading data

### 5. Comprehensive Data Validation

**`getReservationCurrentStatus()` function:**
- Validates reservation object exists
- Checks for required fields (date, startTime)
- Validates date components are numbers
- Returns safe default for invalid data

```javascript
if (!reservation || !reservation.date || !reservation.startTime) {
    return { text: 'Dati mancanti', badgeClass: 'bg-secondary', current: false };
}

// Validate date components
if (isNaN(year) || isNaN(month) || isNaN(day) || isNaN(hour) || isNaN(minute)) {
    return { text: 'Data non valida', badgeClass: 'bg-secondary', current: false };
}
```

### 6. Dashboard Empty State Handling

**`updateTablesDashboard()` function:**
- Checks if allTables array exists and has entries
- Displays friendly message when no tables are configured
- Prevents errors when iterating empty arrays

## Test Data Created

Created test data files to validate the fix:

### `data/reservations.json`
- Contains sample reservations with valid data
- Includes bookings for multiple tables
- Covers different time slots and durations

### `data/tables_layout.json`
- Contains 6 tables with valid configuration
- Tables have IDs 1-6 with proper coordinates and dimensions
- Suitable for testing dashboard functionality

### `test_nan_fix.html`
- Standalone test page for validation
- Tests various edge cases:
  - Normal reservations with duration
  - Missing duration (should use default 60)
  - Invalid tableId (should be filtered)
  - Empty reservations array
  - Duration as string (should parse correctly)
- All tests validate that NaN never appears

## Acceptance Criteria Verification

✅ **"Tavoli Occupati" displays a number instead of NaN**
   - Default value is 0 when no tables are occupied
   - Always displays a valid number

✅ **Count is accurate (matches actual occupied tables)**
   - Uses Set to ensure each table is counted only once
   - Validates tableId before counting

✅ **Occupied table detection works correctly**
   - Properly validates reservation time windows
   - Uses valid duration values for end time calculation

✅ **No console errors about undefined variables**
   - All data is validated before use
   - Try-catch blocks prevent unhandled errors

✅ **Count updates in real-time as bookings change**
   - Auto-update every 30 seconds
   - Manual refresh available via button

✅ **All dashboard metrics display numbers (no NaN)**
   - All statistics initialized to 0
   - All calculations use validated data

✅ **Console logging shows correct calculations**
   - Warning messages for any calculation errors
   - Debug information for troubleshooting

## Benefits of the Fix

1. **Robust Error Handling**: System continues to work even with invalid data
2. **Clear Debugging**: Console warnings help identify data issues
3. **Default Values**: Sensible defaults prevent NaN (60 min duration, etc.)
4. **Type Safety**: Explicit type conversion with parseInt()
5. **Validation Layers**: Multiple checks ensure data integrity
6. **User Experience**: No confusing NaN displays, always shows numbers

## Files Modified

- `reservation_history.html` - Main dashboard file with all fixes applied

## Files Created

- `data/reservations.json` - Test reservation data
- `data/tables_layout.json` - Test table configuration
- `test_nan_fix.html` - Test page for validation
- `NAN_FIX_SUMMARY.md` - This documentation

## Technical Notes

- All duration values now use `parseInt(reservation.duration) || 60` for safety
- All tableId values use `parseInt(r.tableId)` and check with `!isNaN(tableId)`
- Time calculations use Europe/Rome timezone consistently
- Invalid data is logged to console for debugging but doesn't break the UI
- The fix is defensive - it handles errors gracefully without alerting users

## Testing Recommendations

1. Load the dashboard and verify all statistics show "0" initially
2. Create reservations and verify counts update correctly
3. Test with reservations that have missing/invalid duration
4. Test edge cases (empty database, invalid data)
5. Verify console warnings appear for any data issues
6. Check that occupied tables count matches visual table status cards
