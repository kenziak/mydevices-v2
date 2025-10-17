<?php
// Ajax: obsługa odpowiedzi inwentaryzacyjnych (Posiadam / Nie posiadam)
include('../../../inc/includes.php');
require_once __DIR__ . '/../src/Inventory.php';

header('Content-Type: application/json; charset=UTF-8');

Session::checkLoginUser();
$userId = (int)Session::getLoginUserID();

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$entryId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$comment = trim((string)($_POST['comment'] ?? $_GET['comment'] ?? ''));

$inv = new \GlpiPlugin\Mydevices\Inventory();

try {
    if ($action === 'possessed') {
        $ok = $inv->confirmPossessed($entryId, $userId, $comment);
        echo json_encode(['success' => (bool)$ok]);
        exit;
    } elseif ($action === 'not_possessed') {
        $res = $inv->reportNotPossessed($entryId, $userId, $comment);
        echo json_encode(['success' => (bool)$res, 'ticket' => $res]);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nieznana akcja']);
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}