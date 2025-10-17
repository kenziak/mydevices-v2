-- Instalacja MyDevices v2.0 - tabele konfiguracji, inventory i device_types

CREATE TABLE IF NOT EXISTS `glpi_plugin_mydevices_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_enabled` tinyint(1) DEFAULT 1,
  `inventory_frequency` int(11) DEFAULT 180,
  `inventory_notification_type` varchar(16) DEFAULT 'email',
  `inventory_email_recipients` text,
  `inventory_email_template` text,
  `cache_enabled` tinyint(1) DEFAULT 1,
  `cache_ttl` int(11) DEFAULT 300,
  `pdf_logo_path` varchar(255) DEFAULT '/var/www/html/glpi/plugins/mydevices/logo/',
  `pdf_header` text,
  `pdf_footer` text,
  `visible_columns` text,
  `date_mod` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `glpi_plugin_mydevices_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `itemtype` varchar(128) NOT NULL,
  `items_id` int(11) NOT NULL,
  `request_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `user_response` enum('pending','possessed','not_possessed') DEFAULT 'pending',
  `confirmed_date` timestamp NULL,
  `status` enum('pending','confirmed','ticket_created','resolved') DEFAULT 'pending',
  `ticket_id` int(11) DEFAULT NULL,
  `comment` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `glpi_plugin_mydevices_device_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemtype` varchar(128) NOT NULL,
  `name` varchar(128) NOT NULL,
  `enabled` tinyint(1) DEFAULT 1,
  `rank` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Domyślne wpisy (możesz zmodyfikować nazwy / tłumaczenia w panelu admin):
INSERT INTO `glpi_plugin_mydevices_device_types` (`itemtype`,`name`,`enabled`,`rank`) VALUES
('Computer','Komputer',1,1),
('Monitor','Monitor',1,2),
('Peripheral','Peryferia',1,3),
('Phone','Telefon',1,4),
('Simcard','Karta SIM',1,5);