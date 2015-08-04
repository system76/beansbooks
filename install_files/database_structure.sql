SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO"; SET time_zone = "+00:00"; CREATE TABLE IF NOT EXISTS `accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_account_id` bigint(20) unsigned DEFAULT NULL,
  `account_type_id` tinyint(3) unsigned DEFAULT NULL,
  `reserved` boolean NOT NULL DEFAULT FALSE,
  `deposit` tinyint(1) NOT NULL DEFAULT '0',
  `payment` tinyint(1) NOT NULL DEFAULT '0',
  `receivable` tinyint(1) NOT NULL DEFAULT '0',
  `payable` tinyint(1) NOT NULL DEFAULT '0',
  `writeoff` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `reconcilable` tinyint(1) NOT NULL DEFAULT '0',
  `terms` tinyint(3) unsigned DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `account_reconciles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned NULL DEFAULT NULL,
  `date` date DEFAULT NULL,
  `balance_start` decimal(15,2) DEFAULT NULL,
  `balance_end` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `account_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint(20) unsigned DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT NULL,
  `account_reconcile_id` bigint(20) unsigned DEFAULT NULL,
  `transfer` boolean NOT NULL DEFAULT FALSE,
  `writeoff` boolean NOT NULL DEFAULT FALSE,
  `adjustment` boolean NOT NULL DEFAULT FALSE,
  `close_books` boolean NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `account_transaction_forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `form_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `writeoff_amount` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `account_types` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `table_sign` tinyint(1) NOT NULL DEFAULT '0',
  `deposit` tinyint(1) NOT NULL DEFAULT '0',
  `payment` tinyint(1) NOT NULL DEFAULT '0',
  `receivable` tinyint(1) NOT NULL DEFAULT '0',
  `payable` tinyint(1) NOT NULL DEFAULT '0',
  `reconcilable` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `entities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `default_shipping_address_id` bigint(20) unsigned DEFAULT NULL,
  `default_billing_address_id` bigint(20) unsigned DEFAULT NULL,
  `default_remit_address_id` bigint(20) unsigned DEFAULT NULL,
  `default_account_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('customer','vendor') DEFAULT NULL,
  `first_name` varchar(64) DEFAULT NULL,
  `last_name` varchar(64) DEFAULT NULL,
  `company_name` varchar(64) DEFAULT NULL,
  `email` varchar(256) DEFAULT NULL,
  `phone_number` varchar(32) DEFAULT NULL,
  `fax_number` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `entity_addresses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `address1` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `zip` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `forms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `create_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `cancel_transaction_id` bigint(20) unsigned DEFAULT NULL,
  `refund_form_id` bigint(20) unsigned DEFAULT NULL,
  `shipping_address_id` bigint(20) unsigned DEFAULT NULL,
  `billing_address_id` bigint(20) unsigned DEFAULT NULL,
  `remit_address_id` bigint(20) unsigned DEFAULT NULL,
  `type` enum('sale','expense','purchase') DEFAULT NULL,
  `tax_exempt` boolean NOT NULL DEFAULT FALSE,
  `tax_exempt_reason` varchar( 255 ) NULL DEFAULT NULL,
  `sent` enum('print','email','both') DEFAULT NULL,
  `date_created` date DEFAULT NULL,
  `date_billed` date DEFAULT NULL,
  `date_due` date DEFAULT NULL,
  `date_cancelled` date DEFAULT NULL,
  `code` varchar(16) DEFAULT NULL,
  `reference` varchar(16) DEFAULT NULL,
  `alt_reference` varchar(16) DEFAULT NULL,
  `aux_reference` varchar(16) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000 ; CREATE TABLE IF NOT EXISTS `form_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `adjustment` boolean NOT NULL DEFAULT FALSE,
  `tax_exempt` boolean NOT NULL DEFAULT FALSE,
  `description` varchar(128) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `quantity` decimal(13,3) DEFAULT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `form_taxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint(20) unsigned DEFAULT NULL,
  `tax_id` bigint(20) unsigned DEFAULT NULL,
  `form_line_amount` decimal(15,2) DEFAULT NULL,
  `form_line_taxable_amount` decimal(15,2) DEFAULT NULL,
  `tax_percent` decimal(6,6) DEFAULT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `timestamp` bigint(20) unsigned DEFAULT NULL,
  `object_id` bigint(20) unsigned DEFAULT NULL,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL,
  `code` varchar(16) DEFAULT NULL,
  `description` text,
  `user_limit` int(10) unsigned DEFAULT NULL,
  `auth_expiration_length` bigint(20) DEFAULT NULL,
  `customer_read` tinyint(1) NOT NULL DEFAULT '0',
  `customer_write` tinyint(1) NOT NULL DEFAULT '0',
  `customer_sale_read` tinyint(1) NOT NULL DEFAULT '0',
  `customer_sale_write` tinyint(1) NOT NULL DEFAULT '0',
  `customer_payment_read` tinyint(1) NOT NULL DEFAULT '0',
  `customer_payment_write` tinyint(1) NOT NULL DEFAULT '0',
  `vendor_read` tinyint(1) NOT NULL DEFAULT '0',
  `vendor_write` tinyint(1) NOT NULL DEFAULT '0',
  `vendor_expense_read` tinyint(1) NOT NULL DEFAULT '0',
  `vendor_expense_write` tinyint(1) NOT NULL DEFAULT '0',
  `vendor_purchase_read` tinyint(1) NOT NULL DEFAULT '0',
  `vendor_purchase_write` tinyint(1) NOT NULL DEFAULT '0',
  `vendor_payment_read` tinyint(1) NOT NULL DEFAULT '0',
  `vendor_payment_write` tinyint(1) NOT NULL DEFAULT '0',
  `account_read` tinyint(1) NOT NULL DEFAULT '0',
  `account_write` tinyint(1) NOT NULL DEFAULT '0',
  `account_transaction_read` tinyint(1) NOT NULL DEFAULT '0',
  `account_transaction_write` tinyint(1) NOT NULL DEFAULT '0',
  `account_reconcile` tinyint(1) NOT NULL DEFAULT '0',
  `books` tinyint(1) NOT NULL DEFAULT '0',
  `reports` tinyint(1) NOT NULL DEFAULT '0',
  `setup` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(64) DEFAULT NULL,
  `value` text,
  `reserved` boolean NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `taxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `code` varchar(16) DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL,
  `percent` decimal(6,6) DEFAULT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT NULL,
  `date_due` DATE DEFAULT NULL,
  `date_due_months_increment` TINYINT UNSIGNED DEFAULT NULL, 
  `license` varchar( 255 ) DEFAULT NULL,
  `authority` varchar( 255 ) DEFAULT NULL,
  `address1` varchar( 255 ) DEFAULT NULL,
  `address2` varchar( 255 ) DEFAULT NULL,
  `city` varchar( 255 ) DEFAULT NULL,
  `state` varchar( 255 ) DEFAULT NULL,
  `zip` varchar( 255 ) DEFAULT NULL,
  `country` varchar( 255 ) DEFAULT NULL,
  `visible` boolean NOT NULL DEFAULT TRUE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `tax_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tax_id` bigint(20) unsigned DEFAULT NULL,
  `form_id` bigint(20) unsigned DEFAULT NULL,
  `tax_payment_id` bigint(20) unsigned DEFAULT NULL,
  `date` date DEFAULT NULL,
  `type` enum('invoice','refund') DEFAULT NULL,
  `form_line_amount` decimal(15,2) DEFAULT NULL,
  `form_line_taxable_amount` decimal(15,2) DEFAULT NULL,
  `tax_percent` decimal(6,6) DEFAULT NULL,
  `total` decimal(15,2) DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `tax_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tax_id` bigint(20) unsigned DEFAULT NULL,
  `date` date DEFAULT NULL,
  `date_start` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `writeoff_amount` decimal(15,2) DEFAULT NULL,
  `transaction_id` bigint(20) unsigned DEFAULT NULL,
  `invoiced_line_amount` decimal( 15, 2 ) NULL DEFAULT NULL,
  `invoiced_line_taxable_amount` decimal( 15, 2 ) NULL DEFAULT NULL,
  `invoiced_amount` decimal( 15, 2 ) NULL DEFAULT NULL,
  `refunded_line_amount` decimal( 15, 2 ) NULL DEFAULT NULL,
  `refunded_line_taxable_amount` decimal( 15, 2 ) NULL DEFAULT NULL,
  `refunded_amount` decimal( 15, 2 ) NULL DEFAULT NULL,
  `net_line_amount` decimal( 15, 2 ) NULL DEFAULT NULL,
  `net_line_taxable_amount` decimal( 15, 2 ) NULL DEFAULT NULL,
  `net_amount` decimal( 15, 2 ) NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_id` bigint(20) unsigned DEFAULT NULL,
  `form_type` enum('sale', 'purchase', 'expense', 'tax_payment') DEFAULT NULL,
  `form_id` bigint UNSIGNED NULL DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `reference` varchar(16) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `payment` enum('customer','vendor','expense') DEFAULT NULL,
  `close_books` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `email` varchar(256) DEFAULT NULL,
  `reset` varchar(128) DEFAULT NULL,
  `reset_expiration` bigint(20) unsigned DEFAULT NULL,
  `password_change`tinyint(1) NOT NULL DEFAULT '0',
  `password` varchar(128) DEFAULT NULL,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `auth_expiration` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ; ALTER TABLE `accounts` 
ADD INDEX (`parent_account_id`); ALTER TABLE `accounts` 
ADD INDEX (`account_type_id`); ALTER TABLE `account_reconciles` 
ADD INDEX (`account_id`); ALTER TABLE `account_transactions` 
ADD INDEX (`transaction_id`); ALTER TABLE `account_transactions` 
ADD INDEX (`account_id`); ALTER TABLE `account_transactions` 
ADD INDEX (`account_reconcile_id`); ALTER TABLE `account_transaction_forms` 
ADD INDEX (`account_transaction_id`); ALTER TABLE `account_transaction_forms` 
ADD INDEX (`form_id`); ALTER TABLE `entities` 
ADD INDEX (`default_shipping_address_id`); ALTER TABLE `entities` 
ADD INDEX (`default_billing_address_id`); ALTER TABLE `entities` 
ADD INDEX (`default_remit_address_id`); ALTER TABLE `entities` 
ADD INDEX (`default_account_id`); ALTER TABLE `entity_addresses` 
ADD INDEX (`entity_id`); ALTER TABLE `forms` 
ADD INDEX (`entity_id`); ALTER TABLE `forms` 
ADD INDEX (`account_id`); ALTER TABLE `forms` 
ADD INDEX (`create_transaction_id`); ALTER TABLE `forms` 
ADD INDEX (`invoice_transaction_id`); ALTER TABLE `forms` 
ADD INDEX (`cancel_transaction_id`); ALTER TABLE `forms` 
ADD INDEX (`refund_form_id`); ALTER TABLE `forms` 
ADD INDEX (`shipping_address_id`); ALTER TABLE `forms` 
ADD INDEX (`billing_address_id`); ALTER TABLE `forms` 
ADD INDEX (`remit_address_id`); ALTER TABLE `form_lines` 
ADD INDEX (`form_id`); ALTER TABLE `form_lines` 
ADD INDEX (`account_id`); ALTER TABLE `form_taxes` 
ADD INDEX (`form_id`); ALTER TABLE `form_taxes` 
ADD INDEX (`tax_id`); ALTER TABLE `taxes` 
ADD INDEX (`account_id`); ALTER TABLE `tax_items` 
ADD INDEX (`tax_id`); ALTER TABLE `tax_items` 
ADD INDEX (`form_id`); ALTER TABLE `tax_items` 
ADD INDEX (`tax_payment_id`); ALTER TABLE `tax_payments` 
ADD INDEX (`tax_id`); ALTER TABLE `tax_payments` 
ADD INDEX (`transaction_id`); ALTER TABLE `transactions` 
ADD INDEX (`entity_id`); ALTER TABLE `transactions` 
ADD INDEX (`form_id`); ALTER TABLE `users` 
ADD INDEX (`role_id`)
