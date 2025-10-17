-- Proste wstawienie domyślnych typów urządzeń przy migracji z v1.x
INSERT INTO `glpi_plugin_mydevices_device_types` (`itemtype`,`name`,`enabled`,`rank`)
SELECT 'Computer','Komputer',1,1 WHERE NOT EXISTS (SELECT 1 FROM glpi_plugin_mydevices_device_types WHERE itemtype='Computer');
INSERT INTO `glpi_plugin_mydevices_device_types` (`itemtype`,`name`,`enabled`,`rank`)
SELECT 'Monitor','Monitor',1,2 WHERE NOT EXISTS (SELECT 1 FROM glpi_plugin_mydevices_device_types WHERE itemtype='Monitor');
INSERT INTO `glpi_plugin_mydevices_device_types` (`itemtype`,`name`,`enabled`,`rank`)
SELECT 'Peripheral','Peryferia',1,3 WHERE NOT EXISTS (SELECT 1 FROM glpi_plugin_mydevices_device_types WHERE itemtype='Peripheral');
INSERT INTO `glpi_plugin_mydevices_device_types` (`itemtype`,`name`,`enabled`,`rank`)
SELECT 'Phone','Telefon',1,4 WHERE NOT EXISTS (SELECT 1 FROM glpi_plugin_mydevices_device_types WHERE itemtype='Phone');
INSERT INTO `glpi_plugin_mydevices_device_types` (`itemtype`,`name`,`enabled`,`rank`)
SELECT 'Simcard','Karta SIM',1,5 WHERE NOT EXISTS (SELECT 1 FROM glpi_plugin_mydevices_device_types WHERE itemtype='Simcard');