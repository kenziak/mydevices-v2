<?php

/**
 * Plugin MyDevices - zarządzanie urządzeniami użytkowników
 * 
 * @author Łukasz Kurenda
 * @license GPLv3
 */

// Definicja wersji wtyczki
define('PLUGIN_MYDEVICES_VERSION', '1.0.0');

/**
 * Zwrócenie informacji o wersji wtyczki
 */
function plugin_version_mydevices() {
    return [
        'name'         => 'My Devices',
        'version'      => PLUGIN_MYDEVICES_VERSION,
        'author'       => 'Łukasz Kurenda',
        'license'      => 'GPLv3',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => '10.0.0',
                'max' => '12.0.0'
            ]
        ]
    ];
}

/**
 * Sprawdzenie wymagań przed instalacją
 */
function plugin_mydevices_check_prerequisites() {
    if (version_compare(GLPI_VERSION, '10.0.0', 'lt')) {
        echo "This plugin requires GLPI >= 10.0.0";
        return false;
    }
    return true;
}

/**
 * Sprawdzenie konfiguracji
 */
function plugin_mydevices_check_config() {
    return true;
}

/**
 * Inicjalizacja wtyczki
 */
function plugin_init_mydevices() {
    global $PLUGIN_HOOKS;
    
    // CSRF compliance
    $PLUGIN_HOOKS['csrf_compliant']['mydevices'] = true;
    
    // Dodaj hook do przedefiniowania menu
    if (Session::getLoginUserID()) {
        $PLUGIN_HOOKS['redefine_menus']['mydevices'] = 'plugin_mydevices_redefine_menus';
    }
}

/**
 * Instalacja: tworzenie tabel pluginu
 */
function plugin_mydevices_install() {
    global $DB;
    
    try {
        $migration = new Migration(10010);
        
        // Tabela właściwości zasobów
        $props = 'glpi_plugin_mydevices_asset_properties';
        if (!$DB->tableExists($props)) {
            $query = "CREATE TABLE IF NOT EXISTS `$props` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `itemtype` VARCHAR(100) NOT NULL,
                `items_id` INT UNSIGNED NOT NULL,
                `usage_type` VARCHAR(20) DEFAULT 'company',
                `work_location` VARCHAR(20) DEFAULT 'office',
                `locations_id` INT UNSIGNED DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_item` (`itemtype`, `items_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $DB->doQueryOrDie($query, "Error creating table $props");
        }
        
        // Tabela ownership - przeznaczenie zasobu
        $ownership = 'glpi_plugin_mydevices_ownership';
        if (!$DB->tableExists($ownership)) {
            $query = "CREATE TABLE IF NOT EXISTS `$ownership` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `itemtype` VARCHAR(128) NOT NULL,
                `items_id` INT UNSIGNED NOT NULL,
                `user_id` INT UNSIGNED NULL,
                `ownership` ENUM('private','business') NOT NULL DEFAULT 'business',
                `note` TEXT NULL,
                `date_creation` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `date_mod` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `item_unique` (`itemtype`,`items_id`,`user_id`),
                KEY `by_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $DB->doQueryOrDie($query, "Error creating table $ownership");
        }
        
        // Utwórz katalog na logo
        $logoDir = '/var/www/html/glpi/plugins/mydevices/logo/';
        if (!is_dir($logoDir)) {
            mkdir($logoDir, 0755, true);
        }
        
        $migration->executeMigration();
        return true;
        
    } catch (Exception $e) {
        error_log("MyDevices install error: " . $e->getMessage());
        return false;
    }
}

/**
 * Odinstalowanie: usunięcie tabel pluginu
 */
function plugin_mydevices_uninstall() {
    global $DB;
    
    try {
        // Lista tabel do usunięcia
        $tables = [
            'glpi_plugin_mydevices_ownership',
            'glpi_plugin_mydevices_asset_properties'
        ];
        
        foreach ($tables as $table) {
            if ($DB->tableExists($table)) {
                $query = "DROP TABLE IF EXISTS `$table`";
                $DB->doQueryOrDie($query, "Error dropping table $table");
            }
        }
        
        // Usuń preferencje wyświetlania wtyczki
        $DB->delete('glpi_displaypreferences', [
            'itemtype' => ['LIKE', 'PluginMydevices%']
        ]);
        
        // Usuń logi wtyczki
        $DB->delete('glpi_logs', [
            'itemtype' => ['LIKE', 'PluginMydevices%']
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("MyDevices uninstall error: " . $e->getMessage());
        return true; // Zwracamy true, aby umożliwić odinstalowanie nawet przy błędach
    }
}
