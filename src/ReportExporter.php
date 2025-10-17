<?php
namespace GlpiPlugin\Mydevices;

use DateTime;

/**
 * Klasa ReportExporter
 *
 * Odpowiada za generowanie raportów z kampanii inwentaryzacyjnych w formacie CSV.
 * Zarządza również retencją plików, czyli automatycznym usuwaniem starszych eksportów.
 */
class ReportExporter {
    protected $exportsDir;

    public function __construct() {
        $pluginDir = realpath(__DIR__ . '/../../') ?: __DIR__ . '/../../';
        $this->exportsDir = rtrim($pluginDir, '/') . '/exports';
        if (!is_dir($this->exportsDir)) {
            @mkdir($this->exportsDir, 0755, true);
        }
    }

    /**
     * Generuje raport CSV dla danej kampanii inwentaryzacyjnej.
     *
     * @param int $campaignId ID kampanii. Jeśli 0, raport obejmuje ostatnie 30 dni.
     * @return string Ścieżka do wygenerowanego pliku CSV.
     * @throws \Exception Jeśli nie można otworzyć pliku do zapisu.
     */
    public function exportInventoryCampaign(int $campaignId = 0): string {
        global $DB;
        $fmt = new DateTime();
        $stamp = $fmt->format('Y-m-d_H-i-s');
        $filename = "inventory_report_{$stamp}" . ($campaignId ? "_c{$campaignId}" : '') . ".csv";
        $path = $this->exportsDir . '/' . $filename;

        $fh = fopen($path, 'w');
        if (!$fh) {
            throw new \Exception("Nie można otworzyć pliku do zapisu: $path");
        }

        // Nagłówki CSV
        fputcsv($fh, ['inventory_id','campaign_id','users_id','user_email','itemtype','items_id','device_name','request_date','user_response','confirmed_date','status','ticket_id','reminder_count','last_sent_date','escalate_flag','comment']);

        // Pobierz dane do raportu
        if ($campaignId) {
            $sql = "SELECT i.*, u.email AS user_email, '' AS device_name
                    FROM glpi_plugin_mydevices_inventory i
                    LEFT JOIN glpi_users u ON u.id = i.users_id
                    WHERE i.campaign_id = :cid";
            $stmt = $DB->prepare($sql);
            $stmt->bindValue(':cid', $campaignId);
        } else {
            $sql = "SELECT i.*, u.email AS user_email, '' AS device_name
                    FROM glpi_plugin_mydevices_inventory i
                    LEFT JOIN glpi_users u ON u.id = i.users_id
                    WHERE i.request_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $stmt = $DB->prepare($sql);
        }
        $stmt->execute();

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            fputcsv($fh, [
                $row['id'],
                $row['campaign_id'] ?? '',
                $row['users_id'],
                $row['user_email'],
                $row['itemtype'],
                $row['items_id'],
                $row['device_name'],
                $row['request_date'],
                $row['user_response'],
                $row['confirmed_date'],
                $row['status'],
                $row['ticket_id'],
                $row['reminder_count'],
                $row['last_sent_date'],
                $row['escalate_flag'],
                $row['comment']
            ]);
        }

        fclose($fh);
        @chmod($path, 0644);

        return $path;
    }

    /**
     * Usuwa stare pliki eksportów.
     *
     * @param int $days Liczba dni, po których pliki są uznawane za stare.
     * @return int Liczba usuniętych plików.
     */
    public function cleanOldExports(int $days = 30): int {
        $removed = 0;
        $files = glob($this->exportsDir . '/inventory_report_*.csv');
        foreach ($files as $f) {
            if (filemtime($f) < (time() - $days * 86400)) {
                @unlink($f);
                $removed++;
            }
        }
        return $removed;
    }
}