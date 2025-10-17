<?php
namespace GlpiPlugin\Mydevices;

use CommonDBTM;

/**
 * Klasa Config
 *
 * Zarządza konfiguracją wtyczki MyDevices.
 * Odpowiada za odczyt i zapis ustawień do tabeli glpi_plugin_mydevices_configs.
 * Implementuje mechanizm domyślnych wartości, jeśli konfiguracja nie istnieje.
 */
class Config extends CommonDBTM {
    protected $table = "glpi_plugin_mydevices_configs";

    /**
     * Pobiera konfigurację wtyczki.
     *
     * @return array Tablica z konfiguracją.
     */
    public static function getConfig(): array {
        $config = [];
        $c = new self();

        // Konfiguracja jest przechowywana w jednym wierszu o ID=1
        if ($c->getFromDB(1)) {
            $config = $c->fields;
        } else {
            // Zwraca domyślne wartości, jeśli w bazie nie ma jeszcze konfiguracji
            $config = [
                'id' => 1,
                'inventory_enabled' => 1,
                'inventory_frequency' => 180,
                'inventory_notification_type' => 'email',
                'inventory_email_recipients' => 'glpihelpdesk@uzp.gov.pl',
                'inventory_email_template' => '',
                'cache_enabled' => 0, // Domyślnie wyłączony, aby uniknąć problemów z kompatybilnością
                'cache_ttl' => 300,
                'pdf_logo_path' => '/var/www/html/glpi/plugins/mydevices/logo/',
                'pdf_header' => '',
                'pdf_footer' => '',
                'visible_columns' => json_encode(['name','model','serial']),
            ];
        }
        return $config;
    }

    /**
     * Zapisuje konfigurację wtyczki.
     *
     * @param array $data Dane do zapisu.
     * @return bool Zwraca true, jeśli zapis się powiódł, w przeciwnym razie false.
     */
    public static function saveConfig(array $data): bool {
        $config_item = new self();
        $found = $config_item->getFromDB(1);

        $payload = $data;
        $payload['id'] = 1;

        if ($found) {
            // Aktualizuje istniejącą konfigurację
            return $config_item->update($payload);
        } else {
            // Dodaje nową konfigurację, jeśli nie istnieje
            return $config_item->add($payload);
        }
    }
}