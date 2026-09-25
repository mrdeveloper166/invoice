-- Recycle bin: soft delete for invoices + admin PIN for permanent delete
ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL DEFAULT NULL;

ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `admin_pin` VARCHAR(50) NULL DEFAULT '123456';

UPDATE `users` SET `admin_pin` = '123456' WHERE `admin_pin` IS NULL OR `admin_pin` = '';
