<?php
include('../../../inc/includes.php');
require_once __DIR__ . '/../src/ReportExporter.php';

Session::checkRight('config','w');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'export_campaign' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $exporter = new \GlpiPlugin\Mydevices\ReportExporter();
    try {
        $path = $exporter->exportInventoryCampaign($id);
        header('Content-Type: application/json');
        echo json_encode(['success'=>true,'path'=>str_replace(GLPI_ROOT,'',$path)]);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

if ($action === 'create_campaign') {
    // TODO: implementacja tworzenia kampanii i wysyłki initial maili
    // Implementacja: InventoryNotification::createCampaignNow() lub Inventory::createCampaign()
    header('Location: ' . Plugin::getWebDir('mydevices') . '/front/admin_dashboard.php');
    exit;
}

http_response_code(400);
echo json_encode(['success'=>false,'error'=>'Unknown action']);