<?php
namespace GlpiPlugin\Mydevices;

use CommonDBTM;

/**
 * Plugin configuration wrapper
 */
class Config extends CommonDBTM {
    protected $table = "glpi_plugin_mydevices_configs";

    /**
     * Pobierz konfigurację
     * @return array
     */
    public static function getConfig(): array {
        $config = [];
        $c = new self();
        // Zakładamy, że konfiguracja ma zawsze ID=1
        if ($c->getFromDB(1)) {
            $config = $c->fields;
        } else {
            // Domyślne wartości, jeśli brak wpisu w bazie
            $config = [
                'id' => 1,
                'inventory_enabled' => 1,
                'inventory_frequency' => 180,
                'inventory_notification_type' => 'email',
                'inventory_email_recipients' => 'glpihelpdesk@uzp.gov.pl',
                'inventory_email_template' => '',
                'cache_enabled' => 0,
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
     * Zapis konfiguracji
     * @param array $data
     * @return bool
     */
    public static function saveConfig(array $data): bool {
        $config_item = new self();
        $found = $config_item->getFromDB(1);

        $payload = $data;
        $payload['id'] = 1;

        if ($found) {
            return $config_item->update($payload);
        } else {
            return $config_item->add($payload);
        }
    }
}