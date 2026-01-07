<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$layoutFile = '../data/tables_layout.json';

function loadLayout() {
    global $layoutFile;
    if (!file_exists($layoutFile)) {
        return [
            'tables' => [],
            'canvasWidth' => 1000,
            'canvasHeight' => 800,
            'savedAt' => ''
        ];
    }
    $content = file_get_contents($layoutFile);
    return json_decode($content, true) ?: [
        'tables' => [],
        'canvasWidth' => 1000,
        'canvasHeight' => 800,
        'savedAt' => ''
    ];
}

function saveLayout($data) {
    global $layoutFile;
    $data['savedAt'] = date('c');
    file_put_contents($layoutFile, json_encode($data, JSON_PRETTY_PRINT));
}

function getNextTableId($tables) {
    if (empty($tables)) {
        return 1;
    }
    
    $maxId = max(array_column($tables, 'id'));
    return $maxId + 1;
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($action === 'load') {
                $layout = loadLayout();
                echo json_encode(['success' => true, 'data' => $layout]);
            }
            break;
            
        case 'POST':
            if ($action === 'save') {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!isset($input['tables']) || !is_array($input['tables'])) {
                    throw new Exception("Invalid layout data");
                }
                
                $layout = [
                    'tables' => $input['tables'],
                    'canvasWidth' => $input['canvasWidth'] ?? 1000,
                    'canvasHeight' => $input['canvasHeight'] ?? 800,
                    'savedAt' => date('c')
                ];
                
                saveLayout($layout);
                echo json_encode(['success' => true, 'message' => 'Layout saved successfully']);
            }
            break;
            
        case 'DELETE':
            if ($action === 'clear') {
                $layout = [
                    'tables' => [],
                    'canvasWidth' => 1000,
                    'canvasHeight' => 800,
                    'savedAt' => ''
                ];
                
                saveLayout($layout);
                echo json_encode(['success' => true, 'message' => 'Layout cleared']);
            }
            break;
            
        default:
            throw new Exception('Method not supported');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>