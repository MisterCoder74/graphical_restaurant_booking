<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Configura CORS se necessario
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestisci richieste preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            listBackups();
            break;
            
        case 'restore':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Metodo non consentito per restore');
            }
            restoreBackup();
            break;
            
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Metodo non consentito per delete');
            }
            deleteBackup();
            break;
            
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Metodo non consentito per create');
            }
            createManualBackup();
            break;
            
        default:
            throw new Exception('Azione non valida');
    }
    
} catch (Exception $e) {
    error_log("Errore backup manager: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function listBackups() {
    $backupFiles = glob('tables_conf_backup_*.json');
    $backups = [];
    
    foreach ($backupFiles as $file) {
        $stats = stat($file);
        $backups[] = [
            'filename' => basename($file),
            'date' => date('Y-m-d H:i:s', $stats['mtime']),
            'size' => $stats['size'],
            'formatted_size' => formatBytes($stats['size'])
        ];
    }
    
    // Ordina per data (più recenti prima)
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    echo json_encode([
        'success' => true,
        'backups' => $backups,
        'count' => count($backups)
    ]);
}

function restoreBackup() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['filename'])) {
        throw new Exception('Nome file backup non specificato');
    }
    
    $backupFile = $data['filename'];
    
    // Verifica che il nome del file sia sicuro
    if (!preg_match('/^tables_conf_backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.json$/', $backupFile)) {
        throw new Exception('Nome file backup non valido');
    }
    
    if (!file_exists($backupFile)) {
        throw new Exception('File backup non trovato');
    }
    
    $configFile = 'tables_conf.json';
    
    // Crea backup del file attuale prima del ripristino
    if (file_exists($configFile)) {
        $preRestoreBackup = 'tables_conf_pre_restore_' . date('Y-m-d_H-i-s') . '.json';
        copy($configFile, $preRestoreBackup);
    }
    
    // Ripristina il backup
    if (!copy($backupFile, $configFile)) {
        throw new Exception('Impossibile ripristinare il backup');
    }
    
    // Verifica che il file ripristinato sia valido
    $content = file_get_contents($configFile);
    $validation = json_decode($content, true);
    
    if ($validation === null) {
        throw new Exception('File backup ripristinato non valido');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Backup ripristinato con successo',
        'restored_file' => $backupFile,
        'tables_count' => count($validation['tables'] ?? [])
    ]);
}

function deleteBackup() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['filename'])) {
        throw new Exception('Nome file backup non specificato');
    }
    
    $backupFile = $data['filename'];
    
    // Verifica che il nome del file sia sicuro
    if (!preg_match('/^tables_conf_backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.json$/', $backupFile)) {
        throw new Exception('Nome file backup non valido');
    }
    
    if (!file_exists($backupFile)) {
        throw new Exception('File backup non trovato');
    }
    
    if (!unlink($backupFile)) {
        throw new Exception('Impossibile eliminare il file backup');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Backup eliminato con successo',
        'deleted_file' => $backupFile
    ]);
}

function createManualBackup() {
    $configFile = 'tables_conf.json';
    
    if (!file_exists($configFile)) {
        throw new Exception('Nessun file di configurazione da cui creare backup');
    }
    
    $backupFile = 'tables_conf_backup_' . date('Y-m-d_H-i-s') . '.json';
    
    if (!copy($configFile, $backupFile)) {
        throw new Exception('Impossibile creare il backup');
    }
    
    // Verifica il contenuto del backup
    $content = file_get_contents($backupFile);
    $validation = json_decode($content, true);
    
    if ($validation === null) {
        unlink($backupFile);
        throw new Exception('Backup creato ma non valido');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Backup creato con successo',
        'backup_file' => $backupFile,
        'tables_count' => count($validation['tables'] ?? [])
    ]);
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
?>