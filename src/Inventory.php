<?php
namespace GlpiPlugin\Mydevices;

use DB;
use Session;
use Plugin;
use Ticket;

/**
 * Klasa zarządzająca wpisami inwentaryzacji.
 * - createInventoryEntry() - tworzy wpis pending
 * - confirmPossessed() - potwierdzenie posiadania
 * - reportNotPossessed() - zgłoszenie braku, tworzy ticket
 */
class Inventory {
    protected $table = 'glpi_plugin_mydevices_inventory';

    /**
     * Tworzy wpis inwentaryzacyjny dla jednego urządzenia
     */
    public function createInventoryEntry(int $userId, string $itemtype, int $itemsId): int {
        global $DB;
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO `{$this->table}` (users_id, itemtype, items_id, request_date, user_response, status)
                VALUES (:users_id, :itemtype, :items_id, :request_date, 'pending', 'pending')";
        $stmt = $DB->prepare($sql);
        $stmt->bindValue(':users_id', $userId);
        $stmt->bindValue(':itemtype', $itemtype);
        $stmt->bindValue(':items_id', $itemsId);
        $stmt->bindValue(':request_date', $now);
        $stmt->execute();
        return (int)$DB->lastInsertId();
    }

    /**
     * Użytkownik potwierdza posiadanie urządzenia
     */
    public function confirmPossessed(int $entryId, int $userId, ?string $comment = null): bool {
        global $DB;
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE `{$this->table}` SET user_response='possessed', confirmed_date=:confirmed_date, status='confirmed', comment=:comment WHERE id=:id AND users_id=:users_id";
        $stmt = $DB->prepare($sql);
        $stmt->bindValue(':confirmed_date', $now);
        $stmt->bindValue(':id', $entryId);
        $stmt->bindValue(':users_id', $userId);
        $stmt->bindValue(':comment', $comment);
        return $stmt->execute();
    }

    /**
     * Użytkownik zgłasza brak urządzenia => tworzymy ticket w GLPI
     */
    public function reportNotPossessed(int $entryId, int $userId, ?string $comment = null) {
        global $DB;
        $row = $this->getEntry($entryId);
        if (!$row || (int)$row['users_id'] !== $userId) {
            return false;
        }

        // Utwórz ticket GLPI
        $ticket = new \Ticket();
        $ticket->fields['name'] = 'Brak urządzenia zgłoszony przez użytkownika';
        $ticket->fields['content'] = "Użytkownik (ID: {$userId}) zgłosił brak urządzenia.\nTyp: {$row['itemtype']}\nItem ID: {$row['items_id']}\nKomentarz: " . ($comment ?? '');
        $ticket->fields['entities_id'] = Session::getLoginUserID() ? Plugin::getCurrentUserID() : 0;
        $ticket->add();

        // Aktualizacja wpisu inventory
        $sql = "UPDATE `{$this->table}` SET user_response='not_possessed', confirmed_date=:confirmed_date, status='ticket_created', ticket_id=:ticket_id, comment=:comment WHERE id=:id";
        $stmt = $DB->prepare($sql);
        $stmt->bindValue(':confirmed_date', date('Y-m-d H:i:s'));
        $stmt->bindValue(':ticket_id', $ticket->getID());
        $stmt->bindValue(':comment', $comment);
        $stmt->bindValue(':id', $entryId);
        $stmt->execute();

        // Wyślij emaily informacyjne (adresy z konfiguracji)
        $cfg = Config::getConfig();
        $toList = $cfg['inventory_email_recipients'] ?? 'gelphelpdesk@uzp.gov.pl';
        $subject = 'MyDevices: Zgłoszenie braku urządzenia (ID: '.$ticket->getID().')';
        $message = "Utworzono ticket ID: ".$ticket->getID()."\nUżytkownik ID: {$userId}\nTyp: {$row['itemtype']}\nItem ID: {$row['items_id']}\nKomentarz: ".($comment ?? '');
        // Rozdzielanie adresów przecinkami lub średnikami
        $tos = preg_split('/[;,]+/', $toList);
        foreach ($tos as $to) {
            $to = trim($to);
            if ($to) {
                @mail($to, $subject, $message);
            }
        }

        return ['ticket_id' => $ticket->getID()];
    }

    /**
     * Pobierz wpis po id
     */
    public function getEntry(int $id) {
        global $DB;
        $sql = "SELECT * FROM `{$this->table}` WHERE id=:id";
        $stmt = $DB->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Pobierz wszystkie wpisy inwentaryzacyjne dla użytkownika
     */
    public function getInventoryForUser(int $userId): array {
        global $DB;
        $sql = "SELECT * FROM `{$this->table}` WHERE users_id=:users_id ORDER BY request_date DESC";
        $stmt = $DB->prepare($sql);
        $stmt->bindValue(':users_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}