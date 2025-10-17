<?php
/**
 * Endpoint AJAX do aktualizacji pól zasobów.
 *
 * Obsługuje aktualizację statusu i lokalizacji urządzeń.
 * Zgodnie z wytycznymi, pozostawiony bez zmian w stosunku do pierwotnej wersji.
 */

include('../../../inc/includes.php');

// Pozostawione bez zmian, zgodnie z wymaganiami
ini_set('display_errors', 0);
error_reporting(E_ALL);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'PHP Fatal Error: ' . $error['message']]);
        exit;
    }
});

header('Content-Type: application/json; charset=UTF-8');
Session::checkLoginUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$itemtype = $_POST['itemtype'] ?? '';
$items_id = (int)($_POST['items_id'] ?? 0);
$field    = $_POST['field'] ?? '';
$value    = $_POST['value'] ?? '';
$uid      = (int)Session::getLoginUserID();

$allowed_fields = ['locations_id', 'states_id'];
if (empty($itemtype) || $items_id <= 0 || !in_array($field, $allowed_fields)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
    exit;
}

if (!class_exists($itemtype)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid item type: ' . $itemtype]);
    exit;
}

$item = new $itemtype();
if (!$item->getFromDB($items_id)) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Asset not found']);
    exit;
}

if (!Session::haveAccessToEntity($item->fields['entities_id'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No access to entity']);
    exit;
}

$is_owner = (($item->fields['users_id'] ?? 0) == $uid);
$is_admin = Session::haveRight('config', UPDATE);

if (!($is_admin || $is_owner)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Permission denied']);
    exit;
}

$value = (int)$value;
if ($value < 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid value']);
    exit;
}

$update_data = ['id' => $items_id, $field => $value];
if ($item->update($update_data)) {
    echo json_encode(['status' => 'ok', 'message' => 'Zaktualizowano pomyślnie']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Update failed']);
}