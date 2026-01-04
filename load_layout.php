<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Configura CORS se necessario
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestisci richieste preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Accetta solo richieste GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito']);
    exit;
}

try {
    // Percorso del file di configurazione
    $configFile = 'tables_conf.json';
    
    // Verifica se il file esiste
    if (!file_exists($configFile)) {
        // Se il file non esiste, restituisci una configurazione vuota
        echo json_encode([
            'success' => true,
            'data' => [
                'name' => 'Layout_Ristorante',
                'date' => date('c'),
                'tables' => []
            ],
            'message' => 'Nessuna configurazione trovata, layout vuoto caricato'
        ]);
        exit;
    }
    
    // Verifica che il file sia leggibile
    if (!is_readable($configFile)) {
        throw new Exception('File di configurazione non leggibile');
    }
    
    // Leggi il contenuto del file
    $fileContent = file_get_contents($configFile);
    
    if ($fileContent === false) {
        throw new Exception('Impossibile leggere il file di configurazione');
    }
    
    // Verifica che il file non sia vuoto
    if (empty(trim($fileContent))) {
        throw new Exception('File di configurazione vuoto');
    }
    
    // Decodifica il JSON
    $data = json_decode($fileContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('File di configurazione non valido: ' . json_last_error_msg());
    }
    
    // Valida la struttura dei dati
    if (!is_array($data)) {
        throw new Exception('Formato dati non valido: deve essere un oggetto JSON');
    }
    
    // Assicurati che esista l'array tavoli
    if (!isset($data['tables'])) {
        $data['tables'] = [];
    }
    
    if (!is_array($data['tables'])) {
        throw new Exception('Formato tavoli non valido: deve essere un array');
    }
    
    // Valida ogni tavolo
    foreach ($data['tables'] as $index => $table) {
        if (!is_array($table)) {
            throw new Exception("Tavolo $index: deve essere un oggetto");
        }
        
        // Verifica i campi obbligatori
        $requiredFields = ['x', 'y', 'name', 'seats'];
        foreach ($requiredFields as $field) {
            if (!isset($table[$field])) {
                throw new Exception("Tavolo $index: campo '$field' mancante");
            }
        }
        
        // Valida i tipi di dato
        if (!is_numeric($table['x']) || !is_numeric($table['y'])) {
            throw new Exception("Tavolo $index: coordinate non valide");
        }
        
        if (!is_numeric($table['seats']) || intval($table['seats']) <= 0) {
            throw new Exception("Tavolo $index: numero posti non valido");
        }
        
        if (empty(trim($table['name']))) {
            throw new Exception("Tavolo $index: nome non valido");
        }
        
        // Converti i numeri per sicurezza
        $data['tables'][$index]['x'] = floatval($table['x']);
        $data['tables'][$index]['y'] = floatval($table['y']);
        $data['tables'][$index]['seats'] = intval($table['seats']);
    }
    
    // *** AGGIUNTA: Assicuriamoci che tutti i tavoli abbiano le proprietà necessarie ***
    if (isset($data['tables'])) {
        foreach ($data['tables'] as &$table) {
            if (!isset($table['currentStatus'])) {
                $table['currentStatus'] = 'disponibile';
            }
            if (!isset($table['history'])) {
                $table['history'] = [];
            }
            if (!isset($table['orientation'])) {
                $table['orientation'] = 'horizontal';
            }
        }
        unset($table); // Rimuovi il riferimento per sicurezza
    }
    
    // Aggiungi campi mancanti se necessario
    if (!isset($data['name'])) {
        $data['name'] = 'Layout_Ristorante';
    }
    
    if (!isset($data['date'])) {
        $data['date'] = date('c');
    }
    
    // Informazioni aggiuntive sul file
    $fileStats = stat($configFile);
    $lastModified = date('c', $fileStats['mtime']);
    $fileSize = $fileStats['size'];
    
    // Log dell'operazione
    error_log("Layout caricato con successo: " . count($data['tables']) . " tavoli");
    
    // Risposta di successo
    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => 'Layout caricato con successo',
        'tables_count' => count($data['tables']),
        'last_modified' => $lastModified,
        'file_size' => $fileSize
    ]);
    
} catch (Exception $e) {
    // Log dell'errore
    error_log("Errore nel caricamento layout: " . $e->getMessage());
    
    // Verifica se esiste un backup recente
    $backupFiles = glob('tables_conf_backup_*.json');
    $backupMessage = '';
    
    if (!empty($backupFiles)) {
        // Trova il backup più recente
        usort($backupFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $latestBackup = $backupFiles[0];
        $backupDate = date('Y-m-d H:i:s', filemtime($latestBackup));
        $backupMessage = " Backup disponibile: $latestBackup (del $backupDate)";
    }
    
    // Risposta di errore
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage() . $backupMessage,
        'has_backup' => !empty($backupFiles)
    ]);
}
?>