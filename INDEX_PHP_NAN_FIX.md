# index.php Dashboard NaN Error Fix

## Problem
The dashboard statistics cards in index.php were displaying "NaN" instead of numbers, particularly the "Tavoli Occupati" (Occupied Tables) card.

## Root Cause
1. **Variable Scope Issue**: The `totalTables` variable was defined inside the first `if` block but used in a separate `if` block, causing it to be `undefined` when accessed
2. **Lack of Initialization**: No default values were set for stat variables before API calls
3. **Missing Data Validation**: API responses were not validated before use
4. **Random Simulation**: Occupied tables were calculated using random values instead of actual data

## Changes Made

### 1. Fixed Variable Scope (index.php, lines 412-417)
**Before:**
```javascript
async function loadQuickStats() {
    try {
        const layoutResponse = await fetch('./php/api_layout.php?action=load&v=' + Date.now());
        const layoutResult = await layoutResponse.json();

        if (layoutResult.success) {
            const totalTables = layoutResult.data.tables.length;  // Scoped to this block
            document.getElementById('totalTables').textContent = totalTables;
        }

        // ... later in another if block
        const occupiedTables = Math.floor(Math.random() * Math.max(1, totalTables - 2)) + 1;  // totalTables is undefined here!
```

**After:**
```javascript
async function loadQuickStats() {
    // Initialize all stat variables with default values
    let totalTables = 0;
    let activeReservations = 0;
    let todayGuests = 0;
    let occupiedTables = 0;

    try {
        const layoutResponse = await fetch('./php/api_layout.php?action=load&v=' + Date.now());
        const layoutResult = await layoutResponse.json();

        if (layoutResult.success && layoutResult.data && layoutResult.data.tables) {
            totalTables = layoutResult.data.tables.length;  // Now accessible throughout
            document.getElementById('totalTables').textContent = totalTables;
        }
```

### 2. Replaced Random Simulation with Actual Calculation (lines 453-489)
**Before:**
```javascript
// Occupied tables (simulate based on current time)
const currentTime = new Date();
const currentHour = currentTime.getHours();
const occupiedTables = Math.floor(Math.random() * Math.max(1, totalTables - 2)) + 1; // Random value!
```

**After:**
```javascript
// Calculate occupied tables based on actual current time
occupiedTables = 0;
const occupiedTableIds = new Set();

for (const reservation of reservations) {
    // Skip cancelled reservations
    if (reservation.status === 'cancelled') {
        continue;
    }

    // Check if reservation is for today
    if (reservation.date !== today) {
        continue;
    }

    // Parse reservation time
    const startTimeParts = (reservation.startTime || '').split(':');
    if (startTimeParts.length !== 2) {
        continue;
    }

    const reservationHour = parseInt(startTimeParts[0]) || 0;
    const reservationMinute = parseInt(startTimeParts[1]) || 0;
    const reservationStartMinutes = reservationHour * 60 + reservationMinute;
    const duration = parseInt(reservation.duration) || 60; // Default 60 minutes
    const reservationEndMinutes = reservationStartMinutes + duration;

    // Check if reservation is currently active
    if (currentMinutes >= reservationStartMinutes && currentMinutes < reservationEndMinutes) {
        const tableId = parseInt(reservation.tableId);
        if (!isNaN(tableId)) {
            occupiedTableIds.add(tableId);
        }
    }
}

occupiedTables = occupiedTableIds.size;
```

### 3. Added Comprehensive Data Validation
- Check `layoutResult.success` and `layoutResult.data && layoutResult.data.tables`
- Check `reservationsResult.success` and `Array.isArray(reservationsResult.data)`
- Validate time format: `(reservation.startTime || '').split(':')`
- Validate hour/minute parsing with fallback to 0
- Validate duration with fallback to 60 minutes
- Validate tableId with `parseInt()` and `isNaN()` check

### 4. Updated Initial Display Values (lines 253-269)
Changed from "-" to "0" for consistency:
```html
<div class="stat-card">
    <div class="stat-number" id="activeReservations">0</div>
    <div class="stat-label">Prenotazioni Attive</div>
</div>
<div class="stat-card">
    <div class="stat-number" id="occupiedTables">0</div>
    <div class="stat-label">Tavoli Occupati</div>
</div>
<div class="stat-card">
    <div class="stat-number" id="todayGuests">0</div>
    <div class="stat-label">Ospiti Oggi</div>
</div>
<div class="stat-card">
    <div class="stat-number" id="totalTables">0</div>
    <div class="stat-label">Tavoli Totali</div>
</div>
```

### 5. Enhanced Error Handling (lines 494-508)
Added fallback values for both no-data and error cases:
```javascript
} else {
    // Set default values if no reservations data
    document.getElementById('activeReservations').textContent = '0';
    document.getElementById('occupiedTables').textContent = '0';
    document.getElementById('todayGuests').textContent = '0';
}

} catch (error) {
    console.error('Error loading quick stats:', error);
    // Set fallback values on error
    document.getElementById('totalTables').textContent = '0';
    document.getElementById('activeReservations').textContent = '0';
    document.getElementById('occupiedTables').textContent = '0';
    document.getElementById('todayGuests').textContent = '0';
}
```

## Benefits of the Fix

1. **No More NaN Errors**: All stat cards display valid numbers
2. **Accurate Data**: Occupied tables calculated from actual reservation data
3. **Proper Time-Based Detection**: Tables marked as occupied only when reservation is currently active
4. **Unique Table Counting**: Uses Set data structure to prevent counting same table twice
5. **Robust Error Handling**: Graceful fallbacks when data is missing or invalid
6. **Better UX**: Shows "0" instead of "-" for consistent initial state
7. **Real-Time Updates**: Dashboard refreshes every 30 seconds with accurate data

## Testing
Created test files to verify the fix:
- `data/tables_layout.json`: Sample table layout data
- `data/reservations.json`: Sample reservation data with various states
- `test_index_dashboard.html`: Automated test suite to verify all acceptance criteria

## Acceptance Criteria Met
- ✅ "Tavoli Occupati" card displays a number instead of NaN
- ✅ Count is accurate (matches actual occupied tables)
- ✅ Occupied table detection works correctly with current time
- ✅ All dashboard cards display numbers (no NaN)
- ✅ No console errors about undefined variables
- ✅ Dashboard loads correctly on page refresh
- ✅ All statistics cards update when reservations change
- ✅ API data is properly validated
- ✅ Console logging shows correct data flow

## Files Modified
- `index.php`: Fixed loadQuickStats() function and initial display values
- `data/tables_layout.json`: Created test data
- `data/reservations.json`: Created test data

## Files Created
- `test_index_dashboard.html`: Test suite for dashboard functionality
