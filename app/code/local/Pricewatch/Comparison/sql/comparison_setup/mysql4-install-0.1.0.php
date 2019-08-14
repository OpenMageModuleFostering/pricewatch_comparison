<?php

$installer = $this;

$installer->startSetup();

$installer->run("

DROP TABLE IF EXISTS {$this->getTable('pricewatch_comparison')};
CREATE TABLE {$this->getTable('pricewatch_comparison')} (
  `product_id` int(11) unsigned NOT NULL,
  `sku` varchar(255) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `price` decimal(12,4) NOT NULL default '0.00',
  `special_price` decimal(12,4) NOT NULL default '0.00',
  `product_url` varchar(255) NOT NULL default '',
  `image_url` varchar(255) NOT NULL default '',
  `category` varchar(255) NOT NULL default '',
  `brand` varchar(255) NOT NULL default '',
  `description` longtext NOT NULL default '',
  `message` text NOT NULL default '',
  `transaction_id` varchar(255) NOT NULL default '0',
  `status` smallint(6) NOT NULL default '0',
  `created_time` datetime NULL,
  `updated_time` datetime NULL,
  `api_time` datetime NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('pricewatch_comparison_status')};
CREATE TABLE {$this->getTable('pricewatch_comparison_status')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `status` smallint(6) NOT NULL default '0',
  `updated_time` datetime NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO {$this->getTable('pricewatch_comparison_status')}(`name`, `status`,`updated_time`) values ('bulkupload','1', ''),('bulkrequired','1', '');


");

$installer->endSetup();