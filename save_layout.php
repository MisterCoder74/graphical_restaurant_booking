<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Configura CORS se necessario
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestisci richieste preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Accetta solo richieste POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito']);
    exit;
}

try {
    // Leggi i dati JSON dalla richiesta
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Dati JSON non validi: ' . json_last_error_msg());
    }
    
    // Valida i dati ricevuti
    if (!isset($data['tables']) || !is_array($data['tables'])) {
        throw new Exception('Formato dati non valido: manca array tavoli');
    }
    
    // Valida ogni tavolo
    foreach ($data['tables'] as $index => $table) {
        if (!isset($table['x']) || !isset($table['y']) || !isset($table['name']) || !isset($table['seats'])) {
            throw new Exception("Tavolo $index: dati mancanti");
        }
        
        if (!is_numeric($table['x']) || !is_numeric($table['y']) || !is_numeric($table['seats'])) {
            throw new Exception("Tavolo $index: dati numerici non validi");
        }
        
        if (empty(trim($table['name']))) {
            throw new Exception("Tavolo $index: nome non valido");
        }
    }
    
    // Aggiungi timestamp se non presente
    if (!isset($data['date'])) {
        $data['date'] = date('c'); // ISO 8601 format
    }
    
    // Aggiungi nome se non presente
    if (!isset($data['name'])) {
        $data['name'] = 'Layout_Ristorante';
    }
    
    // Percorso del file di configurazione
    $configFile = 'tables_conf.json';
    
    // Crea backup del file esistente
    if (file_exists($configFile)) {
        $backupFile = 'tables_conf_backup_' . date('Y-m-d_H-i-s') . '.json';
        if (!copy($configFile, $backupFile)) {
            error_log("Attenzione: impossibile creare backup di $configFile");
        }
        
        // Mantieni solo gli ultimi 5 backup
        $backupFiles = glob('tables_conf_backup_*.json');
        if (count($backupFiles) > 5) {
            // Ordina per data di modifica (più vecchi prima)
            usort($backupFiles, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Elimina i backup più vecchi
            for ($i = 0; $i < count($backupFiles) - 5; $i++) {
                unlink($backupFiles[$i]);
            }
        }
    }
    
    // Converti i dati in JSON con formattazione leggibile
    $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if ($jsonData === false) {
        throw new Exception('Errore nella codifica JSON: ' . json_last_error_msg());
    }
    
    // Scrivi il file atomicamente usando un file temporaneo
    $tempFile = $configFile . '.tmp';
    
    if (file_put_contents($tempFile, $jsonData, LOCK_EX) === false) {
        throw new Exception('Impossibile scrivere file temporaneo');
    }
    
    // Rinomina il file temporaneo al nome finale
    if (!rename($tempFile, $configFile)) {
        unlink($tempFile); // Pulisci in caso di errore
        throw new Exception('Impossibile finalizzare il salvataggio');
    }
    
    // Verifica che il file sia stato scritto correttamente
    if (!file_exists($configFile)) {
        throw new Exception('File di configurazione non trovato dopo il salvataggio');
    }
    
    // Verifica che il contenuto sia valido
    $verification = json_decode(file_get_contents($configFile), true);
    if ($verification === null) {
        throw new Exception('File salvato ma contenuto non valido');
    }
    
    // Log dell'operazione
    error_log("Layout salvato con successo: " . count($data['tables']) . " tavoli");
    
    // Risposta di successo
    echo json_encode([
        'success' => true,
        'message' => 'Layout salvato con successo',
        'tables_count' => count($data['tables']),
        'timestamp' => $data['date']
    ]);
    
} catch (Exception $e) {
    // Log dell'errore
    error_log("Errore nel salvataggio layout: " . $e->getMessage());
    
    // Pulisci eventuali file temporanei
    if (isset($tempFile) && file_exists($tempFile)) {
        unlink($tempFile);
    }
    
    // Risposta di errore
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>