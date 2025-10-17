-- Dodanie kolumn do obsługi przypomnień i eskalacji w tabeli glpi_plugin_mydevices_inventory

ALTER TABLE `glpi_plugin_mydevices_inventory`
  ADD COLUMN `campaign_id` INT(11) DEFAULT NULL AFTER `id`,
  ADD COLUMN `reminder_count` TINYINT(4) NOT NULL DEFAULT 0 AFTER `comment`,
  ADD COLUMN `last_sent_date` TIMESTAMP NULL DEFAULT NULL AFTER `reminder_count`,
  ADD COLUMN `next_send_date` TIMESTAMP NULL DEFAULT NULL AFTER `last_sent_date`,
  ADD COLUMN `escalate_flag` TINYINT(1) NOT NULL DEFAULT 0 AFTER `next_send_date`,
  ADD COLUMN `manual_review_by` INT(11) DEFAULT NULL AFTER `escalate_flag`,
  ADD COLUMN `review_note` TEXT DEFAULT NULL AFTER `manual_review_by`;