<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$reservationsFile = '../data/reservations.json';

function loadReservations() {
    global $reservationsFile;
    if (!file_exists($reservationsFile)) {
        return ['reservations' => []];
    }
    $content = file_get_contents($reservationsFile);
    return json_decode($content, true) ?: ['reservations' => []];
}

function saveReservations($data) {
    global $reservationsFile;
    file_put_contents($reservationsFile, json_encode($data, JSON_PRETTY_PRINT));
}

function validateBookingConflict($newBooking, $existingBookings) {
    $newStart = strtotime($newBooking['date'] . ' ' . $newBooking['startTime']);
    $newEnd = $newStart + ($newBooking['duration'] * 60);
    
    foreach ($existingBookings as $booking) {
        if ($booking['tableId'] != $newBooking['tableId'] || $booking['status'] === 'cancelled') {
            continue;
        }
        
        $existingStart = strtotime($booking['date'] . ' ' . $booking['startTime']);
        $existingEnd = $existingStart + ($booking['duration'] * 60);
        
        // Check for overlap
        if ($newStart < $existingEnd && $newEnd > $existingStart) {
            return [
                'conflict' => true,
                'message' => 'Conflitto con prenotazione esistente: ' . $booking['guestName'] . 
                           ' (' . $booking['date'] . ' ' . $booking['startTime'] . ')'
            ];
        }
    }
    
    return ['conflict' => false];
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($action === 'list') {
                $data = loadReservations();
                $reservations = $data['reservations'];
                
                // Apply filters
                $tableId = $_GET['tableId'] ?? null;
                $date = $_GET['date'] ?? null;
                $status = $_GET['status'] ?? null;
                
                if ($tableId) {
                    $reservations = array_filter($reservations, fn($r) => $r['tableId'] == $tableId);
                }
                
                if ($date) {
                    $reservations = array_filter($reservations, fn($r) => $r['date'] === $date);
                }
                
                if ($status) {
                    $reservations = array_filter($reservations, fn($r) => $r['status'] === $status);
                }
                
                echo json_encode(['success' => true, 'data' => array_values($reservations)]);
            }
            break;
            
        case 'POST':
            if ($action === 'create') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                $required = ['tableId', 'guestName', 'numberOfGuests', 'date', 'startTime', 'duration'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        throw new Exception("Campo obbligatorio mancante: $field");
                    }
                }
                
                $data = loadReservations();
                
                // Validate guest count
                $tableId = intval($input['tableId']);
                if ($input['numberOfGuests'] > 12) { // Assuming max 12 seats per table
                    throw new Exception("Numero di ospiti troppo alto per questo tavolo");
                }
                
                // Check for conflicts
                $conflictCheck = validateBookingConflict($input, $data['reservations']);
                if ($conflictCheck['conflict']) {
                    echo json_encode(['success' => false, 'error' => $conflictCheck['message']]);
                    exit;
                }
                
                // Create new reservation
                $reservation = [
                    'id' => 'res_' . time() . '_' . rand(1000, 9999),
                    'tableId' => $tableId,
                    'guestName' => $input['guestName'],
                    'numberOfGuests' => intval($input['numberOfGuests']),
                    'date' => $input['date'],
                    'startTime' => $input['startTime'],
                    'duration' => intval($input['duration']),
                    'status' => 'upcoming',
                    'createdAt' => date('c'),
                    'cancelledAt' => null
                ];
                
                $data['reservations'][] = $reservation;
                saveReservations($data);
                
                echo json_encode(['success' => true, 'data' => $reservation]);
            }
            break;
            
        case 'PUT':
            if ($action === 'update') {
                $input = json_decode(file_get_contents('php://input'), true);
                $reservationId = $input['id'] ?? null;
                
                if (!$reservationId) {
                    throw new Exception("ID prenotazione mancante");
                }
                
                $data = loadReservations();
                $found = false;
                
                foreach ($data['reservations'] as &$reservation) {
                    if ($reservation['id'] === $reservationId) {
                        $found = true;
                        
                        if (isset($input['status'])) {
                            $reservation['status'] = $input['status'];
                            
                            if ($input['status'] === 'cancelled') {
                                $reservation['cancelledAt'] = date('c');
                            }
                        }
                        
                        break;
                    }
                }
                
                if (!$found) {
                    throw new Exception("Prenotazione non trovata");
                }
                
                saveReservations($data);
                echo json_encode(['success' => true, 'message' => 'Prenotazione aggiornata']);
            }
            break;
            
        case 'DELETE':
            if ($action === 'cancel') {
                $reservationId = $_GET['id'] ?? null;
                
                if (!$reservationId) {
                    throw new Exception("ID prenotazione mancante");
                }
                
                $data = loadReservations();
                $found = false;
                
                foreach ($data['reservations'] as &$reservation) {
                    if ($reservation['id'] === $reservationId) {
                        $found = true;
                        $reservation['status'] = 'cancelled';
                        $reservation['cancelledAt'] = date('c');
                        break;
                    }
                }
                
                if (!$found) {
                    throw new Exception("Prenotazione non trovata");
                }
                
                saveReservations($data);
                echo json_encode(['success' => true, 'message' => 'Prenotazione cancellata']);
            }
            break;
            
        default:
            throw new Exception('Metodo non supportato');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>