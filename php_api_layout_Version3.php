<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Resolve data file path relative to this php file's directory (root-agnostic)
$layoutFile = __DIR__ . '/../data/tables_layout.json';

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
    $dir = dirname($layoutFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($layoutFile, json_encode($data, JSON_PRETTY_PRINT));
}

// Router
switch ($action) {
    case 'load':
        $layout = loadLayout();
        echo json_encode(['success' => true, 'data' => $layout]);
        break;

    case 'save':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }
        saveLayout($input);
        echo json_encode(['success' => true, 'data' => $input]);
        break;

    case 'reset':
        saveLayout(['tables' => [], 'canvasWidth' => 1000, 'canvasHeight' => 800, 'savedAt' => date('c')]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'unknown_action']);
        break;
}