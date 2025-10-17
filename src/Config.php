<?php
namespace GlpiPlugin\Mydevices;

use Toolbox;
use CommonDBTM;

/**
 * Plugin configuration wrapper
 *
 * Zajmuje się czytaniem i zapisem konfiguracji pluginu (tylko do tabeli plugin)
 * Komentarze po polsku opisują każdą metodę.
 */
class Config extends CommonDBTM {
    protected $table = "glpi_plugin_mydevices_configs";

    /**
     * Pobierz konfigurację (singleton)
     * @return array
     */
    public static function getConfig(): array {
        // Najpierw spróbuj cache
        $cacheKey = 'plugin_mydevices_config_v2';
        $cache = Toolbox::getGlpiCache();
        $cached = $cache->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $config = [];
        $c = new self();
        $c->getFromDB(1); // zakładamy jeden wiersz konfiguracji
        if ($c->isNewItem()) {
            // Domyślne wartości
            $config = [
                'inventory_enabled' => 1,
                'inventory_frequency' => 180,
                'inventory_notification_type' => 'email',
                'inventory_email_recipients' => 'gelphelpdesk@uzp.gov.pl',
                'inventory_email_template' => '',
                'cache_enabled' => 1,
                'cache_ttl' => 300,
                'pdf_logo_path' => '/var/www/html/glpi/plugins/mydevices/logo/',
                'pdf_header' => '',
                'pdf_footer' => '',
                'visible_columns' => json_encode(['name','model','serial']),
            ];
        } else {
            $config = $c->fields;
        }

        if (!empty($config) && $config['cache_enabled'] ?? 1) {
            $cache->set($cacheKey, $config, (int)($config['cache_ttl'] ?? 300));
        }
        return $config;
    }

    /**
     * Zapis konfiguracji (używać tylko przez panel admina)
     * @param array $data
     * @return bool
     */
    public static function saveConfig(array $data): bool {
        $c = new self();
        $c->getFromDB(1);
        foreach ($data as $k => $v) {
            $c->fields[$k] = $v;
        }
        $res = $c->update($data);
        // Invalidate cache
        Toolbox::getGlpiCache()->remove('plugin_mydevices_config_v2');
        return $res;
    }
}