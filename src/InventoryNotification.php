<?php
namespace GlpiPlugin\Mydevices;

use DB;
use Toolbox;

/**
 * Klasa InventoryNotification
 *
 * Odpowiada za logikę zadań cyklicznych (cron).
 * Zarządza tworzeniem kampanii inwentaryzacyjnych i wysyłaniem przypomnień.
 */
class InventoryNotification {

    /**
     * Uruchamia zadanie cron.
     *
     * Sprawdza, czy dla danego użytkownika należy rozpocząć nową inwentaryzację,
     * a następnie tworzy odpowiednie wpisy w bazie danych i wysyła powiadomienia.
     */
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

            // Sprawdź, kiedy była ostatnia potwierdzona inwentaryzacja
            $sqlLast = "SELECT MAX(confirmed_date) AS last_conf FROM glpi_plugin_mydevices_inventory WHERE users_id = :uid";
            $stmt = $DB->prepare($sqlLast);
            $stmt->bindValue(':uid', $userId);
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $lastConf = $row['last_conf'] ?? null;

            $needs_inventory = false;
            if (empty($lastConf)) {
                $needs_inventory = true;
            } else {
                $days_since_last = (time() - strtotime($lastConf)) / (60*60*24);
                if ($days_since_last >= $freqDays) {
                    $needs_inventory = true;
                }
            }

            if (!$needs_inventory) {
                continue;
            }

            // Pobierz wszystkie urządzenia przypisane do użytkownika
            $myDevices = new \MyDevices();
            $assets = $myDevices->getAllAssetsForUser($userId);
            if (empty($assets)) {
                continue;
            }

            $inventory = new Inventory();
            foreach ($assets as $asset) {
                // Sprawdź, czy nie ma już aktywnego wpisu "pending" dla tego urządzenia
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

            // Wyślij e-mail do użytkownika z linkiem do potwierdzenia
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