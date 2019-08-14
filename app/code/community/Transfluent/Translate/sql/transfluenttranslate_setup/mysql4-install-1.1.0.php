<?php
/**
 * Transfluent extension for Magento, (c) 2013, 1.1.1
 * Author: coders@transfluent.com
 */
$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer->startSetup();
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('transfluenttranslate')};
CREATE TABLE {$this->getTable('transfluenttranslate')} (
  `id` int unsigned NOT NULL auto_increment,
  `source_text_hash` varchar(32) NULL,
  `text_id` varchar(255) NOT NULL,
  -- `type` int unsigned NOT NULL,
  -- `ref_id` int unsigned NOT NULL,
  -- `order_id` int unsigned NOT NULL,
  -- `word_count` int unsigned NOT NULL,
  `source_store` int unsigned NOT NULL,
  `target_store` int unsigned NOT NULL,
  `source_language` int unsigned NOT NULL,
  `target_language` int unsigned NOT NULL,
  `level` int unsigned NOT NULL,
  `status` int unsigned NOT NULL,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
  -- KEY `order_idx` (`order_id`),
  -- KEY `ref_idx` (`type`, `ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->endSetup();
