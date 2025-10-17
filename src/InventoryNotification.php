<?php
namespace GlpiPlugin\Mydevices;

use DB;
use Toolbox;

/**
 * Cron task: plugin_mydevices_sendinventory
 * - Działa codziennie: sprawdza użytkowników i tworzy wpisy inwentaryzacyjne jeśli minęło >= inventory_frequency dni
 */
class InventoryNotification {

    public static function runCron() {
        global $DB;

        $cfg = Config::getConfig();
        if (empty($cfg['inventory_enabled'])) {
            return;
        }
        $freqDays = (int)($cfg['inventory_frequency'] ?? 180);

        // Pobierz wszystkich aktywnych użytkowników
        $sqlUsers = "SELECT id FROM glpi_users WHERE is_deleted = 0 AND realname != ''";
        foreach ($DB->query($sqlUsers)->fetchAll(\PDO::FETCH_ASSOC) as $u) {
            $userId = (int)$u['id'];

            // Sprawdź kiedy była ostatnia potwierdzona inwentaryzacja (najpóźniejsze confirmed_date)
            $sqlLast = "SELECT MAX(confirmed_date) AS last_conf FROM glpi_plugin_mydevices_inventory WHERE users_id = :uid";
            $stmt = $DB->prepare($sqlLast);
            $stmt->bindValue(':uid', $userId);
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $lastConf = $row['last_conf'] ?? null;

            $need = false;
            if (empty($lastConf)) {
                $need = true;
            } else {
                $days = (time() - strtotime($lastConf)) / (60*60*24);
                if ($days >= $freqDays) {
                    $need = true;
                }
            }

            if (!$need) {
                continue;
            }

            // Pobierz urządzenia przypisane do użytkownika
            $myDevices = new \MyDevices();
            $assets = $myDevices->getAllAssetsForUser($userId);
            if (empty($assets)) {
                continue;
            }

            $inventory = new Inventory();
            foreach ($assets as $asset) {
                // Utwórz wpis pending (jeżeli nie istnieje już aktywny pending dla tego urządzenia)
                $sqlCheck = "SELECT id FROM glpi_plugin_mydevices_inventory WHERE users_id=:uid AND itemtype=:it AND items_id=:iid AND status='pending'";
                $stc = $DB->prepare($sqlCheck);
                $stc->bindValue(':uid', $userId);
                $stc->bindValue(':it', $asset['itemtype']);
                $stc->bindValue(':iid', $asset['id']);
                $stc->execute();
                if ($stc->fetch()) {
                    continue;
                }

                $inventory->createInventoryEntry($userId, $asset['itemtype'], (int)$asset['id']);
            }

            // Wyślij email do użytkownika z linkiem do potwierdzenia (jeśli zdefiniowano)
            if (!empty($cfg['inventory_notification_type']) && in_array($cfg['inventory_notification_type'], ['email','both'])) {
                $userObj = new \User();
                $userObj->getFromDB($userId);
                $email = $userObj->getField('email');
                if ($email) {
                    $subject = "Wymagana inwentaryzacja urządzeń";
                    $body = "Prosimy o potwierdzenie posiadanych urządzeń. Wejdź na panel: " . \Plugin::getWebDir('mydevices') . "/front/mydevices.php";
                    @mail($email, $subject, $body);
                }
            }
        }
    }
}