ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS `igst` DECIMAL(10,2) NULL DEFAULT 0.00 AFTER `sgst`;

ALTER TABLE `company_settings` ADD COLUMN IF NOT EXISTS `state` VARCHAR(100) NULL DEFAULT NULL AFTER `address`;

UPDATE `company_settings`
SET `state` = 'Uttar Pradesh'
WHERE (`state` IS NULL OR `state` = '') AND LEFT(REPLACE(`gstin`,' ',''), 2) = '09';
