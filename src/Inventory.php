<?php
namespace GlpiPlugin\Mydevices;

use DB;
use Session;
use Plugin;
use Ticket;

/**
 * Klasa Inventory
 *
 * Zarządza logiką inwentaryzacji, w tym tworzeniem wpisów,
 * obsługą odpowiedzi użytkowników oraz integracją z systemem ticketów GLPI.
 */
class Inventory {
    protected $table = 'glpi_plugin_mydevices_inventory';

    /**
     * Tworzy nowy wpis inwentaryzacyjny dla danego urządzenia.
     *
     * @param int $userId ID użytkownika.
     * @param string $itemtype Typ urządzenia.
     * @param int $itemsId ID urządzenia.
     * @return int ID nowo utworzonego wpisu.
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
     * Potwierdza posiadanie urządzenia przez użytkownika.
     *
     * @param int $entryId ID wpisu inwentaryzacyjnego.
     * @param int $userId ID użytkownika.
     * @param string|null $comment Opcjonalny komentarz.
     * @return bool Zwraca true, jeśli operacja się powiodła.
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
     * Zgłasza brak urządzenia, tworzy ticket w GLPI i wysyła e-mail.
     *
     * @param int $entryId ID wpisu inwentaryzacyjnego.
     * @param int $userId ID użytkownika.
     * @param string|null $comment Opcjonalny komentarz.
     * @return array|false Zwraca tablicę z ID ticketu lub false w przypadku błędu.
     */
    public function reportNotPossessed(int $entryId, int $userId, ?string $comment = null) {
        global $DB;
        $row = $this->getEntry($entryId);
        if (!$row || (int)$row['users_id'] !== $userId) {
            return false;
        }

        // Utwórz ticket w GLPI
        $ticket = new \Ticket();
        $ticket->fields['name'] = 'Brak urządzenia zgłoszony przez użytkownika';
        $ticket->fields['content'] = "Użytkownik (ID: {$userId}) zgłosił brak urządzenia.\nTyp: {$row['itemtype']}\nItem ID: {$row['items_id']}\nKomentarz: " . ($comment ?? '');
        $ticket->fields['entities_id'] = Session::getLoginUserID() ? Plugin::getCurrentUserID() : 0;
        $ticket_id = $ticket->add($ticket->fields);

        // Zaktualizuj wpis w tabeli inwentaryzacyjnej
        $sql = "UPDATE `{$this->table}` SET user_response='not_possessed', confirmed_date=:confirmed_date, status='ticket_created', ticket_id=:ticket_id, comment=:comment WHERE id=:id";
        $stmt = $DB->prepare($sql);
        $stmt->bindValue(':confirmed_date', date('Y-m-d H:i:s'));
        $stmt->bindValue(':ticket_id', $ticket_id);
        $stmt->bindValue(':comment', $comment);
        $stmt->bindValue(':id', $entryId);
        $stmt->execute();

        // Wyślij e-mail informacyjny do działu IT
        $cfg = Config::getConfig();
        $toList = $cfg['inventory_email_recipients'] ?? 'glpihelpdesk@uzp.gov.pl';
        $subject = 'MyDevices: Zgłoszenie braku urządzenia (Ticket ID: '.$ticket_id.')';
        $message = "Utworzono ticket ID: ".$ticket_id."\nUżytkownik ID: {$userId}\nTyp: {$row['itemtype']}\nItem ID: {$row['items_id']}\nKomentarz: ".($comment ?? '');

        $tos = preg_split('/[;,]+/', $toList);
        foreach ($tos as $to) {
            $to = trim($to);
            if ($to) {
                @mail($to, $subject, $message);
            }
        }

        return ['ticket_id' => $ticket_id];
    }

    /**
     * Pobiera pojedynczy wpis inwentaryzacyjny.
     *
     * @param int $id ID wpisu.
     * @return array|false Zwraca dane wpisu lub false, jeśli nie znaleziono.
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
     * Pobiera wszystkie wpisy inwentaryzacyjne dla danego użytkownika.
     *
     * @param int $userId ID użytkownika.
     * @return array Tablica z wpisami inwentaryzacyjnymi.
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