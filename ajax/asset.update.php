<?php
/**
 * MyDevices Plugin - Asset Update Endpoint
 * Handles AJAX updates for asset fields (states_id, locations_id)
 */

define('GLPI_DONT_CHECK_CSRF', 1);

// Wyłącz wyświetlanie błędów PHP (zwracamy tylko JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Przechwytuj wszystkie błędy i zwróć JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'PHP Fatal Error: ' . $error['message'],
            'debug' => [
                'file' => $error['file'],
                'line' => $error['line']
            ]
        ]);
        exit;
    }
});

try {
    include('../../../inc/includes.php');
} catch (Throwable $e) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to load GLPI: ' . $e->getMessage()
    ]);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

// Funkcja debugLog
function debugLog($message, $data = null) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $logFile = $logDir . '/asset-update-debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message";
    if ($data !== null) {
        $logEntry .= " | Data: " . print_r($data, true);
    }
    $logEntry .= "\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

debugLog("=== NEW REQUEST START ===");
debugLog("POST data", $_POST);
debugLog("Request method", $_SERVER['REQUEST_METHOD']);

try {
    // Sprawdź czy użytkownik jest zalogowany
    Session::checkLoginUser();
    
    debugLog("Session user ID", Session::getLoginUserID());

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        debugLog("Method check: FAILED");
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
        exit;
    }

    // UWAGA: Wyłączamy walidację CSRF dla tego endpointu pluginu
    debugLog("CSRF validation: SKIPPED for plugin endpoint");

    $itemtype = $_POST['itemtype'] ?? '';
    $items_id = (int)($_POST['items_id'] ?? 0);
    $field    = $_POST['field'] ?? '';
    $value    = $_POST['value'] ?? '';
    $uid      = (int)Session::getLoginUserID();

    debugLog("Input parsed", compact('itemtype', 'items_id', 'field', 'value', 'uid'));

    // Walidacja parametrów
    $allowed_fields = ['locations_id', 'states_id'];
    if (empty($itemtype) || $items_id <= 0 || !in_array($field, $allowed_fields)) {
        debugLog("Validation: FAILED");
        http_response_code(400);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Invalid parameters',
            'debug' => compact('itemtype', 'items_id', 'field')
        ]);
        exit;
    }

    // Walidacja klasy
    if (!class_exists($itemtype)) {
        debugLog("Class check: FAILED - $itemtype does not exist");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid item type: ' . $itemtype]);
        exit;
    }

    // Załaduj zasób
    $item = new $itemtype();
    if (!$item->getFromDB($items_id)) {
        debugLog("Asset load: FAILED");
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Asset not found']);
        exit;
    }

    debugLog("Asset loaded", [
        'users_id' => $item->fields['users_id'] ?? 'N/A', 
        'name' => $item->fields['name'] ?? 'N/A',
        'entities_id' => $item->fields['entities_id'] ?? 'N/A'
    ]);

    // Sprawdź dostęp do encji
    if (!Session::haveAccessToEntity($item->fields['entities_id'])) {
        debugLog("Entity access: FAILED");
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'No access to entity']);
        exit;
    }

    // Sprawdź uprawnienia
    $is_owner = (($item->fields['users_id'] ?? 0) == $uid);
    $is_admin = Session::haveRight('config', UPDATE);

    debugLog("Permissions", compact('is_owner', 'is_admin', 'uid'));

    if (!($is_admin || $is_owner)) {
        debugLog("Permission: DENIED");
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Permission denied - you are not owner or admin']);
        exit;
    }

    // Walidacja wartości
    $value = (int)$value;
    if ($value < 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid value']);
        exit;
    }

    // Aktualizuj zasób
    $update_data = [
        'id' => $items_id,
        $field => $value
    ];
    debugLog("Attempting update", $update_data);

    $result = $item->update($update_data);
    debugLog("Update result", ['return' => $result]);

    if (!$result) {
        throw new \Exception('item->update() returned false');
    }

    // Weryfikacja
    $verify_item = new $itemtype();
    if (!$verify_item->getFromDB($items_id)) {
        throw new \Exception('Failed to reload item after update');
    }

    debugLog("Verification", [
        'expected' => $value, 
        'actual' => $verify_item->fields[$field]
    ]);

    if ($verify_item->fields[$field] == $value) {
        debugLog("=== UPDATE SUCCESS ===");
        echo json_encode([
            'status' => 'ok',
            'message' => 'Zaktualizowano pomyślnie',
            'debug' => [
                'field' => $field,
                'value' => $value,
                'itemtype' => $itemtype,
                'items_id' => $items_id
            ]
        ]);
    } else {
        throw new \Exception('Verification failed: value not updated in database');
    }

} catch (Throwable $e) {
    debugLog("=== UPDATE ERROR ===", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Update failed: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
